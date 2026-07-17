<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Envelopamento (wrap) da chave de dados (DEK) com segredo do .env (KEK).
 *
 * Modelo B: o banco guarda apenas o blob cifrado; o .env guarda WHISTLEBLOWING_KEY_WRAP_SECRET.
 * Dump do banco sozinho não revela a DEK. Troca da DEK na Configuração não exige alterar o .env.
 */
final class WhistleblowingKeyWrapService
{
    public const PREFIX = 'wbk1:';
    public const ENV_VAR = 'WHISTLEBLOWING_KEY_WRAP_SECRET';
    public const MIN_SECRET_LENGTH = 32;

    private const CIPHER = 'aes-256-gcm';

    public static function hasWrapSecret(): bool
    {
        return strlen(self::wrapSecret()) >= self::MIN_SECRET_LENGTH;
    }

    public static function wrapSecret(): string
    {
        return trim((string) ($_ENV[self::ENV_VAR] ?? ''));
    }

    public static function isWrapped(string $stored): bool
    {
        return str_starts_with(trim($stored), self::PREFIX);
    }

    /**
     * Prepara a DEK para gravação no banco (sempre envelopada quando o segredo existe).
     *
     * @throws \RuntimeException se o segredo de envelopamento não estiver configurado
     */
    public static function prepareForStorage(string $dek): string
    {
        $dek = trim($dek);
        if ($dek === '') {
            return '';
        }

        if (!self::hasWrapSecret()) {
            throw new \RuntimeException(
                'Configure ' . self::ENV_VAR . ' no .env (mín. ' . self::MIN_SECRET_LENGTH
                . ' caracteres) antes de guardar a chave de criptografia no banco.'
            );
        }

        if (self::isWrapped($dek)) {
            // Já veio envelopada — revalida abrindo e regravando
            $dek = self::unwrap($dek);
        }

        return self::wrap($dek);
    }

    /**
     * Obtém a DEK em claro a partir do valor armazenado (blob envelopado ou legado em claro).
     *
     * @throws \RuntimeException se o valor estiver envelopado e o segredo do .env estiver ausente/incorreto
     */
    public static function resolveStoredKey(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }

        if (!self::isWrapped($stored)) {
            return $stored;
        }

        return self::unwrap($stored);
    }

    public static function wrap(string $dek): string
    {
        $dek = trim($dek);
        if ($dek === '') {
            throw new \InvalidArgumentException('Chave de dados vazia.');
        }
        if (!self::hasWrapSecret()) {
            throw new \RuntimeException('Segredo de envelopamento ausente no .env.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(
            $dek,
            self::CIPHER,
            self::deriveKek(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Falha ao envelopar a chave de criptografia.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public static function unwrap(string $stored): string
    {
        $stored = trim($stored);
        if (!self::isWrapped($stored)) {
            return $stored;
        }
        if (!self::hasWrapSecret()) {
            throw new \RuntimeException(
                'A chave no banco está envelopada, mas ' . self::ENV_VAR
                . ' não está configurado (ou está incorreto) no .env.'
            );
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Blob da chave envelopada está corrompido.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plain = openssl_decrypt($ciphertext, self::CIPHER, self::deriveKek(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false || $plain === '') {
            throw new \RuntimeException(
                'Não foi possível abrir a chave envelopada. Verifique se ' . self::ENV_VAR
                . ' no .env é o mesmo usado na gravação (ou restaure o backup do .env).'
            );
        }

        return $plain;
    }

    /**
     * Se a chave no banco ainda estiver em claro e o segredo existir, envelopa e regrava.
     *
     * @return bool true se migrou nesta chamada
     */
    public static function migratePlaintextKeyIfNeeded(): bool
    {
        if (!self::hasWrapSecret()) {
            return false;
        }

        $repo = new \App\adms\Models\Repository\WhistleblowingConfigRepository();
        $raw = trim((string) ($repo->getRow()['encryption_key'] ?? ''));
        if ($raw === '' || self::isWrapped($raw)) {
            return false;
        }

        if (strlen($raw) < WhistleblowingChannelSecurityService::MIN_KEY_LENGTH) {
            return false;
        }

        return $repo->saveEncryptionKey($raw);
    }

    private static function deriveKek(): string
    {
        return hash('sha256', self::wrapSecret(), true);
    }
}
