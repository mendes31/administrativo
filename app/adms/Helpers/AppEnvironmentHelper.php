<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Identifica se a instância não é produção (homologação / desenvolvimento / teste).
 * Usado para marcar e-mails e alertas e evitar confusão com a base real.
 */
final class AppEnvironmentHelper
{
    public static function isNonProduction(): bool
    {
        $env = strtolower(trim((string)($_ENV['APP_ENV'] ?? '')));
        $db = strtolower(trim((string)($_ENV['DB_NAME'] ?? '')));

        if ($db !== '' && preg_match('/homolog|teste|test|_dev|sandbox|local/i', $db) === 1) {
            return true;
        }

        if ($env !== '' && !in_array($env, ['production', 'prod'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Texto curto para assunto de e-mail, ex.: "[TESTE/HOMOLOGAÇÃO] ".
     */
    public static function emailSubjectPrefix(): string
    {
        if (!self::isNonProduction()) {
            return '';
        }

        return '[TESTE/HOMOLOGAÇÃO] ';
    }

    /**
     * Faixa HTML destacada para o corpo do e-mail (ou null em produção).
     */
    public static function emailHtmlBanner(): ?string
    {
        if (!self::isNonProduction()) {
            return null;
        }

        $db = trim((string)($_ENV['DB_NAME'] ?? ''));
        $env = trim((string)($_ENV['APP_ENV'] ?? ''));
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
        if (!self::isNonProduction()) {
            return null;
        }

        $db = trim((string)($_ENV['DB_NAME'] ?? ''));
        $env = trim((string)($_ENV['APP_ENV'] ?? ''));
        $extra = '';
        if ($env !== '' || $db !== '') {
            $extra = ' (' . trim($env . ($env !== '' && $db !== '' ? ', ' : '') . ($db !== '' ? 'banco=' . $db : '')) . ')';
        }

        return '*** AMBIENTE DE TESTE / HOMOLOGAÇÃO' . $extra . ' — esta mensagem NÃO é da base de produção. ***';
    }
}
