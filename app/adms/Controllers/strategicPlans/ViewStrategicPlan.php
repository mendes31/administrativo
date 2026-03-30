<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ViewStrategicPlan
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicPlansRepository();
    }

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

        $plan = $this->repository->getById($id);
        if (!$plan) {
            $_SESSION['msg'] = "Plano estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        // Verificar se o usuário tem permissão para visualizar este plano
        if (!$this->hasFullAccess()) {
            $userDepartmentId = $_SESSION['user_department_id'] ?? null;
            if ($userDepartmentId && $plan['department_id'] != $userDepartmentId) {
                $_SESSION['msg'] = "Você não tem permissão para visualizar este plano!";
                $_SESSION['msg_type'] = "danger";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
                exit;
            }
        }

        $this->viewPlan($plan);
    }

    private function viewPlan($plan): void
    {
        // Inicializar $this->data como array vazio se for null
        if ($this->data === null) {
            $this->data = [];
        }

        // Definir o título da página e configurações
        $pageElements = [
            'title_head' => 'Visualizar Plano Estratégico',
            'menu' => 'list-strategic-plans',
            'buttonPermission' => ['ListStrategicPlans'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados do plano
        $this->data['plan'] = $plan;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicPlans/view-strategic-plan", $this->data);
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
