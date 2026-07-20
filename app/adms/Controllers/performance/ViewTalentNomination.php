<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\TalentNominationsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewTalentNomination
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $nid = (int) $id;
        if ($nid <= 0) {
            $_SESSION['error'] = 'Nomeação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-talent-nominations');
            exit;
        }

        $repository = new TalentNominationsRepository();
        $nomination = $repository->getById($nid);
        if (!$nomination) {
            $_SESSION['error'] = 'Nomeação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-talent-nominations');
            exit;
        }

        $this->data['nomination'] = $nomination;
        $returnUrl = $_ENV['URL_ADM'] . 'view-talent-nomination/' . $nid;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_talent_nominations', $nid, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Nomeação HiPo',
            'menu' => 'list-talent-nominations',
            'buttonPermission' => [
                'ListTalentNominations',
                'UpdateTalentNomination',
                'NineBoxMatrix',
                'ListPdiPlans',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/view_talent_nomination', $this->data);
        $loadView->loadView();
    }
}
