<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cache de entradas de produção (OIGN/IGN1) e sucata diária (BEAS_ARBZEIT).
 * A data da ordem (BELDAT) passa a ter coluna própria no fato da OP.
 */
final class ProdProductionReceiptCache extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_prod_receipt_fact')) {
            $this->table('adms_prod_receipt_fact', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
                ->addColumn('oign_doc_entry', 'integer', ['null' => false])
                ->addColumn('line_num', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('oign_doc_num', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('doc_date', 'date', ['null' => false])
                ->addColumn('belnr_id', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('item_code', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('item_name', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('warehouse', 'string', ['limit' => 30, 'null' => false, 'default' => ''])
                ->addColumn('qty', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['oign_doc_entry', 'line_num'], ['unique' => true, 'name' => 'uk_prod_receipt_doc_line'])
                ->addIndex(['doc_date'], ['name' => 'idx_prod_receipt_date'])
                ->addIndex(['warehouse'], ['name' => 'idx_prod_receipt_whs'])
                ->addIndex(['item_code'], ['name' => 'idx_prod_receipt_item'])
                ->addIndex(['belnr_id'], ['name' => 'idx_prod_receipt_belnr'])
                ->create();
        }

        if (!$this->hasTable('adms_prod_scrap_day')) {
            $this->table('adms_prod_scrap_day', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
                ->addColumn('doc_date', 'date', ['null' => false])
                ->addColumn('warehouse', 'string', ['limit' => 30, 'null' => false, 'default' => ''])
                ->addColumn('qty_good', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('qty_scrap', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => false, 'default' => '0'])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['doc_date', 'warehouse'], ['unique' => true, 'name' => 'uk_prod_scrap_day_whs'])
                ->create();
        }

        if ($this->hasTable('adms_prod_wo_fact') && !$this->table('adms_prod_wo_fact')->hasColumn('order_date')) {
            $this->table('adms_prod_wo_fact')
                ->addColumn('order_date', 'date', ['null' => true, 'after' => 'qty_scrap'])
                ->addIndex(['order_date'], ['name' => 'idx_prod_wo_order_date'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_prod_wo_fact') && $this->table('adms_prod_wo_fact')->hasColumn('order_date')) {
            $this->table('adms_prod_wo_fact')
                ->removeIndexByName('idx_prod_wo_order_date')
                ->removeColumn('order_date')
                ->update();
        }
        foreach (['adms_prod_scrap_day', 'adms_prod_receipt_fact'] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
