<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Linhas manuais na BOM para itens PA - PROJETO (componentes sem cadastro no estoque).
 */
final class ExtendInvItemBomManualLines extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_bom')) {
            return;
        }

        $table = $this->table('inv_item_bom');

        if (!$table->hasColumn('line_source')) {
            $table->addColumn('line_source', 'string', [
                'limit' => 20,
                'default' => 'catalog',
                'null' => false,
                'comment' => 'catalog = item cadastrado; manual = linha hipotética (PA projeto)',
            ]);
        }

        if (!$table->hasColumn('manual_description')) {
            $table->addColumn('manual_description', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Descrição livre quando line_source = manual',
            ]);
        }

        if (!$table->hasColumn('manual_component_type')) {
            $table->addColumn('manual_component_type', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'MP, EMB ou OTHER — classificação CVAR',
            ]);
        }

        if (!$table->hasColumn('manual_unit')) {
            $table->addColumn('manual_unit', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'Unidade informada na linha manual',
            ]);
        }

        if (!$table->hasColumn('manual_unit_cost')) {
            $table->addColumn('manual_unit_cost', 'decimal', [
                'precision' => 15,
                'scale' => 6,
                'null' => true,
                'comment' => 'Custo unitário informado na linha manual',
            ]);
        }

        $table->update();

        // component_item_id passa a ser opcional para linhas manuais
        $this->execute(
            'ALTER TABLE inv_item_bom MODIFY component_item_id INT UNSIGNED NULL'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_item_bom')) {
            return;
        }

        // Remove linhas manuais antes de restaurar NOT NULL
        $this->execute("DELETE FROM inv_item_bom WHERE line_source = 'manual' OR component_item_id IS NULL");

        $this->execute(
            'ALTER TABLE inv_item_bom MODIFY component_item_id INT UNSIGNED NOT NULL'
        );

        $table = $this->table('inv_item_bom');
        foreach (['manual_unit_cost', 'manual_unit', 'manual_component_type', 'manual_description', 'line_source'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col)->update();
            }
        }
    }
}
