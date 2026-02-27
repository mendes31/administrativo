<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela inv_item_operations para armazenar a rota de produção
 * (sequência de operações) de cada item de estoque.
 *
 * Migration segura:
 * - Só cria a tabela se ainda não existir.
 * - Usa FKs para inv_items e inv_operations.
 */
final class CreateInvItemOperations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            $this->table('inv_item_operations')
                ->addColumn('inv_item_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'Item principal (produto que possui a rota)',
                ])
                ->addColumn('inv_operation_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'Operação de produção (ex.: Pesagem, Envase)',
                ])
                ->addColumn('sequence', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'default' => 1,
                    'comment' => 'Ordem de execução da operação na rota',
                ])
                ->addColumn('time_per_batch_hours', 'decimal', [
                    'precision' => 10,
                    'scale' => 4,
                    'null' => false,
                    'default' => 0,
                    'comment' => 'Tempo padrão da operação por lote (em horas)',
                ])
                ->addColumn('notes', 'text', [
                    'null' => true,
                    'comment' => 'Observações da operação para este item',
                ])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(
                    ['inv_item_id', 'sequence'],
                    ['name' => 'idx_inv_item_operations_sequence']
                )
                ->addForeignKey(
                    'inv_item_id',
                    'inv_items',
                    'id',
                    ['delete' => 'CASCADE', 'update' => 'CASCADE']
                )
                ->addForeignKey(
                    'inv_operation_id',
                    'inv_operations',
                    'id',
                    ['delete' => 'RESTRICT', 'update' => 'CASCADE']
                )
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_operations')) {
            $this->table('inv_item_operations')->drop()->save();
        }
    }
}

