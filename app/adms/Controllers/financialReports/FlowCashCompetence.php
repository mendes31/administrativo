<?php
namespace App\adms\Controllers\financialReports;

use App\adms\Models\Repository\FlowCashCompetenceRepository;
use App\adms\Views\Services\LoadViewService;

class FlowCashCompetence
{
    private array|string|null $data = null;

    public function index(): void
    {
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: date('Y');
        $export = $_GET['export'] ?? null;
        $repo = new FlowCashCompetenceRepository();
        $this->data['year'] = $year;
        $this->data['months'] = [
            'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'
        ];
        $this->data['cashFlow'] = $repo->getCashFlow($year);
        $this->data['competence'] = $repo->getCompetence($year);

        if ($export === 'excel') {
            $repo->exportToExcel($year, $this->data['months'], $this->data['cashFlow'], $this->data['competence']);
        } elseif ($export === 'pdf') {
            $repo->exportToPDF($year, $this->data['months'], $this->data['cashFlow'], $this->data['competence']);
        }

        $pageElements = [
            'title_head' => 'Fluxo de Caixa por Competência',
            'menu' => 'flow-cash-competence',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new \App\adms\Controllers\Services\PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/financialReports/flowCashCompetence', $this->data);
        $loadView->loadView();
    }
} 