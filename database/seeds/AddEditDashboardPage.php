<?php

use Phinx\Seed\AbstractSeed;

class AddEditDashboardPage extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se a página já existe
        $existing = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'edit-dashboard'");
        
        if ($existing) {
            echo "ℹ️  Página 'EditDashboard' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Buscar IDs necessários
        $package = $this->fetchRow("SELECT id FROM adms_packages_pages WHERE name = 'adms'");
        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Dashboards KPI'");
        
        if (!$package || !$group) {
            echo "⚠️  Pacote 'adms' ou Grupo 'Dashboards KPI' não encontrado\n";
            return;
        }
        
        // Inserir a página
        $page = [
            'name' => 'EditDashboard',
            'controller' => 'EditDashboard',
            'controller_url' => 'edit-dashboard',
            'directory' => 'dashboards',
            'obs' => 'Editar dashboards personalizados',
            'public_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => $package['id'],
            'adms_groups_page_id' => $group['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $table = $this->table('adms_pages');
        $table->insert($page)->saveData();
        
        $pageId = $this->getAdapter()->getConnection()->lastInsertId();
        
        echo "✅ Página 'EditDashboard' adicionada com sucesso! (ID: {$pageId})\n";
    }
}

