<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Estoque mínimo por numeração/tamanho, além do padrão da grade no cadastro do EPI. */
final class SstEpiEstoqueMinTamanho extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_epi_estoque_min_tamanho')) {
            $this->table('adms_sst_epi_estoque_min_tamanho')->drop()->save();
        }

        $this->table('adms_sst_epi_estoque_min_tamanho')
            ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('tamanho', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('estoque_minimo', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['adms_sst_epi_id', 'tamanho'], ['unique' => true, 'name' => 'uq_sst_epi_min_tamanho'])
            ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_epi_estoque_min_tamanho')) {
            $this->table('adms_sst_epi_estoque_min_tamanho')->drop()->save();
        }
    }
}
