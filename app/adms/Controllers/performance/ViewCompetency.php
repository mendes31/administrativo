<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar competência
 */
class ViewCompetency
{
    private array|string|null $data = null;

    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: ID da competência não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-competencies');
            exit;
        }

        $repository = new CompetenciesRepository();
        $competency = $repository->getById((int)$id);

        if (!$competency) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Competência não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-competencies');
            exit;
        }

        $this->data['competency'] = $competency;

        $cid = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'view-competency/' . $cid;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_competencies', $cid, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Competência',
            'menu' => 'view-competency',
            'buttonPermission' => [
                'ListCompetencies',
                'UpdateCompetency',
                'DeleteCompetency',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/view_competency', $this->data);
        $loadView->loadView();
    }
}

