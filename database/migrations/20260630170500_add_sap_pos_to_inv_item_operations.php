<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSapPosToInvItemOperations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');
        if (!$table->hasColumn('sap_pos_id')) {
            $table->addColumn('sap_pos_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'sequence',
                'comment' => 'POS_ID BEAS_APL (posição SAP)',
            ]);
        }
        if (!$table->hasColumn('sap_master_pos_id')) {
            $table->addColumn('sap_master_pos_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'sap_pos_id',
                'comment' => 'MASTER_POS_ID BEAS_APL (operação dependente da posição mestre)',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');
        if ($table->hasColumn('sap_master_pos_id')) {
            $table->removeColumn('sap_master_pos_id');
        }
        if ($table->hasColumn('sap_pos_id')) {
            $table->removeColumn('sap_pos_id');
        }
        $table->update();
    }
}
