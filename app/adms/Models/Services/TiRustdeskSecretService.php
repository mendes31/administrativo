<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Criptografia AES-256-GCM das senhas RustDesk em repouso.
 * Chave exclusiva (TI_RUSTDESK_ENCRYPTION_KEY) — não reutiliza a chave do canal de denúncias.
 */
final class TiRustdeskSecretService
{
    private const CIPHER = 'aes-256-gcm';
    private const MIN_KEY_LENGTH = 32;

    public static function isConfigured(): bool
    {
        return strlen(self::rawKeyMaterial()) >= self::MIN_KEY_LENGTH;
    }

    public static function encrypt(string $plaintext): string
    {
        $key = self::derivedKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha ao criptografar a senha do RustDesk.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Senha criptografada inválida.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plain = openssl_decrypt($ciphertext, self::CIPHER, self::derivedKey(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($plain === false) {
            throw new \RuntimeException(
                'Não foi possível abrir a senha. Confira se TI_RUSTDESK_ENCRYPTION_KEY no .env é a mesma usada no cadastro.'
            );
        }

        return $plain;
    }

    private static function derivedKey(): string
    {
        $material = self::rawKeyMaterial();
        if (strlen($material) < self::MIN_KEY_LENGTH) {
            throw new \RuntimeException(
                'Chave de criptografia não configurada. Defina TI_RUSTDESK_ENCRYPTION_KEY no .env (mín. 32 caracteres).'
            );
        }

        return hash('sha256', $material, true);
    }

    private static function rawKeyMaterial(): string
    {
        $fromEnv = trim((string) ($_ENV['TI_RUSTDESK_ENCRYPTION_KEY'] ?? ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $fromGetenv = getenv('TI_RUSTDESK_ENCRYPTION_KEY');
        if (is_string($fromGetenv) && trim($fromGetenv) !== '') {
            return trim($fromGetenv);
        }

        return '';
    }
}
