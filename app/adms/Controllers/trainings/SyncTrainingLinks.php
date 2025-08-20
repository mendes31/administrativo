<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class SyncTrainingLinks
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->sync();
            return;
        }

        $usersRepo = new UsersRepository();
        $positionsRepo = new PositionsRepository();

        $this->data = [
            'users' => $usersRepo->getAllUsersSelect(),
            'positions' => $positionsRepo->getAllPositionsSelect(),
        ];

        // Elementos de página
        $pageElements = [
            'title_head' => 'Sincronizar Vínculos de Treinamentos',
            'menu' => 'sync-training-links',
            'buttonPermission' => ['SyncTrainingLinks'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/syncTrainingLinks', $this->data);
        $loadView->loadView();
    }

    private function sync(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $positionId = (int) ($_POST['position_id'] ?? 0);

        if (!$userId || !$positionId) {
            $_SESSION['msg'] = 'Selecione um colaborador e um cargo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sync-training-links');
            exit;
        }

        $trainingUsersRepo = new TrainingUsersRepository();
        $success = $trainingUsersRepo->syncUserTrainingLinks($userId, $positionId);

        if ($success) {
            $_SESSION['msg'] = 'Vínculos sincronizados com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao sincronizar vínculos.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sync-training-links');
        exit;
    }
} 