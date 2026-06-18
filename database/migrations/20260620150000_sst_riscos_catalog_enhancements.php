<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Catálogo de riscos SST — Fase 2 (código, grupo NR-01, flags PCMSO/EPI).
 */
final class SstRiscosCatalogEnhancements extends AbstractMigration
{
    private const GRUPOS = [
        'Físico',
        'Químico',
        'Biológico',
        'Ergonômico',
        'Acidente/Mecânico',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_sst_riscos')) {
            return;
        }

        $table = $this->table('adms_sst_riscos');

        if (!$table->hasColumn('codigo')) {
            $table->addColumn('codigo', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'nome',
                'comment' => 'Código interno (ex.: RIS001)',
            ]);
        }

        if (!$table->hasColumn('grupo_risco')) {
            $table->addColumn('grupo_risco', 'enum', [
                'values' => self::GRUPOS,
                'null' => true,
                'after' => 'descricao',
                'comment' => 'Grupo conforme NR-01 (PGR/GRO)',
            ]);
        }

        if (!$table->hasColumn('necessita_monitoramento_medico')) {
            $table->addColumn('necessita_monitoramento_medico', 'boolean', [
                'default' => false,
                'after' => 'grupo_risco',
            ]);
        }

        if (!$table->hasColumn('necessita_epi')) {
            $table->addColumn('necessita_epi', 'boolean', [
                'default' => false,
                'after' => 'necessita_monitoramento_medico',
            ]);
        }

        $table->update();

        $this->backfillGrupoFromTipo();

        $refreshed = $this->table('adms_sst_riscos');
        if ($refreshed->hasColumn('codigo') && !$refreshed->hasIndexByName('uq_sst_riscos_codigo')) {
            $this->table('adms_sst_riscos')
                ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_sst_riscos_codigo'])
                ->update();
        }

        $refreshed = $this->table('adms_sst_riscos');
        if ($refreshed->hasColumn('grupo_risco') && !$refreshed->hasIndexByName('idx_sst_riscos_grupo')) {
            $this->table('adms_sst_riscos')
                ->addIndex(['grupo_risco'], ['name' => 'idx_sst_riscos_grupo'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_riscos')) {
            return;
        }

        $table = $this->table('adms_sst_riscos');

        if ($table->hasIndexByName('idx_sst_riscos_grupo')) {
            $table->removeIndexByName('idx_sst_riscos_grupo');
        }
        if ($table->hasIndexByName('uq_sst_riscos_codigo')) {
            $table->removeIndexByName('uq_sst_riscos_codigo');
        }

        foreach (['necessita_epi', 'necessita_monitoramento_medico', 'grupo_risco', 'codigo'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }

        $table->update();
    }

    private function backfillGrupoFromTipo(): void
    {
        if (!$this->table('adms_sst_riscos')->hasColumn('grupo_risco')) {
            return;
        }

        $map = [
            'fisico' => 'Físico',
            'físico' => 'Físico',
            'quimico' => 'Químico',
            'químico' => 'Químico',
            'biologico' => 'Biológico',
            'biológico' => 'Biológico',
            'ergonomico' => 'Ergonômico',
            'ergonômico' => 'Ergonômico',
            'acidente' => 'Acidente/Mecânico',
            'acidente/mecanico' => 'Acidente/Mecânico',
            'acidente/mecânico' => 'Acidente/Mecânico',
            'mecanico' => 'Acidente/Mecânico',
            'mecânico' => 'Acidente/Mecânico',
        ];

        foreach (self::GRUPOS as $grupo) {
            $map[mb_strtolower($grupo, 'UTF-8')] = $grupo;
        }

        $rows = $this->fetchAll('SELECT id, tipo FROM adms_sst_riscos WHERE grupo_risco IS NULL AND tipo IS NOT NULL AND tipo <> \'\'');
        $pdo = $this->getAdapter()->getConnection();
        $stmt = $pdo->prepare('UPDATE adms_sst_riscos SET grupo_risco = :grupo WHERE id = :id');

        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) ($row['tipo'] ?? '')), 'UTF-8');
            if (!isset($map[$key])) {
                continue;
            }
            $stmt->execute(['grupo' => $map[$key], 'id' => (int) $row['id']]);
        }
    }
}
