<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdDocumentos extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_documentos')) {
            $this->table('lgpd_documentos', ['id' => 'id'])
                ->addColumn('nome', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Nome do documento'])
                ->addColumn('tipo', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Tipo do documento (Política, Termo, Plano, etc)'])
                ->addColumn('arquivo', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Caminho/arquivo'])
                ->addColumn('versao', 'string', ['limit' => 20, 'null' => true, 'comment' => 'Versão do documento'])
                ->addColumn('publico', 'enum', ['values' => ['Colaboradores', 'Parceiros', 'Ambos'], 'default' => 'Colaboradores', 'null' => false, 'comment' => 'Público de acesso'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false, 'comment' => 'Status do documento'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_documentos')) {
            $this->table('lgpd_documentos')->drop()->save();
        }
    }
} 