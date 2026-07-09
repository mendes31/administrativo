<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;

/**
 * Rotação da chave AES do canal: recriptografa relatos, mensagens, notas e anexos em disco.
 * Suporta base mista (chave legada + chave forte) detectando a chave correta por registro.
 */
final class WhistleblowingKeyRotationService
{
    /**
     * @return array{
     *   reports: int,
     *   messages: int,
     *   status_notes: int,
     *   attachments: int,
     *   files_reencrypted: int,
     *   legacy_migrated: int
     * }
     */
    public function rotate(string $oldKeyMaterial, string $newKeyMaterial, bool $tryLegacyFallback = true): array
    {
        $oldKeyMaterial = trim($oldKeyMaterial);
        $newKeyMaterial = trim($newKeyMaterial);

        if (strlen($newKeyMaterial) < WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
            throw new \InvalidArgumentException('A nova chave deve ter pelo menos 32 caracteres.');
        }

        if ($oldKeyMaterial !== '' && $oldKeyMaterial === $newKeyMaterial) {
            throw new \InvalidArgumentException('A nova chave deve ser diferente da chave atual informada.');
        }

        $oldCandidates = $this->collectOldKeyCandidates($oldKeyMaterial, $tryLegacyFallback);
        $newEnc = (new WhistleblowingEncryptionService())->withKeyMaterial($newKeyMaterial);

        $reportsRepo = new WhistleblowingReportsRepository();
        $messagesRepo = new WhistleblowingMessagesRepository();
        $uploadService = new WhistleblowingUploadService();

        $reportRows = $reportsRepo->listRawReportsContent();
        $messageRows = $messagesRepo->listRawMessages();
        $statusRows = $reportsRepo->listRawStatusNotes();
        $attachmentRows = $messagesRepo->listRawAttachments();

        $this->validateDecryptable($oldCandidates, $reportRows, $messageRows, $statusRows, $attachmentRows, $uploadService);

        $stats = [
            'reports' => 0,
            'messages' => 0,
            'status_notes' => 0,
            'attachments' => 0,
            'files_reencrypted' => 0,
            'legacy_migrated' => 0,
        ];

        $conn = $reportsRepo->getConnection();
        $conn->beginTransaction();

        try {
            foreach ($reportRows as $row) {
                $plain = $this->decryptJsonWithCandidates(
                    (string) ($row['content_encrypted'] ?? ''),
                    $oldCandidates,
                    'denúncia #' . (int) $row['id']
                );
                $encrypted = $newEnc->encryptJson($plain);
                if (!$reportsRepo->updateContentEncrypted((int) $row['id'], $encrypted)) {
                    throw new \RuntimeException('Falha ao atualizar denúncia #' . (int) $row['id']);
                }
                $stats['reports']++;
            }

            foreach ($messageRows as $row) {
                $plain = $this->decryptWithCandidates(
                    (string) ($row['message_encrypted'] ?? ''),
                    $oldCandidates,
                    'mensagem #' . (int) $row['id']
                );
                $encrypted = $newEnc->encrypt($plain);
                if (!$messagesRepo->updateMessageEncrypted((int) $row['id'], $encrypted)) {
                    throw new \RuntimeException('Falha ao atualizar mensagem #' . (int) $row['id']);
                }
                $stats['messages']++;
            }

            foreach ($statusRows as $row) {
                $plain = $this->decryptWithCandidates(
                    (string) ($row['notes_encrypted'] ?? ''),
                    $oldCandidates,
                    'nota de status #' . (int) $row['id']
                );
                $encrypted = $newEnc->encrypt($plain);
                if (!$reportsRepo->updateStatusNotesEncrypted((int) $row['id'], $encrypted)) {
                    throw new \RuntimeException('Falha ao atualizar nota de status #' . (int) $row['id']);
                }
                $stats['status_notes']++;
            }

            $pendingFiles = [];

            foreach ($attachmentRows as $row) {
                $attachmentId = (int) $row['id'];
                $storedName = (string) ($row['stored_name'] ?? '');
                $originalPlain = $this->decryptOriginalNameWithCandidates($row, $oldCandidates);
                $filePlain = $this->readAttachmentWithCandidates($storedName, $oldCandidates, $uploadService);
                if ($filePlain === null) {
                    throw new \RuntimeException('Falha ao ler anexo #' . $attachmentId . ' para rotação.');
                }

                $written = $uploadService->writeEncryptedFile($filePlain, $newEnc);
                if ($written === null) {
                    throw new \RuntimeException('Falha ao recriptografar arquivo do anexo #' . $attachmentId);
                }

                $newOriginalEncrypted = $newEnc->encrypt($originalPlain);
                if (!$messagesRepo->updateAttachmentAfterRotation(
                    $attachmentId,
                    (string) $written['stored_name'],
                    $newOriginalEncrypted,
                    (int) $written['size_bytes']
                )) {
                    @unlink(WhistleblowingUploadService::getFilePath((string) $written['stored_name']));
                    throw new \RuntimeException('Falha ao atualizar metadados do anexo #' . $attachmentId);
                }

                $pendingFiles[] = ['old' => $storedName, 'new' => (string) $written['stored_name']];
                $stats['attachments']++;
                $stats['files_reencrypted']++;
                if (!str_ends_with(strtolower($storedName), '.enc')) {
                    $stats['legacy_migrated']++;
                }
            }

            $configRepo = new WhistleblowingConfigRepository();
            if (!$configRepo->saveEncryptionKey($newKeyMaterial)) {
                throw new \RuntimeException('Falha ao gravar a nova chave na configuração.');
            }

            $conn->commit();

            foreach ($pendingFiles as $file) {
                if ($file['old'] !== $file['new']) {
                    WhistleblowingUploadService::deleteFile($file['old']);
                }
            }
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        return $stats;
    }

    /**
     * @return list<string>
     */
    private function collectOldKeyCandidates(string $userOldKey, bool $tryLegacyFallback): array
    {
        $candidates = [];

        if ($userOldKey !== '') {
            $candidates[] = $userOldKey;
        }

        $configRepo = new WhistleblowingConfigRepository();
        $current = trim($configRepo->getEncryptionKey());
        if (strlen($current) >= WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
            $candidates[] = $current;
        }

        $envKey = trim((string) ($_ENV['WHISTLEBLOWING_ENCRYPTION_KEY'] ?? ''));
        if (strlen($envKey) >= WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
            $candidates[] = $envKey;
        }

        if ($tryLegacyFallback) {
            $candidates[] = WhistleblowingAttachmentFilenameHelper::legacyFallbackKeyMaterial();
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param list<string> $candidates
     * @param list<array<string, mixed>> $reportRows
     * @param list<array<string, mixed>> $messageRows
     * @param list<array<string, mixed>> $statusRows
     * @param list<array<string, mixed>> $attachmentRows
     */
    private function validateDecryptable(
        array $candidates,
        array $reportRows,
        array $messageRows,
        array $statusRows,
        array $attachmentRows,
        WhistleblowingUploadService $uploadService
    ): void {
        if ($candidates === []) {
            throw new \InvalidArgumentException('Informe a chave atual ou marque a opção de chave legada.');
        }

        foreach ($reportRows as $row) {
            try {
                $this->decryptJsonWithCandidates(
                    (string) ($row['content_encrypted'] ?? ''),
                    $candidates,
                    'denúncia #' . (int) $row['id']
                );
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Não foi possível ler denúncia #' . (int) $row['id'] . ' com as chaves testadas. ' . $e->getMessage()
                );
            }
        }

        foreach ($messageRows as $row) {
            try {
                $this->decryptWithCandidates(
                    (string) ($row['message_encrypted'] ?? ''),
                    $candidates,
                    'mensagem #' . (int) $row['id']
                );
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Não foi possível ler mensagem #' . (int) $row['id'] . ' com as chaves testadas.'
                );
            }
        }

        foreach ($statusRows as $row) {
            try {
                $this->decryptWithCandidates(
                    (string) ($row['notes_encrypted'] ?? ''),
                    $candidates,
                    'nota de status #' . (int) $row['id']
                );
            } catch (\Throwable) {
                throw new \RuntimeException(
                    'Não foi possível ler nota de status #' . (int) $row['id'] . ' com as chaves testadas.'
                );
            }
        }

        foreach ($attachmentRows as $row) {
            $id = (int) $row['id'];
            try {
                $this->decryptOriginalNameWithCandidates($row, $candidates);
            } catch (\Throwable) {
                throw new \RuntimeException(
                    'Não foi possível ler nome do anexo #' . $id . ' com as chaves testadas.'
                );
            }

            if ($this->readAttachmentWithCandidates((string) ($row['stored_name'] ?? ''), $candidates, $uploadService) === null) {
                throw new \RuntimeException(
                    'Arquivo ausente ou ilegível: anexo #' . $id . '.'
                );
            }
        }
    }

    /**
     * @param list<string> $candidates
     * @return array<string, mixed>
     */
    private function decryptJsonWithCandidates(string $encoded, array $candidates, string $label): array
    {
        $lastError = null;
        foreach ($candidates as $material) {
            try {
                return (new WhistleblowingEncryptionService())->withKeyMaterial($material)->decryptJson($encoded);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        throw new \RuntimeException('Falha ao descriptografar ' . $label . '.', 0, $lastError);
    }

    /**
     * @param list<string> $candidates
     */
    private function decryptWithCandidates(string $encoded, array $candidates, string $label): string
    {
        $lastError = null;
        foreach ($candidates as $material) {
            try {
                return (new WhistleblowingEncryptionService())->withKeyMaterial($material)->decrypt($encoded);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        throw new \RuntimeException('Falha ao descriptografar ' . $label . '.', 0, $lastError);
    }

    /**
     * @param list<string> $candidates
     * @param array<string, mixed> $row
     */
    private function decryptOriginalNameWithCandidates(array $row, array $candidates): string
    {
        $lastError = null;
        foreach ($candidates as $material) {
            try {
                $name = trim(
                    (new WhistleblowingEncryptionService())->withKeyMaterial($material)
                        ->decrypt((string) ($row['original_name_encrypted'] ?? ''))
                );
                if ($name !== '' && $name !== 'arquivo') {
                    return $name;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError === null) {
            return WhistleblowingAttachmentFilenameHelper::resolve($row);
        }

        return WhistleblowingAttachmentFilenameHelper::resolve($row);
    }

    /**
     * @param list<string> $candidates
     */
    private function readAttachmentWithCandidates(
        string $storedName,
        array $candidates,
        WhistleblowingUploadService $uploadService
    ): ?string {
        foreach ($candidates as $material) {
            $contents = $uploadService->readFileContentsWithEncryption(
                $storedName,
                (new WhistleblowingEncryptionService())->withKeyMaterial($material)
            );
            if ($contents !== null) {
                return $contents;
            }
        }

        return null;
    }
}
