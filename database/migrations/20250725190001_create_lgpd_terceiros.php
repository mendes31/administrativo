<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdTerceiros extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_terceiros')) {
            $this->table('lgpd_terceiros', ['id' => 'id'])
                ->addColumn('empresa', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Nome da empresa'])
                ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria do terceiro'])
                ->addColumn('tipos_dados', 'string', ['limit' => 200, 'null' => true, 'comment' => 'Tipos de dados tratados'])
                ->addColumn('risco', 'enum', ['values' => ['Baixo', 'Médio', 'Alto'], 'default' => 'Médio', 'null' => false, 'comment' => 'Nível de risco'])
                ->addColumn('status', 'enum', ['values' => ['Aprovado', 'Pendente', 'Não Conforme'], 'default' => 'Pendente', 'null' => false, 'comment' => 'Status do terceiro'])
                ->addColumn('contato', 'string', ['limit' => 150, 'null' => true, 'comment' => 'E-mail de contato'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_terceiros')) {
            $this->table('lgpd_terceiros')->drop()->save();
        }
    }
} 