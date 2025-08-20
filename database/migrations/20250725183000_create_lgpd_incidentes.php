<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdIncidentes extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_incidentes')) {
            $this->table('lgpd_incidentes', ['id' => 'id'])
                ->addColumn('titulo', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Título do incidente'])
                ->addColumn('descricao', 'string', ['limit' => 500, 'null' => true, 'comment' => 'Descrição detalhada'])
                ->addColumn('severidade', 'enum', ['values' => ['Baixo', 'Médio', 'Alto', 'Crítico'], 'default' => 'Médio', 'null' => false, 'comment' => 'Severidade'])
                ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria do incidente'])
                ->addColumn('registros_afetados', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Quantidade de registros afetados'])
                ->addColumn('data_reporte', 'date', ['null' => false, 'comment' => 'Data do reporte'])
                ->addColumn('status', 'enum', ['values' => ['Em Investigação', 'Resolvido', 'Notificado ANPD', 'Em Correção'], 'default' => 'Em Investigação', 'null' => false, 'comment' => 'Status do incidente'])
                ->addColumn('anpd', 'enum', ['values' => ['Sim', 'Não'], 'default' => 'Não', 'null' => false, 'comment' => 'Notificado à ANPD'])
                ->addColumn('responsavel', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Responsável'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_incidentes')) {
            $this->table('lgpd_incidentes')->drop()->save();
        }
    }
} 