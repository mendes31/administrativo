<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Criptografia AES-256-GCM das senhas RustDesk em repouso (consulta/cópia).
 * Preferência: TI_RUSTDESK_ENCRYPTION_KEY no .env.
 * Fallback: ficheiro em storage/private/secrets/ti_rustdesk.key (gerado na primeira gravação).
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
                'Não foi possível abrir a senha. A chave de criptografia mudou em relação à usada no cadastro.'
            );
        }

        return $plain;
    }

    private static function derivedKey(): string
    {
        $material = self::rawKeyMaterial();
        if (strlen($material) < self::MIN_KEY_LENGTH) {
            throw new \RuntimeException(
                'Não foi possível preparar a chave de criptografia das senhas RustDesk (storage/private/secrets).'
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

        return self::storedOrCreateKey();
    }

    private static function storedOrCreateKey(): string
    {
        $path = self::keyFilePath();
        if (is_readable($path)) {
            $existing = trim((string) file_get_contents($path));
            if (strlen($existing) >= self::MIN_KEY_LENGTH) {
                return $existing;
            }
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            return '';
        }

        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            return '';
        }
        try {
            flock($fh, LOCK_EX);
            rewind($fh);
            $existing = trim((string) stream_get_contents($fh));
            if (strlen($existing) >= self::MIN_KEY_LENGTH) {
                return $existing;
            }
            $generated = bin2hex(random_bytes(32));
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $generated);
            fflush($fh);

            return $generated;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private static function keyFilePath(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'private'
            . DIRECTORY_SEPARATOR . 'secrets'
            . DIRECTORY_SEPARATOR . 'ti_rustdesk.key';
    }
}
