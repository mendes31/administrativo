<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * Separação entre relatórios locais (SQL) e SAP (API).
 */
final class DynamicReportScopeHelper
{
    public static function isSapReport(array $report): bool
    {
        if (!empty($report['data_source']) && $report['data_source'] === 'sap_b1') {
            return true;
        }

        if (!empty($report['custom_sql']) && is_string($report['custom_sql'])) {
            return DynamicQueryBuilderService::hasSapSignature($report['custom_sql']);
        }

        return false;
    }
}
