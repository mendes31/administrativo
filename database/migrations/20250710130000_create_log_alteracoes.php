<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLogAlteracoes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_alteracoes')) {
            $table = $this->table('adms_log_alteracoes');
            $table->addColumn('tabela', 'string', ['limit' => 100])
                ->addColumn('objeto_id', 'integer')
                ->addColumn('usuario_id', 'integer')
                ->addColumn('data_alteracao', 'datetime')
                ->addColumn('tipo_operacao', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => true])
                ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('criado_por', 'integer')
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_log_alteracoes')->drop()->save();
    }
}

class CreateAdmsLogAcessos extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            $table = $this->table('adms_log_acessos', ['id' => true]);
            $table
                ->addColumn('usuario_id', 'integer', ['null' => false])
                ->addColumn('tipo_acesso', 'string', ['limit' => 20, 'null' => false, 'comment' => 'LOGIN ou LOGOUT'])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => false])
                ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('data_acesso', 'datetime', ['null' => false])
                ->addColumn('detalhes', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('criado_por', 'integer', ['null' => true])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['usuario_id'])
                ->create();
        }
    }
} 