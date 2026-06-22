<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingPositionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class TrainingPositions
{
    private array|string|null $data = null;

    public function index(int $trainingId): void
    {
        $trainingsRepo = new TrainingsRepository();
        $training = $trainingsRepo->getTraining($trainingId);
        if (!$training) {
            $_SESSION['error'] = 'Treinamento não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }
        if (isset($training['is_current_version']) && (int)$training['is_current_version'] !== 1) {
            $_SESSION['error'] = 'Somente a versão atual pode alterar vínculos de cargos.';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-training/' . $trainingId);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveTrainingPositions($trainingId);
        }

        $this->loadTrainingPositionsData($trainingId);
        $this->loadView();
    }

    private function saveTrainingPositions(int $trainingId): void
    {
        $obrigatorio = $_POST['obrigatorio'] ?? [];
        $tipos = $_POST['tipo_treinamento'] ?? [];
        
        // Buscar vínculos existentes ANTES da alteração
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $existingLinks = $trainingPositionsRepo->getPositionsByTraining($trainingId);
        $existingObrigatorios = array_column($existingLinks, 'adms_position_id');
        
        $result = $trainingPositionsRepo->saveTrainingPositions($trainingId, $obrigatorio, $tipos);
        
        if ($result) {
            $_SESSION['success'] = 'Vínculos atualizados com sucesso!';
            
            // Identificar cargos que foram DESVINCULADOS (estavam obrigatórios e agora não estão)
            $cargosDesvinculados = array_diff($existingObrigatorios, array_keys($obrigatorio));
            
            // Remover vínculos de usuários dos cargos desvinculados
            if (!empty($cargosDesvinculados)) {
                $trainingUsersRepo = new \App\adms\Models\Repository\TrainingUsersRepository();
                foreach ($cargosDesvinculados as $cargoId) {
                    $trainingUsersRepo->removeActiveLinksByCargoAndTraining($cargoId, $trainingId);
                    
                    \App\adms\Helpers\GenerateLog::generateLog(
                        "info", 
                        "Cargo desvinculado do treinamento - vínculos removidos", 
                        [
                            'training_id' => $trainingId,
                            'position_id' => $cargoId,
                            'admin_user_id' => $_SESSION['user_id'] ?? 0,
                            'action' => 'remove_cargo_links'
                        ]
                    );
                }
            }
            
            $trainingUsersRepo = new \App\adms\Models\Repository\TrainingUsersRepository();
            $trainingUsersRepo->syncMandatoryCargoLinksForAllActiveUsers($trainingId);
            \App\adms\Models\Services\TrainingStatusUpdaterService::ensureUpdated(true);
        } else {
            $_SESSION['error'] = 'Erro ao atualizar vínculos!';
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'training-positions/' . $trainingId);
        exit;
    }

    private function loadTrainingPositionsData(int $trainingId): void
    {
        $trainingsRepo = new TrainingsRepository();
        $positionsRepo = new PositionsRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();

        $training = $trainingsRepo->getTraining($trainingId);
        
        if (!$training) {
            $_SESSION['error'] = 'Treinamento não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }

        $positions = $positionsRepo->getAllPositions(1, 1000);
        $linkedPositions = $trainingPositionsRepo->getPositionsByTraining($trainingId);
        $linkedPositionIds = array_column($linkedPositions, 'adms_position_id');

        $this->data['training'] = $training;
        $this->data['positions'] = $positions;
        $this->data['linkedPositions'] = $linkedPositions;
        $this->data['linkedPositionIds'] = $linkedPositionIds;
    }

    private function loadView(): void
    {
        $pageElements = [
            'title_head' => 'Vincular Cargos ao Treinamento',
            'menu' => 'list-trainings',
            'buttonPermission' => ['TrainingPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/trainingPositions', $this->data);
        $loadView->loadView();
    }
} 