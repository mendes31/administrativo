<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Redirecionamentos do hub de relacionamentos no cadastro de risco.
 */
final class SstRiscoNavigationHelper
{
    public static function viewUrl(int $riscoId, string $tab = ''): string
    {
        $url = ($_ENV['URL_ADM'] ?? '') . 'sst-view-risco/' . $riscoId;
        if ($tab !== '') {
            $url .= '#tab-' . $tab;
        }

        return $url;
    }

    public static function riscoIdFromRequest(): int
    {
        return (int) (
            $_POST['return_risco_id']
            ?? $_GET['adms_sst_risco_id']
            ?? $_GET['risco_id']
            ?? 0
        );
    }

    public static function redirectAfterMutation(?int $riscoId, string $tab, string $fallback = 'sst-list-riscos'): void
    {
        if ($riscoId > 0) {
            header('Location: ' . self::viewUrl($riscoId, $tab));
        } else {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . $fallback);
        }
        exit;
    }

    public static function redirectListToHub(): void
    {
        $riscoId = (int) ($_GET['adms_sst_risco_id'] ?? 0);
        if ($riscoId > 0) {
            $tab = match (true) {
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'risco-cargo') => 'cargos',
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'risco-exame') => 'exames',
                str_contains($_SERVER['REQUEST_URI'] ?? '', 'risco-epi') => 'epis',
                default => '',
            };
            header('Location: ' . self::viewUrl($riscoId, $tab));
            exit;
        }
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'sst-list-riscos');
        exit;
    }
}
