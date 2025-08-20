<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ViewTrainingHistory
{
    private array $data = [];

    public function index(): void
    {
        $training_id = $_GET['training_id'] ?? null;
        $user_id = $_GET['user_id'] ?? null;
        if (!$training_id || !$user_id) {
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            exit;
        }
        $trainingsRepo = new TrainingsRepository();
        $usersRepo = new UsersRepository();
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingApplicationsRepo = new TrainingApplicationsRepository();
        $this->data['training'] = $trainingsRepo->getTraining($training_id);
        $this->data['user'] = $usersRepo->getUser($user_id);
        $this->data['history'] = $trainingApplicationsRepo->getHistory($user_id, $training_id);
        $pageElements = [
            'title_head' => 'Histórico de Aplicações',
            'menu' => 'training-history',
            'buttonPermission' => ['ViewTrainingHistory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/trainings/trainingHistory', $this->data);
        $loadView->loadView();
    }
} 