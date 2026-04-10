<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Desbloqueio temporário na sessão após reautenticação para download de PDF de folha.
 */
final class PayrollDocumentDownloadUnlock
{
    public const TTL_SECONDS = 900;

    private const SESSION_KEY = 'payroll_doc_download_unlock';

    public static function grant(int $documentId): void
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][$documentId] = time() + self::TTL_SECONDS;
    }

    public static function isGranted(int $documentId): bool
    {
        $arr = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($arr) || !isset($arr[$documentId])) {
            return false;
        }
        $until = (int) $arr[$documentId];
        if (time() > $until) {
            unset($_SESSION[self::SESSION_KEY][$documentId]);

            return false;
        }

        return true;
    }
}
