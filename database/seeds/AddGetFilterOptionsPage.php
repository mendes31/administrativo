<?php

use Phinx\Seed\AbstractSeed;

class AddGetFilterOptionsPage extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se a página já existe
        $existing = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'get-filter-options'");
        
        if ($existing) {
            echo "ℹ️  Página 'GetFilterOptions' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Buscar IDs necessários
        $package = $this->fetchRow("SELECT id FROM adms_packages_pages WHERE name = 'adms'");
        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Dashboards KPI'");
        
        if (!$package || !$group) {
            echo "⚠️  Pacote 'Administrativo' ou Grupo 'Relatórios' não encontrado\n";
            return;
        }
        
        // Inserir a página
        $page = [
            'name' => 'GetFilterOptions',
            'controller' => 'GetFilterOptions',
            'controller_url' => 'get-filter-options',
            'directory' => 'dashboards',
            'obs' => 'API para buscar opções de filtros dinâmicos (valores distintos)',
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
        
        echo "✅ Página 'GetFilterOptions' adicionada com sucesso! (ID: {$pageId})\n";
    }
}

