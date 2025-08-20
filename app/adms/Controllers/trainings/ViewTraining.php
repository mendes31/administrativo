<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\TrainingPositionsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\ScreenResolutionHelper;

class ViewTraining
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->loadTrainingData($id);
        $this->loadView();
    }

    private function loadTrainingData(int|string $id): void
    {
        $trainingsRepo = new TrainingsRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $trainingUsersRepo = new TrainingUsersRepository();
        $positionsRepo = new PositionsRepository();

        // Depuração: mostrar o valor do ID recebido
        // var_dump('ID recebido:', $id);
        $training = $trainingsRepo->getTraining($id);
        // Depuração: mostrar o resultado da consulta
        //var_dump('Resultado getTraining:', $training); exit;
        
        if (!$training) {
            $_SESSION['error'] = 'Treinamento não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }

        // Buscar cargos vinculados
        $linkedPositions = $trainingPositionsRepo->getPositionsByTraining($id);
        $positionIds = array_column($linkedPositions, 'adms_position_id');
        $positions = [];
        
        if (!empty($positionIds)) {
            $allPositions = $positionsRepo->getAllPositions(1, 1000);
            foreach ($allPositions as $position) {
                if (in_array($position['id'], $positionIds)) {
                    $positions[] = $position;
                }
            }
        }

        // Buscar estatísticas de usuários
        $userStats = $trainingUsersRepo->getTrainingUserStats($id);

        // Buscar estatísticas de vínculos
        $positionStats = $trainingPositionsRepo->getTrainingPositionsStats($id);

        $this->data['training'] = $training;
        $this->data['linkedPositions'] = $linkedPositions;
        $this->data['positions'] = $positions;
        $this->data['userStats'] = $userStats;
        $this->data['positionStats'] = $positionStats;

        // Registrar visualização
        GenerateLog::generateLog("info", "Visualizado treinamento", ['id' => $id, 'nome' => $training['nome']]);
    }

    private function loadView(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $pageElements = [
            'title_head' => 'Visualizar Treinamento',
            'menu' => 'list-trainings',
            'buttonPermission' => ['ListTrainings', 'UpdateTraining', 'DeleteTraining', 'TrainingPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar configurações responsivas
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;

        $loadView = new LoadViewService('adms/Views/trainings/view', $this->data);
        $loadView->loadView();
    }
} 