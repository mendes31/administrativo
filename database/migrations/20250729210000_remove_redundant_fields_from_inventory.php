<?php

use Phinx\Migration\AbstractMigration;

class RemoveRedundantFieldsFromInventory extends AbstractMigration
{
    public function up()
    {
        if ($this->hasTable('lgpd_inventory')) {
            $table = $this->table('lgpd_inventory');
            
            // Verificar se as colunas existem antes de tentar removê-las
            if ($table->hasColumn('data_category')) {
                $table->removeColumn('data_category');
            }
            if ($table->hasColumn('risk_level')) {
                $table->removeColumn('risk_level');
            }
            
            $table->update();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_inventory')) {
            $table = $this->table('lgpd_inventory');
            
            // Adicionar as colunas de volta no rollback
            $table->addColumn('data_category', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria dos dados'])
                  ->addColumn('risk_level', 'enum', ['values' => ['Baixo', 'Médio', 'Alto'], 'default' => 'Médio', 'null' => true, 'comment' => 'Nível de risco'])
                  ->update();
        }
    }
} 