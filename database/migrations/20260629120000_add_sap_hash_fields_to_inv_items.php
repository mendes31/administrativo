<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSapHashFieldsToInvItems extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');

        if (!$table->hasColumn('sap_item_hash')) {
            $table->addColumn('sap_item_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'sap_update_date',
                'comment' => 'SHA-256 dos campos SAP do item',
            ]);
        }

        if (!$table->hasColumn('sap_beas_version')) {
            $table->addColumn('sap_beas_version', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'sap_item_hash',
                'comment' => 'U_beas_ver do SAP',
            ]);
        }

        if (!$table->hasColumn('sap_bom_hash')) {
            $table->addColumn('sap_bom_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'sap_beas_version',
            ]);
        }

        if (!$table->hasColumn('sap_route_hash')) {
            $table->addColumn('sap_route_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'after' => 'sap_bom_hash',
            ]);
        }

        if (!$table->hasColumn('sap_structure_pending')) {
            $table->addColumn('sap_structure_pending', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'sap_route_hash',
                'comment' => 'BOM pendente de sync SAP',
            ]);
        }

        if (!$table->hasColumn('sap_route_pending')) {
            $table->addColumn('sap_route_pending', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'sap_structure_pending',
                'comment' => 'Rota pendente de sync SAP',
            ]);
        }

        if (!$table->hasColumn('sap_last_synced_at')) {
            $table->addColumn('sap_last_synced_at', 'datetime', [
                'null' => true,
                'after' => 'sap_route_pending',
            ]);
        }

        if (!$table->hasIndex(['sap_structure_pending', 'sap_route_pending'])) {
            $table->addIndex(['sap_structure_pending', 'sap_route_pending'], [
                'name' => 'idx_inv_items_sap_structure_pending',
            ]);
        }

        $table->update();
    }
}
