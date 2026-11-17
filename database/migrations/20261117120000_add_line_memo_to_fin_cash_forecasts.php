<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Observação do lançamento (JDT1.LineMemo ou OJDT.Memo) no cache de previstos.
 */
final class AddLineMemoToFinCashForecasts extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_fin_cash_forecasts')) {
            return;
        }
        $table = $this->table('adms_fin_cash_forecasts');
        if (!$table->hasColumn('line_memo')) {
            $table->addColumn('line_memo', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'title_num',
                'comment' => 'Observação da linha ou do lançamento no SAP',
            ]);
            $table->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_fin_cash_forecasts')) {
            return;
        }
        $table = $this->table('adms_fin_cash_forecasts');
        if ($table->hasColumn('line_memo')) {
            $table->removeColumn('line_memo');
            $table->update();
        }
    }
}
