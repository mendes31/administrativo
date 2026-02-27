<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela inv_item_bom para armazenar a lista de materiais (BOM)
 * de cada item de estoque (produto acabado / semiacabado).
 *
 * Migration segura:
 * - Só cria a tabela se ainda não existir.
 * - Usa FKs para inv_items.
 */
final class CreateInvItemBom extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_bom')) {
            $this->table('inv_item_bom')
                ->addColumn('inv_item_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'Item principal (produto que usa a ficha técnica)',
                ])
                ->addColumn('component_item_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'Componente (matéria-prima, embalagem, semiacabado)',
                ])
                ->addColumn('quantity_per_batch', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'null' => false,
                    'default' => 0,
                    'comment' => 'Quantidade do componente por lote padrão do item',
                ])
                ->addColumn('scrap_percent', 'decimal', [
                    'precision' => 7,
                    'scale' => 4,
                    'null' => false,
                    'default' => 0,
                    'comment' => 'Perda/rejeição percentual aplicada sobre a quantidade',
                ])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(
                    ['inv_item_id', 'component_item_id'],
                    ['name' => 'idx_inv_item_bom_unique_component', 'unique' => true]
                )
                ->addForeignKey(
                    'inv_item_id',
                    'inv_items',
                    'id',
                    ['delete' => 'CASCADE', 'update' => 'CASCADE']
                )
                ->addForeignKey(
                    'component_item_id',
                    'inv_items',
                    'id',
                    ['delete' => 'RESTRICT', 'update' => 'CASCADE']
                )
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_bom')) {
            $this->table('inv_item_bom')->drop()->save();
        }
    }
}

