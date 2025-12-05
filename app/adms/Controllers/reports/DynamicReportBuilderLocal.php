<?php

namespace App\adms\Controllers\reports;

/**
 * Página exclusiva para criação/edição de relatórios LOCais.
 * URL sugerida: dynamic-report-builder-local
 */
class DynamicReportBuilderLocal extends DynamicReportBuilder
{
    public function index(): void
    {
        // Força o destaque correto do menu para Relatórios Locais
        $_SESSION['menu_override'] = 'ListDynamicReports';
        $this->data['is_sap_scope'] = false;
        parent::index();
    }
}


