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

        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['training_status_filters']);
            // Redirecionar para a página sem parâmetros
            header('Location: ' . $_ENV['URL_ADM'] . 'list-training-status');
            exit;
        }

        // Verificar se há filtros na URL
        $hasUrlFilters = !empty($_GET['colaborador']) || !empty($_GET['departamento']) || 
                         !empty($_GET['cargo']) || !empty($_GET['treinamento']) || 
                         !empty($_GET['status']) || !empty($_GET['codigo']) ||
                         !empty($_GET['area_responsavel_id']) || !empty($_GET['area_elaborador_id']);

        // Se há filtros na URL, salvá-los na sessão
        if ($hasUrlFilters) {
            $filters = [
                'colaborador' => $_GET['colaborador'] ?? null,
                'departamento' => $_GET['departamento'] ?? null,
                'cargo' => $_GET['cargo'] ?? null,
                'treinamento' => $_GET['treinamento'] ?? null,
                'status' => $_GET['status'] ?? '',
                'codigo' => $_GET['codigo'] ?? null,
                'area_responsavel_id' => $_GET['area_responsavel_id'] ?? null,
                'area_elaborador_id' => $_GET['area_elaborador_id'] ?? null,
            ];
            $_SESSION['training_status_filters'] = $filters;
        } 
        // Se não há filtros na URL, usar os da sessão (se existirem)
        elseif (isset($_SESSION['training_status_filters'])) {
            $filters = $_SESSION['training_status_filters'];
        } 
        // Se não há filtros em nenhum lugar, usar valores vazios
        else {
            $filters = [
                'colaborador' => null,
                'departamento' => null,
                'cargo' => null,
                'treinamento' => null,
                'status' => '',
                'codigo' => null,
                'area_responsavel_id' => null,
                'area_elaborador_id' => null,
            ];
        }

        $statusFiltro = $filters['status'] ?? '';

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