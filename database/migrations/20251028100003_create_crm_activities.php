<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmActivities extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('crm_activities');
        
        $table->addColumn('type', 'enum', ['values' => ['call', 'email', 'meeting', 'task', 'note']])
              ->addColumn('partner_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('opportunity_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('responsible_user_id', 'integer', ['signed' => false])
              
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              
              ->addColumn('scheduled_date', 'datetime', ['null' => true])
              ->addColumn('completed_date', 'datetime', ['null' => true])
              ->addColumn('duration_minutes', 'integer', ['null' => true, 'signed' => false])
              
              ->addColumn('status', 'enum', ['values' => ['Pendente', 'Concluída', 'Cancelada'], 'default' => 'Pendente'])
              ->addColumn('priority', 'enum', ['values' => ['Baixa', 'Média', 'Alta', 'Urgente'], 'default' => 'Média'])
              
              ->addColumn('outcome', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('outcome_notes', 'text', ['null' => true])
              
              ->addColumn('reminder_date', 'datetime', ['null' => true])
              ->addColumn('reminder_sent', 'boolean', ['default' => false])
              
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              ->addIndex(['type'])
              ->addIndex(['partner_id'])
              ->addIndex(['opportunity_id'])
              ->addIndex(['responsible_user_id'])
              ->addIndex(['status'])
              ->addIndex(['scheduled_date'])
              ->addIndex(['completed_date'])
              
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('opportunity_id', 'crm_opportunities', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('responsible_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

