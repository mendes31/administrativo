<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\DashboardsRepository;

/**
 * Duplicar dashboard existente para facilitar edição
 */
class DuplicateDashboard
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $dashboardId = (int)($_POST['dashboard_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        
        // Buscar dashboard original
        $original = $repo->getById($dashboardId);
        
        if (!$original) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        // Verificar acesso
        if (!$repo->canAccess($dashboardId, $userId)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        try {
            // Criar cópia com novo nome
            $data = [
                'name' => $original['name'] . ' (Cópia)',
                'description' => $original['description'],
                'dynamic_report_id' => $original['dynamic_report_id'],
                'created_by' => $userId,
                'is_public' => 0, // Cópia começa como privada
                'category' => $original['category'],
                'measures_config' => $original['measures_config'],
                'kpis_config' => $original['kpis_config'],
                'charts_config' => $original['charts_config'],
                'filters_config' => $original['filters_config'],
                'layout' => $original['layout']
            ];
            
            $newId = $repo->create($data);
            
            $_SESSION['success'] = 'Dashboard duplicado com sucesso! Agora você pode editá-lo.';
            
            // Redirecionar para criar dashboard com as configurações pré-carregadas
            header('Location: ' . $_ENV['URL_ADM'] . 'edit-dashboard/' . $newId);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao duplicar dashboard: ' . $e->getMessage();
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
        }
        
        exit;
    }
}

