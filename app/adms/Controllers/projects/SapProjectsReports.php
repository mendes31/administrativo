<?php

declare(strict_types=1);

namespace App\adms\Controllers\projects;

/**
 * Relatórios SAP de Projetos: mesma tela do dashboard, aberta na aba Relatórios.
 */
class SapProjectsReports extends SapProjectsDashboard
{
    public function index(): void
    {
        $this->render('relatorios', 'sap-projects-reports');
    }
}
