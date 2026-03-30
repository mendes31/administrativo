<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class EditDashboard
{
    private array $data = [];
    
    /**
     * Verificar se usuário tem acesso total (super admin)
     */
    private function hasFullAccess(): bool
    {
        // Super administrador (nível 1) tem acesso total
        return \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
    }

    public function index(?string $id = null): void
    {
        // Ao entrar em qualquer página de Dashboard, limpar overrides de outros menus
        if (isset($_SESSION['menu_override'])) {
            unset($_SESSION['menu_override']);
        }
        // Se for POST, processar atualização
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update();
            return;
        }
        
        // Se não for POST, carregar view de edição
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
        
        // Verificar permissão de edição (seguindo padrão do projeto)
        $isCreator = $dashboard['created_by'] == $userId;
        
        if (!$this->hasFullAccess() && !$isCreator) {
            $_SESSION['error'] = 'Apenas o criador do dashboard ou super administrador pode editá-lo!';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
            exit;
        }
        
        $this->data['dashboard'] = $dashboard;
        
        // Buscar todos os relatórios para seleção
        $reportsRepo = new DynamicReportsRepository();
        $this->data['reports'] = $reportsRepo->getUserReports($userId);
        
        $pageElements = [
            'title_head' => 'Editar Dashboard',
            // Mantém o menu principal de Dashboards em destaque
            'menu' => 'ListDashboards',
            'buttonPermission' => ['ListDashboards', 'ViewDashboard']
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/dashboards/edit', $this->data);
        $loadView->loadView();
    }
    
    private function update(): void
    {
        // Log COMPLETO do POST recebido
        error_log("========================================");
        error_log("🔍 EditDashboard::update() - POST RECEBIDO:");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST Keys: " . implode(', ', array_keys($_POST)));
        error_log("dashboard_id RAW: " . var_export($_POST['dashboard_id'] ?? 'NÃO ENVIADO', true));
        error_log("name: " . ($_POST['name'] ?? 'NÃO ENVIADO'));
        error_log("report_ids: " . ($_POST['report_ids'] ?? 'NÃO ENVIADO'));
        error_log("========================================");
        
        $dashboardId = (int)($_POST['dashboard_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        
        error_log("📊 dashboard_id convertido para INT: {$dashboardId}");
        
        $repo = new DashboardsRepository();
        
        // Verificar acesso
        $dashboard = $repo->getById($dashboardId);
        
        if (!$dashboard) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        // Verificar permissão (seguindo padrão do projeto)
        $isCreator = $dashboard['created_by'] == $userId;
        
        if (!$this->hasFullAccess() && !$isCreator) {
            error_log("❌ Tentativa de edição sem permissão - User: {$userId}, Dashboard: {$dashboardId}");
            $_SESSION['error'] = 'Você não tem permissão para editar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        try {
            // Preparar dados
            $measuresConfig = json_decode($_POST['measures_config'] ?? '[]', true);
            $kpisConfig = json_decode($_POST['kpis_config'] ?? '[]', true);
            $chartsConfig = json_decode($_POST['charts_config'] ?? '[]', true);
            $filtersConfig = json_decode($_POST['filters_config'] ?? '[]', true);
            $relationshipsConfig = json_decode($_POST['relationships'] ?? '[]', true);

            $measuresConfig = is_array($measuresConfig) ? $measuresConfig : [];
            $kpisConfig = is_array($kpisConfig) ? $kpisConfig : [];
            $chartsConfig = is_array($chartsConfig) ? $chartsConfig : [];
            $filtersConfig = is_array($filtersConfig) ? $filtersConfig : [];
            $relationshipsConfig = is_array($relationshipsConfig) ? $relationshipsConfig : [];
            
            error_log("📝 Dados recebidos do POST:");
            error_log("   measures_config: " . count($measuresConfig) . " medidas (Bytes: " . strlen($_POST['measures_config'] ?? '') . ")");
            error_log("   kpis_config: " . count($kpisConfig) . " kpis (Bytes: " . strlen($_POST['kpis_config'] ?? '') . ")");
            error_log("   filters_config: " . count($filtersConfig) . " filtros (Bytes: " . strlen($_POST['filters_config'] ?? '') . ")");
            error_log("   charts_config: " . count($chartsConfig) . " gráficos (Bytes: " . strlen($_POST['charts_config'] ?? '') . ")");
            
            error_log("📊 Dados existentes no banco:");
            error_log("   measures_config: " . count($dashboard['measures_config']) . " medidas");
            error_log("   kpis_config: " . count($dashboard['kpis_config']) . " kpis");
            error_log("   filters_config: " . count($dashboard['filters_config']) . " filtros");
            error_log("   charts_config: " . count($dashboard['charts_config']) . " gráficos");
            
            // PROTEÇÃO INTELIGENTE: Só proteger se receber vazio E tiver dados no banco E o campo no POST for realmente vazio (2 bytes = "[]")
            $protectionApplied = false;
            
            if (empty($measuresConfig) && !empty($dashboard['measures_config']) && strlen($_POST['measures_config'] ?? '') <= 2) {
                error_log("⚠️ PROTEÇÃO measures_config: mantendo " . count($dashboard['measures_config']) . " medidas existentes");
                $measuresConfig = $dashboard['measures_config'];
                $protectionApplied = true;
            }
            if (empty($kpisConfig) && !empty($dashboard['kpis_config']) && strlen($_POST['kpis_config'] ?? '') <= 2) {
                error_log("⚠️ PROTEÇÃO kpis_config: mantendo " . count($dashboard['kpis_config']) . " kpis existentes");
                $kpisConfig = $dashboard['kpis_config'];
                $protectionApplied = true;
            }
            if (empty($chartsConfig) && !empty($dashboard['charts_config']) && strlen($_POST['charts_config'] ?? '') <= 2) {
                error_log("⚠️ PROTEÇÃO charts_config: mantendo " . count($dashboard['charts_config']) . " gráficos existentes");
                $chartsConfig = $dashboard['charts_config'];
                $protectionApplied = true;
            }
            // FILTERS: NÃO aplicar proteção, sempre aceitar o que vier do POST
            // (permite edição dos filtros)
            
            if ($protectionApplied) {
                error_log("🛡️ Proteção ativada - dados preservados");
            } else {
                error_log("✅ Nenhuma proteção necessária - salvando dados do POST");
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? null,
                'category' => $_POST['category'] ?? null,
                'is_public' => isset($_POST['is_public']) ? 1 : 0,
                'measures_config' => $measuresConfig,
                'kpis_config' => $kpisConfig,
                'charts_config' => $chartsConfig,
                'filters_config' => $filtersConfig,
                'layout' => $_POST['layout'] ?? 'default',
                'relationships' => $relationshipsConfig
            ];
            
            // Validar
            if (empty($data['name'])) {
                throw new \Exception('Nome do dashboard é obrigatório!');
            }
            
            // Atualizar relatórios vinculados (se fornecido)
            if (isset($_POST['report_ids'])) {
                $reportIds = json_decode($_POST['report_ids'], true);
                if (is_array($reportIds) && !empty($reportIds)) {
                    $repo->updateReports($dashboardId, $reportIds);
                    error_log("✅ Relatórios do dashboard {$dashboardId} atualizados: " . implode(', ', $reportIds));
                }
            }
            
            // Atualizar dashboard
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

