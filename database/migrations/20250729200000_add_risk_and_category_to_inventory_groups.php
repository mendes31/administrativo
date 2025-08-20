<?php

use Phinx\Migration\AbstractMigration;

class AddRiskAndCategoryToInventoryGroups extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lgpd_inventory_data_groups');
        
        // Adicionar campos para controle individual de risco e categoria
        $table->addColumn('risk_level', 'string', [
            'null' => true,
            'limit' => 20,
            'comment' => 'Nível de risco específico para este grupo neste inventário'
        ]);
        
        $table->addColumn('data_category', 'string', [
            'null' => true,
            'limit' => 20,
            'comment' => 'Categoria específica para este grupo neste inventário'
        ]);
        
        $table->addColumn('notes', 'text', [
            'null' => true,
            'comment' => 'Observações específicas sobre este grupo neste contexto'
        ]);
        
        $table->update();
    }
} 