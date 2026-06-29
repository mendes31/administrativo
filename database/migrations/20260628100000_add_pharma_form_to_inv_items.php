<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPharmaFormToInvItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('inv_items');
        if (!$table->hasColumn('pharma_form')) {
            $table->addColumn('pharma_form', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'production_line',
                'comment' => 'Forma farmacêutica (SAP U_FormaFarma)',
            ])->update();
        }
    }
}
