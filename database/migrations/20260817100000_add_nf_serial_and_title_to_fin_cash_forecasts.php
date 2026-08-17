<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Separa nota fiscal (Serial) do título/boleto no cache de previstos.
 */
final class AddNfSerialAndTitleToFinCashForecasts extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_fin_cash_forecasts')) {
            return;
        }
        $table = $this->table('adms_fin_cash_forecasts');
        if (!$table->hasColumn('nf_serial')) {
            $table->addColumn('nf_serial', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => '',
                'after' => 'doc_num',
                'comment' => 'Número da NF (OINV/OPCH.Serial)',
            ]);
        }
        if (!$table->hasColumn('title_num')) {
            $table->addColumn('title_num', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => '',
                'after' => 'nf_serial',
                'comment' => 'Título/boleto (OBOE.BoeNum) quando existir',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_fin_cash_forecasts')) {
            return;
        }
        $table = $this->table('adms_fin_cash_forecasts');
        if ($table->hasColumn('title_num')) {
            $table->removeColumn('title_num');
        }
        if ($table->hasColumn('nf_serial')) {
            $table->removeColumn('nf_serial');
        }
        $table->update();
    }
}
