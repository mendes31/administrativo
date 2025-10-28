<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmPartnerTags extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('crm_partner_tags', ['id' => false, 'primary_key' => ['partner_id', 'tag_id']]);
        
        $table->addColumn('partner_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('tag_id', 'integer', ['null' => false, 'signed' => false])
              
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('tag_id', 'crm_tags', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              
              ->create();
    }
}

