<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Movimentações de estoque de EPI (histórico; saldo derivado das movimentações).
 */
final class SstEpiMovimentos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_epi_movimentos')) {
            $this->table('adms_sst_epi_movimentos')
                ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('tipo_movimento', 'enum', [
                    'values' => ['Entrada', 'Saída', 'Ajuste', 'Entrega', 'Devolução'],
                    'null' => false,
                ])
                ->addColumn('quantidade', 'integer', ['null' => false, 'signed' => true, 'comment' => 'Positivo=entrada tipos; Ajuste pode ser negativo'])
                ->addColumn('data_movimento', 'date', ['null' => false])
                ->addColumn('documento_ref', 'string', ['limit' => 100, 'null' => true, 'comment' => 'NF, pedido, etc.'])
                ->addColumn('referencia_tipo', 'string', ['limit' => 50, 'null' => true, 'comment' => 'ficha_epi, manual, etc.'])
                ->addColumn('referencia_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('saldo_apos', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Snapshot do saldo após movimento'])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_epi_id'])
                ->addIndex(['tipo_movimento'])
                ->addIndex(['data_movimento'])
                ->addIndex(['referencia_tipo', 'referencia_id'])
                ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if ($this->hasTable('adms_sst_epis') && $this->hasTable('adms_sst_epi_movimentos')) {
            $rows = $this->fetchAll('SELECT id, estoque_atual FROM adms_sst_epis WHERE estoque_atual > 0');
            foreach ($rows as $row) {
                $epiId = (int) ($row['id'] ?? 0);
                $qty = (int) ($row['estoque_atual'] ?? 0);
                if ($epiId <= 0 || $qty <= 0) {
                    continue;
                }
                $exists = $this->fetchRow(
                    'SELECT 1 FROM adms_sst_epi_movimentos WHERE adms_sst_epi_id = ' . $epiId . ' LIMIT 1'
                );
                if ($exists) {
                    continue;
                }
                $this->table('adms_sst_epi_movimentos')->insert([
                    'adms_sst_epi_id' => $epiId,
                    'tipo_movimento' => 'Entrada',
                    'quantidade' => $qty,
                    'data_movimento' => date('Y-m-d'),
                    'documento_ref' => null,
                    'referencia_tipo' => 'migracao_saldo',
                    'referencia_id' => $epiId,
                    'saldo_apos' => $qty,
                    'observacoes' => 'Saldo inicial migrado do cadastro do EPI.',
                    'created_by' => null,
                ])->saveData();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_epi_movimentos')) {
            $this->table('adms_sst_epi_movimentos')->drop()->save();
        }
    }
}
