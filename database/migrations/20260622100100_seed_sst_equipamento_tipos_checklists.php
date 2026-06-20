<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tipos padrão de equipamentos de segurança + checklist do extintor.
 */
final class SeedSstEquipamentoTiposChecklists extends AbstractMigration
{
    /** @var list<array{nome: string, codigo: string, checklist?: list<string>}> */
    private const TIPOS = [
        [
            'nome' => 'Extintor',
            'codigo' => 'EXTINTOR',
            'checklist' => [
                'Manômetro na faixa verde',
                'Lacre íntegro',
                'Sem obstrução de acesso',
                'Sinalização visível',
                'Acesso livre',
                'Sem avarias aparentes',
                'Data de recarga válida',
                'Suporte adequado',
            ],
        ],
        ['nome' => 'Hidrante', 'codigo' => 'HIDRANTE'],
        ['nome' => 'Mangueira de incêndio', 'codigo' => 'MANGUEIRA'],
        ['nome' => 'Porta corta-fogo', 'codigo' => 'PORTA_CORTA_FOGO'],
        ['nome' => 'Iluminação de emergência', 'codigo' => 'ILUMINACAO_EMERG'],
        ['nome' => 'Detector de fumaça', 'codigo' => 'DETECTOR_FUMACA'],
        ['nome' => 'Chuveiro / lava-olhos', 'codigo' => 'CHUVEIRO_LAVA_OLHOS'],
        ['nome' => 'Alarme / central de incêndio', 'codigo' => 'ALARME_INCENDIO'],
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_sst_equipamento_tipos')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach (self::TIPOS as $tipo) {
            $codigo = $tipo['codigo'];
            $exists = $this->fetchRow(
                "SELECT id FROM adms_sst_equipamento_tipos WHERE codigo = '" . addslashes($codigo) . "' LIMIT 1"
            );
            if ($exists) {
                continue;
            }

            $this->table('adms_sst_equipamento_tipos')->insert([
                'nome' => $tipo['nome'],
                'codigo' => $codigo,
                'descricao' => null,
                'status' => 'Ativo',
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            $tipoId = (int) $this->getAdapter()->getConnection()->lastInsertId();
            $ordem = 1;
            foreach ($tipo['checklist'] ?? [] as $descricao) {
                $this->table('adms_sst_equipamento_checklist_itens')->insert([
                    'adms_sst_equipamento_tipo_id' => $tipoId,
                    'descricao' => $descricao,
                    'ordem' => $ordem++,
                    'obrigatorio' => 1,
                    'ativo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->save();
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_equipamento_tipos')) {
            return;
        }
        $codigos = array_map(
            static fn (array $t): string => "'" . addslashes($t['codigo']) . "'",
            self::TIPOS
        );
        $this->execute(
            'DELETE FROM adms_sst_equipamento_tipos WHERE codigo IN (' . implode(',', $codigos) . ')'
        );
    }
}
