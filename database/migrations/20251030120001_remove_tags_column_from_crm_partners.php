<?php

use Phinx\Migration\AbstractMigration;

class RemoveTagsColumnFromCrmPartners extends AbstractMigration
{
    /**
     * Remove o campo 'tags' da tabela crm_partners
     * Tags agora são gerenciadas via crm_partner_tags (many-to-many)
     */
    public function change()
    {
        $table = $this->table('crm_partners');
        
        if ($table->hasColumn('tags')) {
            $table->removeColumn('tags')
                  ->update();
        }
    }
}

