<?php

declare(strict_types=1);

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;
use Phinx\Migration\AbstractMigration;

/**
 * Sequência de código do equipamento por tipo + site (EXT00001 pode repetir em outro site).
 */
final class SstEquipamentoCodigoSeqPorSite extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_equipamentos')) {
            $this->execute(
                "UPDATE adms_sst_equipamentos SET empresa_contratante = 'laboratorio_tiaraju'
                 WHERE empresa_contratante = 'lab_tiaraju_matriz'"
            );
            $this->execute(
                "UPDATE adms_sst_equipamentos SET empresa_contratante = 'afra_pharma'
                 WHERE empresa_contratante = 'lab_tiaraju_filial'"
            );

            $eq = $this->table('adms_sst_equipamentos');
            if ($eq->hasIndexByName('codigo')) {
                $eq->removeIndexByName('codigo')->update();
            }
            if (!$eq->hasIndexByName('idx_sst_equipamentos_codigo')) {
                $eq->addIndex(['codigo'], ['name' => 'idx_sst_equipamentos_codigo'])->update();
            }
            if (!$eq->hasIndexByName('uq_sst_equipamentos_site_codigo')) {
                $eq->addIndex(['empresa_contratante', 'codigo'], [
                    'unique' => true,
                    'name' => 'uq_sst_equipamentos_site_codigo',
                ])->update();
            }
        }

        if ($this->hasTable('adms_sst_equipamento_codigo_seq')) {
            $this->table('adms_sst_equipamento_codigo_seq')->drop()->save();
        }

        $this->table('adms_sst_equipamento_codigo_seq', [
            'id' => false,
            'primary_key' => ['adms_sst_equipamento_tipo_id', 'site_slug'],
        ])
            ->addColumn('adms_sst_equipamento_tipo_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('site_slug', 'string', [
                'limit' => 64,
                'null' => false,
                'comment' => 'Site do equipamento (lista fixa) ou vazio para legado sem site',
            ])
            ->addColumn('ultimo_numero', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('adms_sst_equipamento_tipo_id', 'adms_sst_equipamento_tipos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        $this->reseedFromEquipamentos();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_equipamentos')) {
            $eq = $this->table('adms_sst_equipamentos');
            if ($eq->hasIndexByName('uq_sst_equipamentos_site_codigo')) {
                $eq->removeIndexByName('uq_sst_equipamentos_site_codigo')->update();
            }
            if ($eq->hasIndexByName('idx_sst_equipamentos_codigo')) {
                $eq->removeIndexByName('idx_sst_equipamentos_codigo')->update();
            }
            $dupes = $this->fetchRow(
                'SELECT codigo FROM adms_sst_equipamentos GROUP BY codigo HAVING COUNT(*) > 1 LIMIT 1'
            );
            if (!$dupes && !$eq->hasIndexByName('codigo')) {
                $eq->addIndex(['codigo'], ['unique' => true, 'name' => 'codigo'])->update();
            }
        }

        if ($this->hasTable('adms_sst_equipamento_codigo_seq')) {
            $this->table('adms_sst_equipamento_codigo_seq')->drop()->save();
        }

        $this->table('adms_sst_equipamento_codigo_seq', [
            'id' => false,
            'primary_key' => ['adms_sst_equipamento_tipo_id'],
        ])
            ->addColumn('adms_sst_equipamento_tipo_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('ultimo_numero', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('adms_sst_equipamento_tipo_id', 'adms_sst_equipamento_tipos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    private function reseedFromEquipamentos(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos') || !$this->hasTable('adms_sst_equipamento_tipos')) {
            return;
        }

        $rows = $this->fetchAll(
            'SELECT e.adms_sst_equipamento_tipo_id AS tipo_id, e.empresa_contratante, e.codigo, t.prefixo
             FROM adms_sst_equipamentos e
             INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id'
        );

        /** @var array<string, int> $max */
        $max = [];
        foreach ($rows as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($row['prefixo'] ?? ''));
            if ($tipoId <= 0 || !SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
                continue;
            }
            $n = SstEquipamentoCodigoHelper::extractNumero((string) ($row['codigo'] ?? ''), $prefixo);
            if ($n === null) {
                continue;
            }
            $site = SstEquipamentoSiteHelper::normalize($row['empresa_contratante'] ?? null)
                ?? trim((string) ($row['empresa_contratante'] ?? ''));
            $key = $tipoId . "\0" . $site;
            $max[$key] = max($max[$key] ?? 0, $n);
        }

        foreach ($max as $key => $ultimo) {
            [$tipoId, $site] = explode("\0", (string) $key, 2);
            $this->table('adms_sst_equipamento_codigo_seq')->insert([
                'adms_sst_equipamento_tipo_id' => (int) $tipoId,
                'site_slug' => $site,
                'ultimo_numero' => $ultimo,
            ])->save();
        }
    }
}
