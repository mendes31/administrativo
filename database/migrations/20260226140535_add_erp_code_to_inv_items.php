<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona o campo opcional erp_code em inv_items para armazenar
 * o código do item no ERP (ex.: SAP).
 *
 * Migration segura:
 * - Só adiciona a coluna se ainda não existir.
 */
final class AddErpCodeToInvItems extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('inv_items')) {
            $table = $this->table('inv_items');

            if (!$table->hasColumn('erp_code')) {
                $table
                    ->addColumn('erp_code', 'string', [
                        'limit' => 60,
                        'null' => true,
                        'after' => 'code',
                        'comment' => 'Código do item no ERP externo (ex.: SAP)',
                    ])
                    ->addIndex(['erp_code'], [
                        'name' => 'idx_inv_items_erp_code',
                        'unique' => false,
                    ])
                    ->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_items')) {
            $table = $this->table('inv_items');
            if ($table->hasColumn('erp_code')) {
                $table->removeColumn('erp_code')->update();
            }
        }
    }
}

