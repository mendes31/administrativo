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

    public function encrypt(string $plaintext): string
    {
        $key = $this->deriveKey();
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
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Conteúdo criptografado inválido.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $key = $this->deriveKey();

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Falha ao descriptografar conteúdo da denúncia.');
        }

        return $plaintext;
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

    private function deriveKey(): string
    {
        try {
            $dbKey = (new WhistleblowingConfigRepository())->getEncryptionKey();
            if ($dbKey !== '') {
                return hash('sha256', $dbKey, true);
            }
        } catch (\Throwable) {
            // tabela ainda não migrada
        }

        $envKey = trim((string) ($_ENV['WHISTLEBLOWING_ENCRYPTION_KEY'] ?? ''));
        if ($envKey === '') {
            $fallback = ($_ENV['APP_NAME'] ?? 'app') . '|' . ($_ENV['DB_NAME'] ?? 'db') . '|whistleblowing';
            $envKey = $fallback;
        }

        return hash('sha256', $envKey, true);
    }
}
