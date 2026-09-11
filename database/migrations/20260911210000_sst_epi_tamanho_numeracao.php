<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Grade de tamanho/numeração no cadastro do EPI e saldo por tamanho nas movimentações e fichas.
 */
final class SstEpiTamanhoNumeracao extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_epis')) {
            $epis = $this->table('adms_sst_epis');
            if (!$epis->hasColumn('controla_tamanho')) {
                $epis->addColumn('controla_tamanho', 'boolean', [
                    'default' => false,
                    'null' => false,
                    'after' => 'estoque_minimo',
                    'comment' => '1 = exige tamanho/numeração na movimentação e na ficha',
                ]);
            }
            if (!$epis->hasColumn('grade_tamanhos')) {
                $epis->addColumn('grade_tamanhos', 'string', [
                    'limit' => 500,
                    'null' => true,
                    'after' => 'controla_tamanho',
                    'comment' => 'Tamanhos permitidos separados por vírgula (ex.: 34,35,36 ou PP,P,M,G)',
                ]);
            }
            $epis->update();
        }

        if ($this->hasTable('adms_sst_epi_movimentos')) {
            $mov = $this->table('adms_sst_epi_movimentos');
            if (!$mov->hasColumn('tamanho')) {
                $mov->addColumn('tamanho', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'ca_numero',
                    'comment' => 'Numeração ou tamanho do lote (ex.: 38, GG)',
                ])->update();
            }
            if ($this->table('adms_sst_epi_movimentos')->hasColumn('tamanho')) {
                $this->table('adms_sst_epi_movimentos')->addIndex(['adms_sst_epi_id', 'tamanho'], [
                    'name' => 'idx_sst_epi_mov_tamanho',
                ])->update();
            }
        }

        if ($this->hasTable('adms_sst_epi_ficha_itens')) {
            $itens = $this->table('adms_sst_epi_ficha_itens');
            if (!$itens->hasColumn('tamanho')) {
                $itens->addColumn('tamanho', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'ca_utilizado',
                    'comment' => 'Tamanho/numeração entregue',
                ])->update();
            }
        }

        if ($this->hasTable('adms_sst_epi_entregas')) {
            $ent = $this->table('adms_sst_epi_entregas');
            if (!$ent->hasColumn('tamanho')) {
                $ent->addColumn('tamanho', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'quantidade',
                    'comment' => 'Tamanho/numeração da entrega',
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_epi_movimentos') && $this->table('adms_sst_epi_movimentos')->hasIndex('idx_sst_epi_mov_tamanho')) {
            $this->table('adms_sst_epi_movimentos')->removeIndexByName('idx_sst_epi_mov_tamanho')->update();
        }
        foreach (
            [
                'adms_sst_epi_movimentos' => ['tamanho'],
                'adms_sst_epi_ficha_itens' => ['tamanho'],
                'adms_sst_epi_entregas' => ['tamanho'],
                'adms_sst_epis' => ['grade_tamanhos', 'controla_tamanho'],
            ] as $table => $cols
        ) {
            if (!$this->hasTable($table)) {
                continue;
            }
            $t = $this->table($table);
            foreach ($cols as $col) {
                if ($t->hasColumn($col)) {
                    $t->removeColumn($col);
                }
            }
            $t->update();
        }
    }
}
