<?php

use Phinx\Seed\AbstractSeed;

class AddKpiDashboardPages extends AbstractSeed
{
    public function run(): void
    {
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

        // Páginas do módulo
        $pages = [
            [
                'name' => 'list-kpi-dashboards',
                'adms_groups_page_id' => $groupId,
                'type' => 1,
                'obs' => 'Listar Dashboards KPI',
                'publish' => 1
            ],
            [
                'name' => 'view-kpi-dashboard',
                'adms_groups_page_id' => $groupId,
                'type' => 1,
                'obs' => 'Visualizar Dashboard KPI',
                'publish' => 1
            ],
            [
                'name' => 'create-kpi-dashboard',
                'adms_groups_page_id' => $groupId,
                'type' => 1,
                'obs' => 'Criar Dashboard KPI',
                'publish' => 1
            ],
            [
                'name' => 'update-kpi-dashboard',
                'adms_groups_page_id' => $groupId,
                'type' => 2,
                'obs' => 'Atualizar Dashboard KPI',
                'publish' => 1
            ],
            [
                'name' => 'delete-kpi-dashboard',
                'adms_groups_page_id' => $groupId,
                'type' => 2,
                'obs' => 'Deletar Dashboard KPI',
                'publish' => 1
            ],
            [
                'name' => 'get-kpi-widget-data',
                'adms_groups_page_id' => $groupId,
                'type' => 2,
                'obs' => 'API: Buscar dados do widget KPI',
                'publish' => 1
            ]
        ];

        foreach ($pages as $page) {
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE name = '{$page['name']}'");
            
            if (!$exists) {
                $this->table('adms_pages')->insert($page)->save();
            }
        }
    }
}

