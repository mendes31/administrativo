<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Enriquecimento do catálogo TI para sistemas embarcados:
 * tag do equipamento, fabricante/modelo/série e filial de instalação.
 */
final class EnrichTiSistemasEquipamento extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('ti_sistemas')) {
            return;
        }

        $table = $this->table('ti_sistemas');

        if (!$table->hasColumn('equipamento_tag')) {
            $table->addColumn('equipamento_tag', 'string', [
                'limit' => 80,
                'null' => true,
                'after' => 'localizacao',
            ]);
        }
        if (!$table->hasColumn('fabricante')) {
            $table->addColumn('fabricante', 'string', [
                'limit' => 120,
                'null' => true,
                'after' => 'equipamento_tag',
            ]);
        }
        if (!$table->hasColumn('modelo')) {
            $table->addColumn('modelo', 'string', [
                'limit' => 120,
                'null' => true,
                'after' => 'fabricante',
            ]);
        }
        if (!$table->hasColumn('numero_serie')) {
            $table->addColumn('numero_serie', 'string', [
                'limit' => 120,
                'null' => true,
                'after' => 'modelo',
            ]);
        }
        if (!$table->hasColumn('adms_branch_id')) {
            $table->addColumn('adms_branch_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'numero_serie',
            ]);
        }

        $table->update();

        // Recarrega metadados após update das colunas.
        $table = $this->table('ti_sistemas');

        if (!$table->hasIndexByName('uq_ti_sistemas_equipamento_tag')) {
            $table->addIndex(['equipamento_tag'], [
                'unique' => true,
                'name' => 'uq_ti_sistemas_equipamento_tag',
            ]);
        }
        if (!$table->hasIndexByName('idx_ti_sistemas_branch')) {
            $table->addIndex(['adms_branch_id'], ['name' => 'idx_ti_sistemas_branch']);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('ti_sistemas')) {
            return;
        }

        $table = $this->table('ti_sistemas');
        if ($table->hasIndexByName('uq_ti_sistemas_equipamento_tag')) {
            $table->removeIndexByName('uq_ti_sistemas_equipamento_tag');
        }
        if ($table->hasIndexByName('idx_ti_sistemas_branch')) {
            $table->removeIndexByName('idx_ti_sistemas_branch');
        }
        $table->update();

        $table = $this->table('ti_sistemas');
        foreach (['adms_branch_id', 'numero_serie', 'modelo', 'fabricante', 'equipamento_tag'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
