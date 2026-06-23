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
        $this->data['is_sap_scope'] = true;
        parent::index();
    }
}


