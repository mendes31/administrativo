<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LgpdPortalConfigRepository;

/**
 * Configuração do canal público LGPD (DPO, empresa, PDFs, comitê).
 * Prioridade: banco de dados → variáveis .env (legado).
 */
final class LgpdPublicConfig
{
    private static ?LgpdPortalConfigRepository $repo = null;

    private static function repo(): LgpdPortalConfigRepository
    {
        if (self::$repo === null) {
            self::$repo = new LgpdPortalConfigRepository();
        }

        return self::$repo;
    }

    public static function companyName(): string
    {
        return self::repo()->empresaNome();
    }

    public static function dpoNome(): string
    {
        return self::repo()->dpoNome();
    }

    public static function dpoEmail(): string
    {
        return self::repo()->dpoEmail();
    }

    /** E-mail interno para avisar o DPO (não publicado se vazio). */
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
        return self::repo()->dpoTelefone();
    }

    public static function comiteTitulo(): string
    {
        return self::repo()->comiteTitulo();
    }

    public static function comiteDescricao(): string
    {
        return self::repo()->comiteDescricao();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function comiteMembrosAtivos(): array
    {
        return self::repo()->getComiteMembros(true);
    }

    /**
     * URL pública do portal (preferência: raiz do site via gateway /lgpd).
     * Com URL_LGPD no .env: https://www.tiaraju.com.br/lgpd
     * Sem URL_LGPD: fallback {URL_ADM}/lgpd
     */
    public static function baseUrl(): string
    {
        $fromEnv = trim((string) ($_ENV['URL_LGPD'] ?? ''));
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/');
        }

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
            'cartilha' => self::resolvePdfPath(self::repo()->cartilhaPath()),
            'carta' => self::resolvePdfPath(self::repo()->cartaCompromissoPath()),
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
