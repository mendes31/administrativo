<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DashboardsRepository;

class DeleteDashboard
{
    /**
     * Verificar se usuário tem acesso total (super admin)
     */
    private function hasFullAccess(): bool
    {
        // Super administrador (nível 1) tem acesso total
        return \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
    }
    
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        // Validar CSRF com token único para este dashboard
        $csrfToken = $_POST['csrf_token'] ?? '';
        $tokenName = 'form_delete_dashboard_' . $id;
        $isValid = CSRFHelper::validateCSRFToken($tokenName, $csrfToken);  // Ordem correta!
        
        if (!$isValid) {
            error_log("❌ CSRF inválido - Dashboard ID: {$id}, Token: " . substr($csrfToken, 0, 20) . "...");
            
            $_SESSION['error'] = 'Token de segurança inválido ou expirado! Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        error_log("✅ CSRF válido - Deletando dashboard {$id}");
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        $dashboard = $repo->getById($id);
        
        if (!$dashboard) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        // Verificar permissão (seguindo padrão do projeto)
        $isCreator = $dashboard['created_by'] == $userId;
        
        if (!$this->hasFullAccess() && !$isCreator) {
            error_log("❌ Tentativa de deletar dashboard sem permissão - User: {$userId}, Dashboard: {$id}");
            $_SESSION['error'] = 'Você não tem permissão para deletar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        if ($repo->delete($id)) {
            $_SESSION['success'] = 'Dashboard deletado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao deletar dashboard!';
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
        exit;
    }
}

