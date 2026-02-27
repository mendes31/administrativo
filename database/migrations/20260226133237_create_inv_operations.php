<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela inv_operations para armazenar operações de produção
 * (ex.: Pesagem, Mistura, Envase), que serão usadas nas rotas dos itens.
 *
 * Migration segura:
 * - Só cria a tabela se ainda não existir.
 * - O down() só tenta dropar se a tabela existir.
 */
final class CreateInvOperations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_operations')) {
            $this->table('inv_operations')
                ->addColumn('code', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'comment' => 'Código curto da operação (opcional)',
                ])
                ->addColumn('name', 'string', [
                    'limit' => 120,
                    'null' => false,
                    'comment' => 'Nome da operação (ex.: Pesagem, Envase)',
                ])
                ->addColumn('description', 'text', [
                    'null' => true,
                    'comment' => 'Descrição detalhada da operação',
                ])
                ->addColumn('default_cost_per_hour', 'decimal', [
                    'precision' => 15,
                    'scale' => 6,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Custo/hora padrão para cálculos de custo (informado manualmente na V1)',
                ])
                ->addColumn('active', 'boolean', [
                    'default' => 1,
                    'null' => false,
                ])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['code'], [
                    'unique' => true,
                    'name' => 'idx_inv_operations_code_unique',
                ])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_operations')) {
            $this->table('inv_operations')->drop()->save();
        }
    }
}

