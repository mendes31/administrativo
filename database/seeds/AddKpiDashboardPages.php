<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddKpiDashboardPages extends AbstractSeed
{
    public function run(): void
    {
        // Forçar charset/collation para evitar "Illegal mix of collations"
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $this->execute("SET collation_connection = 'utf8mb4_unicode_ci'");
        $this->execute("SET character_set_client = 'utf8mb4'");
        $this->execute("SET character_set_results = 'utf8mb4'");
        $this->execute("SET character_set_connection = 'utf8mb4'");
        
        // Verificar se o grupo já existe
        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Dashboards KPI'");
        
        if (!$group) {
            $this->table('adms_groups_pages')->insert([
                'name' => 'Dashboards KPI',
                'obs' => 'Dashboards personalizados com KPIs e indicadores'
            ])->save();
            
            $groupId = (int) $this->getAdapter()->getConnection()->lastInsertId();
        } else {
            $groupId = $group['id'];
        }

        // Páginas do módulo (estrutura correta conforme tabela adms_pages)
        $pages = [
            [
                'name' => 'Listar Dashboards KPI',
                'controller' => 'ListKpiDashboards',
                'controller_url' => 'list-kpi-dashboards',
                'directory' => 'dashboard',
                'obs' => 'Listar Dashboards KPI personalizados.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'Visualizar Dashboard KPI',
                'controller' => 'ViewKpiDashboard',
                'controller_url' => 'view-kpi-dashboard',
                'directory' => 'dashboard',
                'obs' => 'Visualizar Dashboard KPI específico.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'Criar Dashboard KPI',
                'controller' => 'CreateKpiDashboard',
                'controller_url' => 'create-kpi-dashboard',
                'directory' => 'dashboard',
                'obs' => 'Criar novo Dashboard KPI.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'Atualizar Dashboard KPI',
                'controller' => 'UpdateKpiDashboard',
                'controller_url' => 'update-kpi-dashboard',
                'directory' => 'dashboard',
                'obs' => 'Atualizar Dashboard KPI existente.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'Deletar Dashboard KPI',
                'controller' => 'DeleteKpiDashboard',
                'controller_url' => 'delete-kpi-dashboard',
                'directory' => 'dashboard',
                'obs' => 'Deletar Dashboard KPI.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'API - Dados Widget KPI',
                'controller' => 'GetKpiWidgetData',
                'controller_url' => 'get-kpi-widget-data',
                'directory' => 'dashboard',
                'obs' => 'API para buscar dados do widget KPI.',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ]
        ];

        $data = [];
        
        foreach ($pages as $page) {
            // Verificar se já existe pelo controller_url (mais seguro)
            $controllerUrl = $page['controller_url'];
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = '{$controllerUrl}'");
            
            if (!$exists) {
                $data[] = array_merge($page, [
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
        }

        // Inserir apenas se houver dados novos
        if (!empty($data)) {
            $this->table('adms_pages')->insert($data)->save();
        }
    }
}

