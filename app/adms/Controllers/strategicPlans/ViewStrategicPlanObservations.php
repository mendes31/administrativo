<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Models\Repository\StrategicPlanObservationsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ViewStrategicPlanObservations
{
    private StrategicPlansRepository $plansRepo;
    private StrategicPlanObservationsRepository $observationsRepo;
    private array $data = [];

    public function __construct()
    {
        $this->plansRepo = new StrategicPlansRepository();
        $this->observationsRepo = new StrategicPlanObservationsRepository();
    }

    /**
     * Exibir observações de um plano estratégico
     */
    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do plano não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        // Buscar dados do plano
        $plan = $this->plansRepo->getById($id);
        if (!$plan) {
            $_SESSION['msg'] = "Plano estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        // Verificar se o usuário tem permissão para visualizar observações deste plano
        if (!$this->hasFullAccess()) {
            $userDepartmentId = $_SESSION['user_department_id'] ?? null;
            if ($userDepartmentId && $plan['department_id'] != $userDepartmentId) {
                $_SESSION['msg'] = "Você não tem permissão para visualizar observações deste plano!";
                $_SESSION['msg_type'] = "danger";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
                exit;
            }
        }

        // Buscar observações do plano
        $observations = $this->observationsRepo->getByStrategicPlanId($id);

        // Preparar dados para a view
        $this->data['plan'] = $plan;
        $this->data['observations'] = $observations;

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Observações - ' . $plan['title'],
            'menu' => 'list-strategic-plans',
            'buttonPermission' => ['ViewStrategicPlan'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a view
        $loadView = new LoadViewService("adms/Views/strategicPlans/observations", $this->data);
        $loadView->loadView();
    }

    /**
     * Verifica se o usuário tem acesso total (super admin ou departamento Diretoria)
     */
    private function hasFullAccess(): bool
    {
        // Super administrador (nível 1) tem acesso total
        if (\App\adms\Helpers\UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        // Usuários do departamento "Diretoria" também têm acesso total
        if (isset($_SESSION['user_department']) && $_SESSION['user_department'] === 'Diretoria') {
            return true;
        }

        return false;
    }
}
