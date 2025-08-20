<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdAuditorias extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_auditorias')) {
            $this->table('lgpd_auditorias', ['id' => 'id'])
                ->addColumn('tipo', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Tipo de auditoria'])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Descrição da auditoria'])
                ->addColumn('data', 'date', ['null' => false, 'comment' => 'Data da auditoria'])
                ->addColumn('responsavel', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Responsável'])
                ->addColumn('resultado', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Resultado (Aprovado, Pendências, etc)'])
                ->addColumn('documento', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Arquivo do relatório'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_auditorias')) {
            $this->table('lgpd_auditorias')->drop()->save();
        }
    }
} 