<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\ScreenResolutionHelper;

class ListTrainingStatus
{
    private array $data = [];

    public function index(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $trainingUsersRepo = new TrainingUsersRepository();
        $usersRepo = new UsersRepository();
        $departmentsRepo = new DepartmentsRepository();
        $positionsRepo = new PositionsRepository();
        $trainingsRepo = new TrainingsRepository();

        // Filtros da URL
        $statusFiltro = $_GET['status'] ?? '';
        $filters = [
            'colaborador' => $_GET['colaborador'] ?? null,
            'departamento' => $_GET['departamento'] ?? null,
            'cargo' => $_GET['cargo'] ?? null,
            'treinamento' => $_GET['treinamento'] ?? null,
            'status' => $statusFiltro,
        ];

        // Dados para a view
        $this->data = [
            'filters' => $filters,
            'matrix' => $trainingUsersRepo->getTrainingStatusByUser($filters),
            'summary' => $trainingUsersRepo->getSummaryAll(),
            'expiring' => $trainingUsersRepo->getExpiringTrainings(30),
            'listDepartments' => $departmentsRepo->getAllDepartmentsSelect(),
            'listPositions' => $positionsRepo->getAllPositionsSelect(),
            'listTrainings' => $trainingsRepo->getAllTrainingsSelect(),
            'listUsers' => $usersRepo->getAllUsersSelect(),
        ];

        // Filtragem de status no backend
        $matrix = $trainingUsersRepo->getTrainingStatusByUser($filters);
        if ($statusFiltro === '') {
            // Todos exceto concluído
            $matrix = array_filter($matrix, function($row) {
                $status = $row['status_dinamico'] ?? $row['status'] ?? '';
                return $status !== 'concluido';
            });
        } elseif ($statusFiltro === 'concluido') {
            $matrix = array_filter($matrix, function($row) {
                $status = $row['status_dinamico'] ?? $row['status'] ?? '';
                return $status === 'concluido';
            });
        } elseif ($statusFiltro) {
            $matrix = array_filter($matrix, function($row) use ($statusFiltro) {
                $status = $row['status_dinamico'] ?? $row['status'] ?? '';
                return $status === $statusFiltro;
            });
        }
        $this->data['matrix'] = $matrix;
        // Contagem dinâmica dos status para os cards
        $statusCounts = [
            'dentro_do_prazo' => 0,
            'proximo_vencimento' => 0,
            'vencido' => 0,
            'agendado' => 0,
            'concluido' => 0,
        ];
        foreach ($matrix as $row) {
            $status = $row['status_dinamico'] ?? $row['status'] ?? '';
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }
        $statusCounts['todos'] = $statusCounts['dentro_do_prazo'] + $statusCounts['proximo_vencimento'] + $statusCounts['vencido'] + $statusCounts['agendado'];
        $this->data['statusCounts'] = $statusCounts;

        // Elementos de página
        $pageElements = [
            'title_head' => 'Matriz de Treinamentos',
            'menu' => 'list-training-status',
            'buttonPermission' => ['ListTrainingStatus'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar configurações responsivas
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;

        $loadView = new LoadViewService('adms/Views/trainings/listTrainingStatus', $this->data);
        $loadView->loadView();
    }
} 