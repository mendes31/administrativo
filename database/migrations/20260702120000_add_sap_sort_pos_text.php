<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSapSortPosText extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('inv_item_operations')) {
            $table = $this->table('inv_item_operations');
            if (!$table->hasColumn('sap_sort_id')) {
                $table->addColumn('sap_sort_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'sap_master_pos_id',
                    'comment' => 'SortId BEAS_APL — ordem de exibição da rota',
                ])->update();
            }
            if (!$table->hasColumn('sap_pos_text')) {
                $table->addColumn('sap_pos_text', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'sap_sort_id',
                    'comment' => 'POS_TEXT BEAS_APL — posição exibida ao usuário',
                ])->update();
            }
        }

        if ($this->hasTable('inv_item_route_consolidated')) {
            $table = $this->table('inv_item_route_consolidated');
            if (!$table->hasColumn('sap_group_pos_text')) {
                $table->addColumn('sap_group_pos_text', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'sap_group_pos_id',
                    'comment' => 'POS_TEXT do POS_ID mestre do grupo (sap_group_pos_id)',
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_operations')) {
            $table = $this->table('inv_item_operations');
            if ($table->hasColumn('sap_pos_text')) {
                $table->removeColumn('sap_pos_text')->update();
            }
            if ($table->hasColumn('sap_sort_id')) {
                $table->removeColumn('sap_sort_id')->update();
            }
        }

        if ($this->hasTable('inv_item_route_consolidated')) {
            $table = $this->table('inv_item_route_consolidated');
            if ($table->hasColumn('sap_group_pos_text')) {
                $table->removeColumn('sap_group_pos_text')->update();
            }
        }
    }
}
