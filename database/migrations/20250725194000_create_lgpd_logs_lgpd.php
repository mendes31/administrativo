<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdLogsLgpd extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_logs_lgpd')) {
            $this->table('lgpd_logs_lgpd', ['id' => 'id'])
                ->addColumn('tabela', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Tabela afetada'])
                ->addColumn('objeto_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do registro'])
                ->addColumn('usuario_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Usuário responsável'])
                ->addColumn('tipo_operacao', 'enum', ['values' => ['INSERT', 'UPDATE', 'DELETE'], 'default' => 'UPDATE', 'null' => false, 'comment' => 'Tipo de operação'])
                ->addColumn('dados_antes', 'text', ['null' => true, 'comment' => 'Dados antes da alteração (JSON)'])
                ->addColumn('dados_depois', 'text', ['null' => true, 'comment' => 'Dados depois da alteração (JSON)'])
                ->addColumn('data_alteracao', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => true, 'comment' => 'IP do usuário'])
                ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true, 'comment' => 'User agent'])
                ->addColumn('criado_por', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Usuário que criou o log'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_logs_lgpd')) {
            $this->table('lgpd_logs_lgpd')->drop()->save();
        }
    }
} 