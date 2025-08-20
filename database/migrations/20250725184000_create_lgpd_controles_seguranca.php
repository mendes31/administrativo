<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdControlesSeguranca extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_controles_seguranca')) {
            $this->table('lgpd_controles_seguranca', ['id' => 'id'])
                ->addColumn('controle', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Nome do controle'])
                ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria do controle'])
                ->addColumn('status', 'enum', ['values' => ['Implementado', 'Parcial', 'Em Implementação'], 'default' => 'Parcial', 'null' => false, 'comment' => 'Status do controle'])
                ->addColumn('conformidade', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Percentual de conformidade'])
                ->addColumn('risco', 'enum', ['values' => ['Baixo', 'Médio', 'Alto'], 'default' => 'Médio', 'null' => false, 'comment' => 'Nível de risco'])
                ->addColumn('ultima_auditoria', 'date', ['null' => true, 'comment' => 'Data da última auditoria'])
                ->addColumn('responsavel', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Responsável'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_controles_seguranca')) {
            $this->table('lgpd_controles_seguranca')->drop()->save();
        }
    }
} 