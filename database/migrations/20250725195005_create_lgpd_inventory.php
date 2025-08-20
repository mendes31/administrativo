<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdInventory extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_inventory')) {
            $this->table('lgpd_inventory', ['id' => 'id'])
                ->addColumn('area', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Ex: Vendas, RH, Marketing'])
                ->addColumn('data_type', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Ex: Nome, CPF, Salário'])
                ->addColumn('data_category', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Ex: Dado pessoal, Dado sensível'])
                ->addColumn('data_subject', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Titular: Cliente, Funcionário'])
                ->addColumn('storage_location', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Onde está armazenado'])
                ->addColumn('access_level', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Quem tem acesso'])
                ->addColumn('risk_level', 'enum', ['values' => ['Alto','Médio','Baixo'], 'default' => 'Médio', 'null' => false, 'comment' => 'Nível de risco'])
                ->addColumn('department_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Departamento responsável'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('department_id', 'adms_departments', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addIndex(['area'])
                ->addIndex(['data_category'])
                ->addIndex(['risk_level'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_inventory')) {
            $this->table('lgpd_inventory')->drop()->save();
        }
    }
}