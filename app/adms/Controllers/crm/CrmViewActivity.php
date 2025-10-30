<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar Atividade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmViewActivity
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "Atividade não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-activities");
            exit;
        }

        $repository = new CrmActivitiesRepository();
        $activity = $repository->getActivityById((int)$id);

        if (!$activity) {
            $_SESSION['msg'] = "Atividade não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-activities");
            exit;
        }

        $this->data['activity'] = $activity;

        // Layout
        $pageElements = [
            'title_head' => 'Visualizar Atividade - CRM',
            'menu' => 'crm-list-activities',
            'buttonPermission' => ['CrmViewActivity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/activities/view", $this->data);
        $loadView->loadView();
    }
}

