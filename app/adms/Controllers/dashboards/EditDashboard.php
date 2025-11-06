<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class EditDashboard
{
    private array $data = [];

    public function index(?string $id = null): void
    {
        if (!$id) {
            $_SESSION['error'] = 'Dashboard não especificado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $dashboardId = (int)$id;
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        
        // Verificar acesso
        if (!$repo->canAccess($dashboardId, $userId)) {
            $_SESSION['error'] = 'Você não tem permissão para editar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $dashboard = $repo->getById($dashboardId);
        
        if (!$dashboard) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        // Verificar se é o criador (apenas criador pode editar)
        if ($dashboard['created_by'] != $userId) {
            $_SESSION['error'] = 'Apenas o criador do dashboard pode editá-lo!';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
            exit;
        }
        
        $this->data['dashboard'] = $dashboard;
        
        // Buscar todos os relatórios para seleção
        $reportsRepo = new DynamicReportsRepository();
        $this->data['reports'] = $reportsRepo->getUserReports($userId);
        
        $pageElements = [
            'title_head' => 'Editar Dashboard',
            'menu' => 'EditDashboard',
            'buttonPermission' => ['ListDashboards', 'ViewDashboard']
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/dashboards/edit', $this->data);
        $loadView->loadView();
    }
    
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $dashboardId = (int)($_POST['dashboard_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        
        // Verificar acesso
        $dashboard = $repo->getById($dashboardId);
        if (!$dashboard || $dashboard['created_by'] != $userId) {
            $_SESSION['error'] = 'Você não tem permissão para editar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        try {
            // Preparar dados
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? null,
                'category' => $_POST['category'] ?? null,
                'is_public' => isset($_POST['is_public']) ? 1 : 0,
                'measures_config' => json_decode($_POST['measures_config'] ?? '[]', true),
                'kpis_config' => json_decode($_POST['kpis_config'] ?? '[]', true),
                'charts_config' => json_decode($_POST['charts_config'] ?? '[]', true),
                'filters_config' => json_decode($_POST['filters_config'] ?? '[]', true),
                'layout' => $_POST['layout'] ?? 'default'
            ];
            
            // Validar
            if (empty($data['name'])) {
                throw new \Exception('Nome do dashboard é obrigatório!');
            }
            
            // Atualizar
            $success = $repo->update($dashboardId, $data);
            
            if ($success) {
                $_SESSION['success'] = 'Dashboard atualizado com sucesso!';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
            } else {
                throw new \Exception('Erro ao atualizar dashboard!');
            }
            
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . $_ENV['URL_ADM'] . 'edit-dashboard/' . $dashboardId);
        }
        
        exit;
    }
}

