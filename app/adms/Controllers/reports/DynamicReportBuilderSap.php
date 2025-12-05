<?php

namespace App\adms\Controllers\reports;

/**
 * Página exclusiva para criação/edição de relatórios SAP (API).
 * URL sugerida: dynamic-report-builder-sap
 */
class DynamicReportBuilderSap extends DynamicReportBuilder
{
    public function index(): void
    {
        // Força o destaque correto do menu para Relatórios SAP (API)
        $_SESSION['menu_override'] = 'ListDynamicReportsSap';
        $this->data['is_sap_scope'] = true;
        parent::index();
    }
}


