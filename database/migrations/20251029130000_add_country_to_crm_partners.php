<?php

use Phinx\Migration\AbstractMigration;

class AddCountryToCrmPartners extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('crm_partners');
        
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

