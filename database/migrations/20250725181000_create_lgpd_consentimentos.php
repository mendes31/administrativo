<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdConsentimentos extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_consentimentos')) {
            $this->table('lgpd_consentimentos', ['id' => 'id'])
                ->addColumn('titular_nome', 'string', ['limit' => 150, 'null' => false, 'comment' => 'Nome do titular'])
                ->addColumn('titular_email', 'string', ['limit' => 150, 'null' => true, 'comment' => 'E-mail do titular'])
                ->addColumn('finalidade', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Finalidade do consentimento'])
                ->addColumn('canal', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Canal de coleta (site, app, etc)'])
                ->addColumn('data_consentimento', 'date', ['null' => false, 'comment' => 'Data do consentimento'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Revogado', 'Expirado'], 'default' => 'Ativo', 'null' => false, 'comment' => 'Status do consentimento'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['titular_email'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $this->table('lgpd_consentimentos')->drop()->save();
        }
    }
} 