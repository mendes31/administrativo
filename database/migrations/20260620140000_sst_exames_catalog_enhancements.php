<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Catálogo de exames complementares SST — Fase 2 (tipo, código, validade, resultados).
 */
final class SstExamesCatalogEnhancements extends AbstractMigration
{
    private const TIPOS = [
        'Clínico',
        'Laboratorial',
        'Imagem',
        'Funcional',
        'Avaliação Médica',
        'Outros',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_sst_exames')) {
            return;
        }

        $table = $this->table('adms_sst_exames');

        if (!$table->hasColumn('codigo')) {
            $table->addColumn('codigo', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'nome',
                'comment' => 'Código interno (ex.: EX0001)',
            ]);
        }

        if (!$table->hasColumn('tipo')) {
            $table->addColumn('tipo', 'enum', [
                'values' => self::TIPOS,
                'null' => true,
                'after' => 'descricao',
                'comment' => 'Classificação do exame complementar',
            ]);
        }

        if (!$table->hasColumn('possui_validade')) {
            $table->addColumn('possui_validade', 'boolean', [
                'default' => false,
                'after' => 'periodicidade_meses',
                'comment' => 'Resultado do exame possui validade',
            ]);
        }

        if (!$table->hasColumn('validade_meses')) {
            $table->addColumn('validade_meses', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'possui_validade',
                'comment' => 'Validade do resultado em meses',
            ]);
        }

        if (!$table->hasColumn('exige_resultado')) {
            $table->addColumn('exige_resultado', 'boolean', [
                'default' => true,
                'after' => 'validade_meses',
            ]);
        }

        if (!$table->hasColumn('resultados_permitidos')) {
            $table->addColumn('resultados_permitidos', 'text', [
                'null' => true,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR,
                'after' => 'exige_resultado',
                'comment' => 'JSON com resultados esperados (Normal, Apto, etc.)',
            ]);
        }

        $table->update();

        $refreshed = $this->table('adms_sst_exames');
        if ($refreshed->hasColumn('codigo') && !$refreshed->hasIndexByName('uq_sst_exames_codigo')) {
            $this->table('adms_sst_exames')
                ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_sst_exames_codigo'])
                ->update();
        }

        $refreshed = $this->table('adms_sst_exames');
        if ($refreshed->hasColumn('tipo') && !$refreshed->hasIndexByName('idx_sst_exames_tipo')) {
            $this->table('adms_sst_exames')
                ->addIndex(['tipo'], ['name' => 'idx_sst_exames_tipo'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_exames')) {
            return;
        }

        $table = $this->table('adms_sst_exames');

        if ($table->hasIndexByName('idx_sst_exames_tipo')) {
            $table->removeIndexByName('idx_sst_exames_tipo');
        }
        if ($table->hasIndexByName('uq_sst_exames_codigo')) {
            $table->removeIndexByName('uq_sst_exames_codigo');
        }

        foreach (['resultados_permitidos', 'exige_resultado', 'validade_meses', 'possui_validade', 'tipo', 'codigo'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }

        $table->update();
    }
}
