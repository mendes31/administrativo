<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddOrganizationChartPermission extends AbstractSeed
{
    /**
     * Adicionar permissão de Organograma para Super Admin
     */
    public function run(): void
    {
        // Buscar ID da página
        $page = $this->query('SELECT id FROM adms_pages WHERE controller = "OrganizationChart"')->fetch();
        
        if (!$page) {
            echo "⚠️ Página OrganizationChart não encontrada. Execute AddOrganizationChartPage primeiro.\n";
            return;
        }
        
        $pageId = $page['id'];
        
        // Verificar se já existe permissão para Super Admin (ID 1)
        $existing = $this->query(
            'SELECT id FROM adms_access_levels_pages WHERE adms_access_level_id = 1 AND adms_page_id = :page_id',
            ['page_id' => $pageId]
        )->fetch();
        
        if (!$existing) {
            $data = [
                [
                    'adms_access_level_id' => 1, // Super Admin
                    'adms_page_id' => $pageId,
                    'permission' => 1
                ]
            ];
            
            $table = $this->table('adms_access_levels_pages');
            $table->insert($data)->save();
            
            echo "✅ Permissão de Organograma adicionada para Super Admin\n";
        } else {
            echo "ℹ️ Permissão já existe\n";
        }
    }
}

