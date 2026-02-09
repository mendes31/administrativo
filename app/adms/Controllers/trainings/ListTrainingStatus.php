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
use App\adms\Models\Services\TrainingStatusUpdaterService;

class ListTrainingStatus
{
    private array $data = [];

    public function index(): void
    {
        // Atualizar status dinâmicos automaticamente (se necessário)
        // Executa apenas se passou mais de 15 minutos desde a última atualização
        TrainingStatusUpdaterService::ensureUpdated();
        
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
        
        // Paginação
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        // Usar configuração responsiva + parâmetro per_page (10, 20, 50, 100)
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'] ?? [10, 20, 50, 100])) {
            $perPage = (int) $_GET['per_page'];
        } else {
            $perPage = $paginationSettings['per_page'] ?? 50;
        }
        
        // Buscar dados com paginação (OTIMIZADO - resolve N+1 e adiciona paginação)
        $matrixResult = $trainingUsersRepo->getTrainingStatusByUser($filters, $page, $perPage);
        
        // Dados para a view
        $this->data = [
            'filters' => $filters,
            'matrix' => $matrixResult['data'],
            'pagination' => [
                'total' => $matrixResult['total'],
                'total_pages' => $matrixResult['total_pages'],
                'current_page' => $matrixResult['current_page'],
                'per_page' => $matrixResult['per_page'],
            ],
            'summary' => $trainingUsersRepo->getSummaryAll(),
            'expiring' => $trainingUsersRepo->getExpiringTrainings(30),
            'listDepartments' => $departmentsRepo->getAllDepartmentsSelect(),
            'listPositions' => $positionsRepo->getAllPositionsSelect(),
            'listTrainings' => $trainingsRepo->getAllTrainingsSelect(),
            'listUsers' => $usersRepo->getAllUsersSelect(),
        ];
        // Contagem dinâmica dos status para os cards (usar summary otimizado)
        $summary = $trainingUsersRepo->getSummaryAll();
        $statusCounts = [
            'dentro_do_prazo' => $summary['dentro_do_prazo'] ?? 0,
            'proximo_vencimento' => $summary['proximo_vencimento'] ?? 0,
            'vencido' => $summary['vencido'] ?? 0,
            'agendado' => $summary['agendado'] ?? 0,
            'concluido' => $summary['concluido'] ?? 0,
            'todos' => $summary['todos'] ?? 0,
        ];
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