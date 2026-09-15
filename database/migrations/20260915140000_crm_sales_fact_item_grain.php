<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Inclui o item SAP no grão do cache para o ranking de itens vendidos.
 */
final class CrmSalesFactItemGrain extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('crm_sales_fact_daily')) {
            return;
        }

        $table = $this->table('crm_sales_fact_daily');
        if (!$table->hasColumn('item_code')) {
            $table
                ->addColumn('item_code', 'string', [
                    'limit' => 50,
                    'null' => false,
                    'default' => '',
                    'after' => 'grupo_item',
                ])
                ->addColumn('item_name', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'default' => '',
                    'after' => 'item_code',
                ])
                ->addIndex(['item_code'], ['name' => 'idx_crm_sales_item_code'])
                ->update();
        }

        $this->execute('DELETE FROM crm_sales_fact_daily');
    }

    public function down(): void
    {
        if (!$this->hasTable('crm_sales_fact_daily')) {
            return;
        }
        $table = $this->table('crm_sales_fact_daily');
        if ($table->hasIndex('idx_crm_sales_item_code')) {
            $table->removeIndexByName('idx_crm_sales_item_code');
        }
        foreach (['item_name', 'item_code'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
