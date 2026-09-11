<?php

declare(strict_types=1);

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;
use Phinx\Migration\AbstractMigration;

/**
 * Reenumera códigos já cadastrados: cada tipo + site começa em 00001.
 */
final class SstEquipamentoRenumerarCodigoPorSite extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos') || !$this->hasTable('adms_sst_equipamento_tipos')) {
            return;
        }

        $rows = $this->fetchAll(
            'SELECT e.id, e.codigo, e.adms_sst_equipamento_tipo_id AS tipo_id, e.empresa_contratante, t.prefixo
             FROM adms_sst_equipamentos e
             INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
             ORDER BY e.adms_sst_equipamento_tipo_id ASC, e.codigo ASC, e.id ASC'
        );

        /** @var array<string, list<array<string, mixed>>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($row['prefixo'] ?? ''));
            if ($tipoId <= 0 || !SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
                continue;
            }
            $site = SstEquipamentoSiteHelper::normalize($row['empresa_contratante'] ?? null)
                ?? trim((string) ($row['empresa_contratante'] ?? ''));
            $key = $tipoId . "\0" . $site . "\0" . $prefixo;
            $groups[$key][] = $row;
        }

        foreach ($groups as $items) {
            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $this->execute('UPDATE adms_sst_equipamentos SET codigo = ' . $this->quote('~R' . $id) . ' WHERE id = ' . $id);
            }
        }

        /** @var array<string, int> $max */
        $max = [];
        foreach ($groups as $key => $items) {
            [$tipoId, $site, $prefixo] = explode("\0", $key, 3);
            $n = 0;
            foreach ($items as $item) {
                $n++;
                $id = (int) ($item['id'] ?? 0);
                $codigo = SstEquipamentoCodigoHelper::format($prefixo, $n);
                $this->execute(
                    'UPDATE adms_sst_equipamentos SET codigo = ' . $this->quote($codigo) . ' WHERE id = ' . $id
                );
            }
            $max[(int) $tipoId . "\0" . $site] = $n;
        }

        if ($this->hasTable('adms_sst_equipamento_codigo_seq')) {
            $this->execute('DELETE FROM adms_sst_equipamento_codigo_seq');
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

    public function down(): void
    {
        // Irreversível: os códigos antigos (numeração global) não são preservados.
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
