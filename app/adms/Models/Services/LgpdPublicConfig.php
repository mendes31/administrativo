<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Configuração do canal público LGPD (DPO, empresa, PDFs).
 */
final class LgpdPublicConfig
{
    public static function companyName(): string
    {
        $name = trim((string) ($_ENV['LGPD_EMPRESA_NOME'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($_ENV['APP_NAME'] ?? 'Tiaraju')) ?: 'Tiaraju';
    }

    public static function dpoNome(): string
    {
        return trim((string) ($_ENV['LGPD_DPO_NOME'] ?? ''));
    }

    public static function dpoEmail(): string
    {
        return trim((string) ($_ENV['LGPD_DPO_EMAIL'] ?? ''));
    }

    /** E-mail interno para avisar o DPO (não publicado se LGPD_DPO_EMAIL estiver vazio). */
    public static function notifyEmail(): string
    {
        $email = self::dpoEmail();
        if ($email !== '') {
            return $email;
        }

        return trim((string) ($_ENV['EMAIL_ADM'] ?? ''));
    }

    public static function dpoTelefone(): string
    {
        return trim((string) ($_ENV['LGPD_DPO_TELEFONE'] ?? ''));
    }

    public static function baseUrl(): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? '/'), '/') . '/lgpd';
    }

    public static function url(string $suffix = ''): string
    {
        $suffix = trim($suffix, '/');
        $base = self::baseUrl();

        return $suffix === '' ? $base : $base . '/' . $suffix;
    }

    /**
     * @return array{cartilha:?string,carta:?string}
     */
    public static function documentPaths(): array
    {
        return [
            'cartilha' => self::resolvePdfPath(
                (string) ($_ENV['LGPD_CARTILHA_PATH'] ?? 'storage/lgpd/publico/cartilha.pdf')
            ),
            'carta' => self::resolvePdfPath(
                (string) ($_ENV['LGPD_CARTA_COMPROMISSO_PATH'] ?? 'storage/lgpd/publico/carta-compromisso.pdf')
            ),
        ];
    }

    private static function resolvePdfPath(string $relativeOrAbsolute): ?string
    {
        $relativeOrAbsolute = trim($relativeOrAbsolute);
        if ($relativeOrAbsolute === '') {
            return null;
        }

        $path = $relativeOrAbsolute;
        if (!preg_match('#^[A-Za-z]:\\\\|^/#', $path)) {
            $root = dirname(__DIR__, 4);
            $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeOrAbsolute);
        }

        if (!is_file($path) || strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        return $path;
    }
}
