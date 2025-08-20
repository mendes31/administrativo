<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdContratosTerceiros extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_contratos_terceiros')) {
            $this->table('lgpd_contratos_terceiros', ['id' => 'id'])
                ->addColumn('terceiro_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do terceiro'])
                ->addColumn('contrato', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Nome/identificação do contrato'])
                ->addColumn('status', 'enum', ['values' => ['DPA Assinado', 'Em Negociação', 'Sem DPA'], 'default' => 'Em Negociação', 'null' => false, 'comment' => 'Status do contrato'])
                ->addColumn('risco', 'enum', ['values' => ['Baixo', 'Médio', 'Alto'], 'default' => 'Médio', 'null' => false, 'comment' => 'Nível de risco'])
                ->addColumn('vencimento', 'date', ['null' => true, 'comment' => 'Data de vencimento'])
                ->addColumn('documento', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Arquivo do contrato'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('terceiro_id', 'lgpd_terceiros', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_contratos_terceiros')) {
            $this->table('lgpd_contratos_terceiros')->drop()->save();
        }
    }
} 