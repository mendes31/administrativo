<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSapUpdateDateToInvItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('inv_items');
        if (!$table->hasColumn('sap_update_date')) {
            $table->addColumn('sap_update_date', 'date', [
                'null' => true,
                'after' => 'inv_pharma_form_id',
                'comment' => 'Última UpdateDate conhecida no SAP (OITM) para sync incremental',
            ])->update();
        }
    }
}
