<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;

class TrainingHistory
{
    private TrainingApplicationsRepository $applicationsRepo;
    private TrainingUsersRepository $trainingUsersRepo;
    private UsersRepository $usersRepo;
    private TrainingsRepository $trainingsRepo;

    public function __construct()
    {
        $this->applicationsRepo = new TrainingApplicationsRepository();
        $this->trainingUsersRepo = new TrainingUsersRepository();
        $this->usersRepo = new UsersRepository();
        $this->trainingsRepo = new TrainingsRepository();
    }

    public function index($urlParameter = null): void
    {
        // Espera parâmetro no formato 'userId-trainingId', ex: '4-2'
        $userId = 0;
        $trainingId = 0;
        if ($urlParameter && preg_match('/^(\d+)-(\d+)$/', $urlParameter, $matches)) {
            $userId = (int)$matches[1];
            $trainingId = (int)$matches[2];
        }

        if (!$userId || !$trainingId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $user = $this->usersRepo->getUser($userId);
        $training = $this->trainingsRepo->getTraining($trainingId);

        if (!$user || !$training) {
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $history = $this->applicationsRepo->getHistory($userId, $trainingId);

        $data = [
            'title_head' => 'Histórico de Reciclagem/Aplicações',
            'user' => $user,
            'training' => $training,
            'history' => $history,
        ];

        $pageLayout = new PageLayoutService();
        $data = $pageLayout->configurePageElements($data);

        $loadView = new \App\adms\Views\Services\LoadViewService('adms/Views/trainings/trainingHistory', $data);
        $loadView->loadView();
    }
} 