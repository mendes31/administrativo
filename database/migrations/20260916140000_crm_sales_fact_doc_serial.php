<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Serial da NF (OINV/ORIN."Serial") no cache, para a listagem de notas.
 * Não esvazia o cache: notas antigas ficam 0 até um sync (incremental cobre
 * os últimos dias; --full preenche o histórico).
 */
final class CrmSalesFactDocSerial extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('crm_sales_fact_daily')) {
            return;
        }
        $table = $this->table('crm_sales_fact_daily');
        if ($table->hasColumn('doc_serial')) {
            return;
        }
        $after = $table->hasColumn('doc_entry') ? 'doc_entry' : 'tipo_documento';
        $table
            ->addColumn('doc_serial', 'integer', [
                'signed' => false,
                'null' => false,
                'default' => 0,
                'after' => $after,
            ])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('crm_sales_fact_daily')) {
            return;
        }
        $table = $this->table('crm_sales_fact_daily');
        if ($table->hasColumn('doc_serial')) {
            $table->removeColumn('doc_serial')->update();
        }
    }
}
