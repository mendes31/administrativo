<?php

use Phinx\Migration\AbstractMigration;

class CreateLgpdTermos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('lgpd_termos')) {
            $this->table('lgpd_termos', ['id' => 'id'])
                ->addColumn('versao', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Versão do termo'])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título do termo'])
                ->addColumn('tipo', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Tipo de termo (login, site, etc.)'])
                ->addColumn('conteudo', 'text', ['null' => false, 'comment' => 'Conteúdo completo do termo (HTML ou texto)'])
                ->addColumn('data_inicio_vigencia', 'datetime', ['null' => false, 'comment' => 'Início da vigência'])
                ->addColumn('data_fim_vigencia', 'datetime', ['null' => true, 'default' => null, 'comment' => 'Fim da vigência (opcional)'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['tipo', 'versao'])
                ->addIndex(['status'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_termos')) {
            $this->table('lgpd_termos')->drop()->save();
        }
    }
}


