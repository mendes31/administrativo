<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class TrainingMatrixManager
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->loadMatrixData();
        $this->loadView();
    }

    private function loadMatrixData(): void
    {
        $usersRepo = new UsersRepository();
        $positionsRepo = new PositionsRepository();
        $trainingsRepo = new TrainingsRepository();
        $trainingUsersRepo = new TrainingUsersRepository();

        $summary = $trainingUsersRepo->getSummaryAll();

        $this->data['stats'] = [
            'total_users' => $usersRepo->getTotalUsers(),
            'total_positions' => $positionsRepo->getAmountPositions(),
            'total_trainings' => $trainingsRepo->getTotalTrainings(),
            'total_matrix_entries' => (int)($summary['todos'] ?? $trainingUsersRepo->getTotalMatrixEntries()),
        ];

        $this->data['summary'] = $summary;
    }

    private function loadView(): void
    {
        $pageElements = [
            'title_head' => 'Visão da Matriz de Treinamentos',
            'menu' => 'list-trainings',
            'buttonPermission' => ['TrainingMatrixManager'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/matrixManager', $this->data);
        $loadView->loadView();
    }
}
