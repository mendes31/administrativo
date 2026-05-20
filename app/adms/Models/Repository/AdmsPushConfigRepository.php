<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class AdmsPushConfigRepository extends DbConnection
{
    public function getConfig(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM adms_push_config ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: [];
    }

    public function isEnabled(): bool
    {
        $config = $this->getConfig();
        if ($config === []) {
            return false;
        }

        return !empty($config['is_enabled'])
            && !empty($config['vapid_public_key'])
            && !empty($config['vapid_private_key'])
            && !empty($config['vapid_subject']);
    }

    public function getPublicKey(): ?string
    {
        $config = $this->getConfig();
        $key = trim((string) ($config['vapid_public_key'] ?? ''));

        return $key !== '' ? $key : null;
    }

    public function saveSettings(array $data): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $oldData = $this->getConfig();
        $isUpdate = $oldData !== [] && !empty($oldData['id']);

        if ($isUpdate) {
            $sql = 'UPDATE adms_push_config SET
                        vapid_subject = :vapid_subject,
                        is_enabled = :is_enabled,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', (int) $oldData['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_push_config (
                        vapid_public_key, vapid_private_key, vapid_subject, is_enabled, created_at, updated_at
                    ) VALUES (
                        NULL, NULL, :vapid_subject, :is_enabled, NOW(), NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
        }

        $stmt->bindValue(':vapid_subject', $data['vapid_subject']);
        $stmt->bindValue(':is_enabled', (int) ($data['is_enabled'] ?? 0), PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            $this->registerLog($oldData, $isUpdate ? 'UPDATE' : 'INSERT');
        }

        return $result;
    }

    public function saveVapidKeys(string $publicKey, string $privateKey): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $oldData = $this->getConfig();
        $isUpdate = $oldData !== [] && !empty($oldData['id']);

        if ($isUpdate) {
            $sql = 'UPDATE adms_push_config SET
                        vapid_public_key = :vapid_public_key,
                        vapid_private_key = :vapid_private_key,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', (int) $oldData['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_push_config (
                        vapid_public_key, vapid_private_key, vapid_subject, is_enabled, created_at, updated_at
                    ) VALUES (
                        :vapid_public_key, :vapid_private_key, :vapid_subject, 0, NOW(), NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
            $defaultSubject = 'mailto:' . (string) ($_ENV['EMAIL_TI'] ?? 'chamados@tiaraju.com.br');
            $stmt->bindValue(':vapid_subject', $defaultSubject);
        }

        $stmt->bindValue(':vapid_public_key', $publicKey);
        $stmt->bindValue(':vapid_private_key', $privateKey);
        $result = $stmt->execute();

        if ($result) {
            $this->registerLog($oldData, $isUpdate ? 'UPDATE' : 'INSERT');
        }

        return $result;
    }

    private function registerLog(array $oldData, string $action): void
    {
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        $newData = $this->getConfig();
        if ($newData === []) {
            return;
        }

        $newId = (int) ($newData['id'] ?? 0);
        if ($newId <= 0) {
            return;
        }

        $oldSanitized = $oldData;
        $newSanitized = $newData;
        if ($oldSanitized !== []) {
            $oldSanitized['vapid_private_key'] = $this->maskSecret((string) ($oldSanitized['vapid_private_key'] ?? ''));
        }
        $newSanitized['vapid_private_key'] = $this->maskSecret((string) ($newSanitized['vapid_private_key'] ?? ''));

        if ($action === 'UPDATE' && $oldData !== []) {
            LogAlteracaoService::registrarAlteracao(
                'adms_push_config',
                (int) $oldData['id'],
                $usuarioId,
                'UPDATE',
                $oldSanitized,
                $newSanitized
            );
            return;
        }

        LogAlteracaoService::registrarAlteracao(
            'adms_push_config',
            $newId,
            $usuarioId,
            'INSERT',
            [],
            $newSanitized
        );
    }

    private function maskSecret(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '********';
    }

    private function tableExists(): bool
    {
        $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_push_config'");
        if ($stmt === false) {
            return false;
        }

        return (bool) $stmt->fetch(PDO::FETCH_NUM);
    }
}
