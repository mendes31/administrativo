<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;
use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class StrategicIndicatorsCreate
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicIndicatorsRepository();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $this->viewCreate();
    }

    private function create(): void
    {
        $data = [
            'strategic_plan_id' => $_POST['strategic_plan_id'] ?? null,
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'target_value' => $_POST['target_value'] ?? '',
            'current_value' => $_POST['current_value'] ?? '',
            'unit' => $_POST['unit'] ?? '',
            'frequency' => $_POST['frequency'] ?? '',
            'responsible_id' => $_POST['responsible_id'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
            'created_by' => $_SESSION['user_id'] ?? 1
        ];

        try {
            $this->repository->create($data);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Indicador criado com sucesso!</div>';
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar indicador: ' . $e->getMessage() . '</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'strategic-indicators-list');
        exit;
    }

    private function viewCreate(): void
    {
        // Obter planos estratégicos para o select
        $plansRepository = new StrategicPlansRepository();
        $plans = $plansRepository->getAllStrategicPlans([], 1, 1000); // Buscar todos os planos
        
        // Obter usuários para o select
        $usersRepository = new UsersRepository();
        $users = $usersRepository->getAllUsers();

        // Elementos de página
        $pageElements = [
            'title_head' => 'Criar Indicador Estratégico',
            'menu' => 'strategic-indicators-create',
            'buttonPermission' => ['StrategicIndicatorsCreate'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['plans'] = $plans;
        $this->data['users'] = $users;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicIndicators/strategic-indicators-create", $this->data);
        $loadView->loadView();
    }
} 