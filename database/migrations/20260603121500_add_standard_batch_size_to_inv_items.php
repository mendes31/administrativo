<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tamanho padrão do lote de produção do item (unidades por lote).
 * Tempos e quantidades da BOM/rota referem-se ao lote; o custo unitário divide por este valor.
 */
final class AddStandardBatchSizeToInvItems extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');
        if (!$table->hasColumn('standard_batch_size')) {
            $table
                ->addColumn('standard_batch_size', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'default' => 1,
                    'null' => false,
                    'after' => 'max_stock',
                    'comment' => 'Quantidade produzida por lote padrão (rateio de custo da rota/BOM)',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');
        if ($table->hasColumn('standard_batch_size')) {
            $table->removeColumn('standard_batch_size')->update();
        }
    }
}
