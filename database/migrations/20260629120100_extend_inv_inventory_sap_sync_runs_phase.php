<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExtendInvInventorySapSyncRunsPhase extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('inv_inventory_sap_sync_runs')) {
            return;
        }

        $table = $this->table('inv_inventory_sap_sync_runs');

        if (!$table->hasColumn('current_phase')) {
            $table->addColumn('current_phase', 'string', [
                'limit' => 30,
                'null' => true,
                'after' => 'sync_mode',
                'comment' => 'items | structures | all',
            ]);
        }

        $table->update();
    }
}
