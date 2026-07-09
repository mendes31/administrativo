<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Criptografia AES-256-GCM para conteúdo de denúncias em repouso.
 */
final class WhistleblowingEncryptionService
{
    private const CIPHER = 'aes-256-gcm';

    private ?string $keyMaterialOverride = null;

    public function withKeyMaterial(string $material): self
    {
        $clone = new self();
        $clone->keyMaterialOverride = $material;

        return $clone;
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->deriveKeyForWrite();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha ao criptografar conteúdo da denúncia.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false) {
            throw new \RuntimeException('Conteúdo criptografado inválido.');
        }

        return $this->decryptBinary($raw);
    }

    /** Criptografa bytes brutos (anexos em disco). Formato: IV(12) + tag(16) + ciphertext */
    public function encryptBinary(string $plaintext): string
    {
        $key = $this->deriveKeyForWrite();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha ao criptografar arquivo da denúncia.');
        }

        return $iv . $tag . $ciphertext;
    }

    public function decryptBinary(string $raw): string
    {
        if (strlen($raw) < 29) {
            throw new \RuntimeException('Conteúdo criptografado inválido.');
        }

        if ($this->keyMaterialOverride !== null) {
            return $this->decryptBinaryWithDerivedKey($raw, $this->deriveKeyForRead());
        }

        $lastError = null;
        foreach ($this->readKeyMaterialCandidates() as $material) {
            try {
                $key = hash('sha256', $material, true);

                return $this->decryptBinaryWithDerivedKey($raw, $key);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new \RuntimeException('Falha ao descriptografar conteúdo da denúncia.');
    }

    public function encryptJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->encrypt($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptJson(string $encoded): array
    {
        $json = $this->decrypt($encoded);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    private function deriveKeyForWrite(): string
    {
        if ($this->keyMaterialOverride !== null) {
            if ($this->keyMaterialOverride === '') {
                throw new \RuntimeException('Material de chave vazio para criptografia.');
            }

            return hash('sha256', $this->keyMaterialOverride, true);
        }

        $material = $this->resolveKeyMaterial();
        if ($material === '') {
            throw new \RuntimeException(
                'Chave de criptografia não configurada. Defina em Canal de Denúncias → Configuração (mín. 32 caracteres).'
            );
        }

        return hash('sha256', $material, true);
    }

    private function deriveKeyForRead(): string
    {
        if ($this->keyMaterialOverride !== null) {
            if ($this->keyMaterialOverride === '') {
                return hash('sha256', WhistleblowingAttachmentFilenameHelper::legacyFallbackKeyMaterial(), true);
            }

            return hash('sha256', $this->keyMaterialOverride, true);
        }

        $material = $this->resolveKeyMaterial();
        if ($material !== '') {
            return hash('sha256', $material, true);
        }

        $fallback = ($_ENV['APP_NAME'] ?? 'app') . '|' . ($_ENV['DB_NAME'] ?? 'db') . '|whistleblowing';

        return hash('sha256', $fallback, true);
    }

    private function resolveKeyMaterial(): string
    {
        try {
            $dbKey = (new WhistleblowingConfigRepository())->getEncryptionKey();
            if (strlen($dbKey) >= WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
                return $dbKey;
            }
        } catch (\Throwable) {
            // tabela ainda não migrada
        }

        $envKey = trim((string) ($_ENV['WHISTLEBLOWING_ENCRYPTION_KEY'] ?? ''));
        if (strlen($envKey) >= WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
            return $envKey;
        }

        return '';
    }

    /**
     * Ordem: chave forte configurada, depois chave legada do sistema (migração).
     *
     * @return list<string>
     */
    private function readKeyMaterialCandidates(): array
    {
        $candidates = [];
        $strong = $this->resolveKeyMaterial();
        if ($strong !== '') {
            $candidates[] = $strong;
        }
        $candidates[] = WhistleblowingAttachmentFilenameHelper::legacyFallbackKeyMaterial();

        return array_values(array_unique($candidates));
    }

    private function decryptBinaryWithDerivedKey(string $raw, string $derivedKey): string
    {
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $derivedKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Falha ao descriptografar conteúdo da denúncia.');
        }

        return $plaintext;
    }
}
