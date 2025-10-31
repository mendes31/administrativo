<?php

use Phinx\Migration\AbstractMigration;

class AddCountryToCrmPartners extends AbstractMigration
{
    public function change(): void
    {
        // Verificar se a tabela existe
        if (!$this->hasTable('crm_partners')) {
            return;
        }

        $table = $this->table('crm_partners');
        
        // Verificar se a coluna já existe antes de adicionar
        if (!$table->hasColumn('country')) {
            $table->addColumn('country', 'string', [
                      'limit' => 2,
                      'default' => 'BR',
                      'after' => 'state',
                      'comment' => 'Código do país (ISO 3166-1 alpha-2)'
                  ])
                  ->addIndex(['country'])
                  ->update();
        }
    }
}

