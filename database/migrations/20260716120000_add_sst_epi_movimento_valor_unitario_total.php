<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Valor unitário e total (R$) nas movimentações de estoque EPI.
 * Base para relatórios de gastos por período / setor / cargo.
 */
final class AddSstEpiMovimentoValorUnitarioTotal extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_epi_movimentos')) {
            return;
        }

        $table = $this->table('adms_sst_epi_movimentos');
        if (!$table->hasColumn('valor_unitario')) {
            $table->addColumn('valor_unitario', 'decimal', [
                'precision' => 12,
                'scale' => 2,
                'null' => true,
                'signed' => false,
                'after' => 'quantidade',
                'comment' => 'Custo unitário em R$',
            ]);
        }
        if (!$table->hasColumn('valor_total')) {
            $table->addColumn('valor_total', 'decimal', [
                'precision' => 14,
                'scale' => 2,
                'null' => true,
                'signed' => false,
                'after' => 'valor_unitario',
                'comment' => 'quantidade × valor_unitario',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_epi_movimentos')) {
            return;
        }

        $table = $this->table('adms_sst_epi_movimentos');
        if ($table->hasColumn('valor_total')) {
            $table->removeColumn('valor_total');
        }
        if ($table->hasColumn('valor_unitario')) {
            $table->removeColumn('valor_unitario');
        }
        $table->update();
    }
}
