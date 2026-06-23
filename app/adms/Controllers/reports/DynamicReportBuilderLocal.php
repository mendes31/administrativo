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
        $this->data['is_sap_scope'] = false;
        parent::index();
    }
}


