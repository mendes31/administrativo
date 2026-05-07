<?php

declare(strict_types=1);

namespace App\adms\Controllers\trainings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Views\Services\LoadViewService;

class TrainingVersionAudit
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'family' => trim((string)($_GET['family'] ?? '')),
            'codigo' => trim((string)($_GET['codigo'] ?? '')),
        ];

        if (isset($_GET['limpar'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'training-version-audit');
            exit;
        }

        $repo = new TrainingsRepository();
        $rows = $repo->getVersionAuditRows($filters);
        $alerts = $repo->getVersionAuditAlerts($filters);

        $this->data['filters'] = $filters;
        $this->data['rows'] = $rows;
        $this->data['alerts'] = $alerts;
        $this->data['alertCounts'] = [
            'families_with_invalid_current_count' => count($alerts['families_with_invalid_current_count'] ?? []),
            'families_with_invalid_active_count' => count($alerts['families_with_invalid_active_count'] ?? []),
            'current_versions_not_active' => count($alerts['current_versions_not_active'] ?? []),
        ];

        $pageElements = [
            'title_head' => 'Auditoria de Versões de Treinamento',
            'menu' => 'training-version-audit',
            'buttonPermission' => [
                'ListTrainings',
                'ListTrainingStatus',
                'TrainingVersionAudit',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/trainingVersionAudit', $this->data);
        $loadView->loadView();
    }
}

