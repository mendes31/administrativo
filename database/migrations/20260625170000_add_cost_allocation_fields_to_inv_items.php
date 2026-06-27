<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Parâmetros de rateio CFIX no cadastro do item (critérios 4, 6 e 8).
 */
final class AddCostAllocationFieldsToInvItems extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');
        $changed = false;

        if (!$table->hasColumn('energy_class')) {
            $table->addColumn('energy_class', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'standard_batch_size',
                'comment' => 'Classe HVAC: CM, PROB, OTHER (critério 8)',
            ]);
            $changed = true;
        }
        if (!$table->hasColumn('complexity_level')) {
            $table->addColumn('complexity_level', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => 'media',
                'after' => 'energy_class',
                'comment' => 'Complexidade de produção: baixa, media, alta (critério 4)',
            ]);
            $changed = true;
        }
        if (!$table->hasColumn('production_line')) {
            $table->addColumn('production_line', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'complexity_level',
                'comment' => 'Linha de produção (referência)',
            ]);
            $changed = true;
        }

        if ($changed) {
            $table->update();
        }

        if ($this->hasTable('inv_cost_period_items')) {
            foreach (['energy_class', 'complexity_level', 'production_line'] as $field) {
                $this->execute(
                    "UPDATE inv_items i
                     SET i.{$field} = (
                         SELECT p.{$field}
                         FROM inv_cost_period_items p
                         WHERE p.inv_item_id = i.id
                           AND p.{$field} IS NOT NULL
                           AND TRIM(p.{$field}) <> ''
                         ORDER BY COALESCE(p.updated_at, p.created_at) DESC
                         LIMIT 1
                     )
                     WHERE (i.{$field} IS NULL OR TRIM(i.{$field}) = '')"
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_items')) {
            return;
        }

        $table = $this->table('inv_items');
        foreach (['production_line', 'complexity_level', 'energy_class'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
