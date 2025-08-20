<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsActionPlans extends AbstractMigration
{
    /**
     * Cria a tabela adms_action_plans.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_action_plans` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - area_id: Área/setor responsável
     * - objective: Objetivo final
     * - what, why, where, who, how: Campos 5W2H
     * - when_start, when_end: Datas de início e fim
     * - how_much: Valor estimado
     * - status: Status do plano
     * - collaborator_comment, director_comment: Comentários
     * - created_by: Usuário criador
     * - created_at, updated_at: Timestamps
     *
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_action_plans')) {
            $table = $this->table('adms_action_plans');
            $table->addColumn('area_id', 'integer', ['null' => false])
                ->addColumn('objective', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('what', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('why', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('where', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('who', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('when_start', 'date', ['null' => false])
                ->addColumn('when_end', 'date', ['null' => false])
                ->addColumn('how', 'text', ['null' => false])
                ->addColumn('how_much', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'not_started'])
                ->addColumn('collaborator_comment', 'text', ['null' => true])
                ->addColumn('director_comment', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }
    }

    /**
     * Remove a tabela adms_action_plans.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_action_plans` do banco de dados.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('adms_action_plans')->drop()->save();
    }
} 