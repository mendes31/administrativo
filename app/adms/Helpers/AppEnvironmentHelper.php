<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Identifica se a instância é base local de teste / homologação.
 *
 * Importante: produção NÃO deve ser marcada como teste só porque APP_ENV
 * veio diferente de "production" (ex.: development copiado do .env.exemple).
 * O marcador [TESTE] só aparece quando há indício claro de base local/teste.
 */
final class AppEnvironmentHelper
{
    /**
     * True apenas em ambiente local / homologação / teste.
     * Em produção (ou base de produção com APP_ENV mal configurado) retorna false.
     */
    public static function isNonProduction(): bool
    {
        return self::isLocalTestEnvironment();
    }

    /**
     * Critérios (qualquer um basta), nesta ordem:
     * 1. APP_ENV = production|prod → nunca é teste.
     * 2. Nome do banco indica homolog/teste/dev/local/sandbox.
     * 3. URL_ADM aponta para localhost / 127.0.0.1.
     * 4. APP_ENV de desenvolvimento E DB_HOST local (localhost/127.0.0.1).
     */
    public static function isLocalTestEnvironment(): bool
    {
        $env = strtolower(trim((string) ($_ENV['APP_ENV'] ?? '')));

        if (in_array($env, ['production', 'prod'], true)) {
            return false;
        }

        $db = strtolower(trim((string) ($_ENV['DB_NAME'] ?? '')));
        if ($db !== '' && preg_match('/homolog|teste|test|_dev|sandbox|local/i', $db) === 1) {
            return true;
        }

        $url = strtolower(trim((string) ($_ENV['URL_ADM'] ?? '')));
        if ($url !== '' && preg_match('#https?://(localhost|127\.0\.0\.1)([:/]|$)#i', $url) === 1) {
            return true;
        }

        $dbHost = strtolower(trim((string) ($_ENV['DB_HOST'] ?? '')));
        $isLocalDbHost = in_array($dbHost, ['localhost', '127.0.0.1', '::1'], true);
        $isDevEnv = in_array($env, [
            'development', 'dev', 'local', 'test', 'testing', 'homolog', 'homologacao', 'homologação',
        ], true);

        if ($isDevEnv && $isLocalDbHost) {
            return true;
        }

        return false;
    }

    /**
     * Prefixo curto para título de notificação in-app, ex.: "[TESTE] ".
     */
    public static function inAppTitlePrefix(): string
    {
        return self::isLocalTestEnvironment() ? '[TESTE] ' : '';
    }

    /**
     * Texto curto para assunto de e-mail, ex.: "[TESTE/HOMOLOGAÇÃO] ".
     */
    public static function emailSubjectPrefix(): string
    {
        if (!self::isLocalTestEnvironment()) {
            return '';
        }

        return '[TESTE/HOMOLOGAÇÃO] ';
    }

    /**
     * Faixa HTML destacada para o corpo do e-mail (ou null em produção).
     */
    public static function emailHtmlBanner(): ?string
    {
        if (!self::isLocalTestEnvironment()) {
            return null;
        }

        $db = trim((string) ($_ENV['DB_NAME'] ?? ''));
        $env = trim((string) ($_ENV['APP_ENV'] ?? ''));
        $detail = [];
        if ($env !== '') {
            $detail[] = 'APP_ENV=' . htmlspecialchars($env, ENT_QUOTES, 'UTF-8');
        }
        if ($db !== '') {
            $detail[] = 'banco=' . htmlspecialchars($db, ENT_QUOTES, 'UTF-8');
        }
        $extra = $detail !== [] ? ' (' . implode(', ', $detail) . ')' : '';

        return '<p style="background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:10px 12px;font-weight:bold;margin:0 0 12px 0;">'
            . '⚠ AMBIENTE DE TESTE / HOMOLOGAÇÃO' . $extra . ' — esta mensagem NÃO é da base de produção.'
            . '</p>';
    }

    /**
     * Linha em texto puro para corpo alternativo do e-mail.
     */
    public static function emailTextBanner(): ?string
    {
        if (!self::isLocalTestEnvironment()) {
            return null;
        }

        $db = trim((string) ($_ENV['DB_NAME'] ?? ''));
        $env = trim((string) ($_ENV['APP_ENV'] ?? ''));
        $extra = '';
        if ($env !== '' || $db !== '') {
            $extra = ' (' . trim($env . ($env !== '' && $db !== '' ? ', ' : '') . ($db !== '' ? 'banco=' . $db : '')) . ')';
        }

        return '*** AMBIENTE DE TESTE / HOMOLOGAÇÃO' . $extra . ' — esta mensagem NÃO é da base de produção. ***';
    }
}
