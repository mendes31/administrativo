<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProgressToInvInventorySapSyncRuns extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('inv_inventory_sap_sync_runs')) {
            return;
        }

        $table = $this->table('inv_inventory_sap_sync_runs');

        if (!$table->hasColumn('progress_examined')) {
            $table->addColumn('progress_examined', 'integer', [
                'signed' => false,
                'default' => 0,
                'null' => false,
                'after' => 'rows_failed',
            ]);
        }

        if (!$table->hasColumn('progress_total')) {
            $table->addColumn('progress_total', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'progress_examined',
            ]);
        }

        if (!$table->hasColumn('progress_label')) {
            $table->addColumn('progress_label', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'progress_total',
            ]);
        }

        if (!$table->hasColumn('result_message')) {
            $table->addColumn('result_message', 'text', [
                'null' => true,
                'after' => 'error_log',
            ]);
        }

        $table->update();
    }
}
