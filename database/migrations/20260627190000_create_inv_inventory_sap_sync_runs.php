<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvInventorySapSyncRuns extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('inv_inventory_sap_sync_runs')) {
            return;
        }

        $this->table('inv_inventory_sap_sync_runs')
            ->addColumn('sync_type', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'items | structures',
            ])
            ->addColumn('sync_mode', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'incremental',
                'comment' => 'full | incremental',
            ])
            ->addColumn('filter_from_date', 'date', ['null' => true])
            ->addColumn('rows_created', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('rows_updated', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('rows_unchanged', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('rows_failed', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'running'])
            ->addColumn('error_log', 'text', ['null' => true])
            ->addColumn('started_at', 'datetime', ['null' => false])
            ->addColumn('finished_at', 'datetime', ['null' => true])
            ->addIndex(['sync_type', 'status', 'finished_at'])
            ->create();
    }
}
