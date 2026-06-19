<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Catálogo EPI genérico (categoria) e rastreio de CA nas movimentações de estoque.
 */
final class SstEpiCategoriaCaMovimentos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_epis')) {
            $epis = $this->table('adms_sst_epis');
            if (!$epis->hasColumn('categoria')) {
                $epis->addColumn('categoria', 'string', [
                    'limit' => 80,
                    'null' => true,
                    'comment' => 'Tipo de proteção (ex.: Proteção Respiratória)',
                ])
                    ->addIndex(['categoria'])
                    ->update();
            }
        }

        if ($this->hasTable('adms_sst_epi_movimentos')) {
            $mov = $this->table('adms_sst_epi_movimentos');
            if (!$mov->hasColumn('ca_numero')) {
                $mov->addColumn('ca_numero', 'string', [
                    'limit' => 50,
                    'null' => true,
                    'comment' => 'CA do lote movimentado',
                ])->update();
            }
            if (!$this->table('adms_sst_epi_movimentos')->hasColumn('ca_validade')) {
                $this->table('adms_sst_epi_movimentos')->addColumn('ca_validade', 'date', [
                    'null' => true,
                    'comment' => 'Validade do CA do lote',
                ])->update();
            }
            if ($this->table('adms_sst_epi_movimentos')->hasColumn('ca_numero')) {
                $this->table('adms_sst_epi_movimentos')->addIndex(['ca_numero'])->update();
            }
        }

        if (!$this->hasTable('adms_sst_epis') || !$this->hasTable('adms_sst_epi_movimentos')) {
            return;
        }

        if (!$this->table('adms_sst_epi_movimentos')->hasColumn('ca_numero')) {
            return;
        }

        $rows = $this->fetchAll(
            "SELECT id, ca_numero, ca_validade FROM adms_sst_epis
             WHERE ca_numero IS NOT NULL AND TRIM(ca_numero) <> ''"
        );
        foreach ($rows as $row) {
            $epiId = (int) ($row['id'] ?? 0);
            $ca = strtoupper(trim((string) ($row['ca_numero'] ?? '')));
            if ($epiId <= 0 || $ca === '') {
                continue;
            }
            $validade = $row['ca_validade'] ?? null;
            $validadeSql = ($validade !== null && $validade !== '') ? $this->getAdapter()->getConnection()->quote((string) $validade) : 'NULL';
            $this->execute(
                'UPDATE adms_sst_epi_movimentos SET ca_numero = '
                . $this->getAdapter()->getConnection()->quote($ca)
                . ', ca_validade = ' . $validadeSql
                . ' WHERE adms_sst_epi_id = ' . $epiId
                . " AND (ca_numero IS NULL OR TRIM(ca_numero) = '')"
            );
        }

        $this->execute('UPDATE adms_sst_epis SET ca_numero = NULL, ca_validade = NULL');

        if ($this->table('adms_sst_epis')->hasColumn('categoria')) {
            $this->execute(
                "UPDATE adms_sst_epis SET categoria = 'Outros'
                 WHERE categoria IS NULL OR TRIM(categoria) = ''"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_epi_movimentos')) {
            $mov = $this->table('adms_sst_epi_movimentos');
            if ($mov->hasColumn('ca_validade')) {
                $mov->removeColumn('ca_validade')->update();
            }
            if ($mov->hasColumn('ca_numero')) {
                $mov->removeColumn('ca_numero')->update();
            }
        }
        if ($this->hasTable('adms_sst_epis') && $this->table('adms_sst_epis')->hasColumn('categoria')) {
            $this->table('adms_sst_epis')->removeColumn('categoria')->update();
        }
    }
}
