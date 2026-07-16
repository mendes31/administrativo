<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * DOCNUM por série (EM/SM/ET/DS/AS), justificativa/motivo e suporte a custo médio por CA.
 */
final class AddSstEpiMovimentoDocnumMotivoSeries extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_epi_movimento_series')) {
            $this->table('adms_sst_epi_movimento_series', ['id' => false, 'primary_key' => ['serie']])
                ->addColumn('serie', 'string', ['limit' => 5, 'null' => false])
                ->addColumn('descricao', 'string', ['limit' => 80, 'null' => false])
                ->addColumn('proximo_numero', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();

            $now = date('Y-m-d H:i:s');
            $this->table('adms_sst_epi_movimento_series')->insert([
                ['serie' => 'EM', 'descricao' => 'Entrada de mercadoria', 'proximo_numero' => 1, 'updated_at' => $now],
                ['serie' => 'SM', 'descricao' => 'Saída de mercadoria', 'proximo_numero' => 1, 'updated_at' => $now],
                ['serie' => 'ET', 'descricao' => 'Entrega a colaborador', 'proximo_numero' => 1, 'updated_at' => $now],
                ['serie' => 'DS', 'descricao' => 'Devolução ao estoque', 'proximo_numero' => 1, 'updated_at' => $now],
                ['serie' => 'AS', 'descricao' => 'Ajuste de saldo', 'proximo_numero' => 1, 'updated_at' => $now],
            ])->saveData();
        }

        if (!$this->hasTable('adms_sst_epi_movimentos')) {
            return;
        }

        $table = $this->table('adms_sst_epi_movimentos');
        if (!$table->hasColumn('doc_serie')) {
            $table->addColumn('doc_serie', 'string', [
                'limit' => 5,
                'null' => true,
                'after' => 'id',
                'comment' => 'EM, SM, ET, DS, AS',
            ]);
        }
        if (!$table->hasColumn('doc_numero')) {
            $table->addColumn('doc_numero', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'doc_serie',
            ]);
        }
        if (!$table->hasColumn('doc_codigo')) {
            $table->addColumn('doc_codigo', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'doc_numero',
                'comment' => 'Ex.: EM 000001',
            ]);
        }
        if (!$table->hasColumn('motivo')) {
            $table->addColumn('motivo', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'observacoes',
                'comment' => 'Motivo padronizado (Saída/Ajuste)',
            ]);
        }
        if (!$table->hasColumn('justificativa')) {
            $table->addColumn('justificativa', 'text', [
                'null' => true,
                'after' => 'motivo',
                'comment' => 'Obrigatória em AS e SM',
            ]);
        }
        $table->update();

        try {
            $this->table('adms_sst_epi_movimentos')
                ->addIndex(['doc_serie', 'doc_numero'], ['unique' => true, 'name' => 'idx_sst_epi_mov_doc'])
                ->update();
        } catch (\Throwable) {
            // índice já existente
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_epi_movimentos')) {
            $table = $this->table('adms_sst_epi_movimentos');
            try {
                $table->removeIndexByName('idx_sst_epi_mov_doc');
            } catch (\Throwable) {
            }
            foreach (['justificativa', 'motivo', 'doc_codigo', 'doc_numero', 'doc_serie'] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }

        if ($this->hasTable('adms_sst_epi_movimento_series')) {
            $this->table('adms_sst_epi_movimento_series')->drop()->save();
        }
    }
}
