<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmAutomations extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de automações
        $table = $this->table('crm_automations');
        
        $table->addColumn('name', 'string', ['limit' => 255, 'comment' => 'Nome da automação'])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('entity_type', 'enum', [
                  'values' => ['partner', 'opportunity', 'activity'],
                  'comment' => 'Entidade que dispara a automação'
              ])
              ->addColumn('trigger_event', 'enum', [
                  'values' => ['created', 'updated', 'deleted', 'stage_changed', 'status_changed', 'date_reached'],
                  'comment' => 'Evento que dispara'
              ])
              ->addColumn('trigger_conditions', 'text', ['null' => true, 'comment' => 'Condições JSON'])
              ->addColumn('action_type', 'enum', [
                  'values' => ['send_email', 'send_whatsapp', 'create_activity', 'create_note', 'update_field', 'send_notification'],
                  'comment' => 'Tipo de ação'
              ])
              ->addColumn('action_config', 'text', ['null' => true, 'comment' => 'Configuração da ação JSON'])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('priority', 'integer', ['default' => 0, 'comment' => 'Ordem de execução'])
              ->addColumn('execution_count', 'integer', ['default' => 0, 'signed' => false, 'comment' => 'Contador'])
              ->addColumn('last_execution', 'datetime', ['null' => true])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'datetime', ['null' => true])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addIndex(['entity_type', 'trigger_event'])
              ->addIndex(['is_active'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->create();

        // Tabela de logs de execução de automações
        $table = $this->table('crm_automation_logs');
        
        $table->addColumn('automation_id', 'integer', ['signed' => false])
              ->addColumn('entity_type', 'string', ['limit' => 50])
              ->addColumn('entity_id', 'integer', ['signed' => false])
              ->addColumn('status', 'enum', ['values' => ['success', 'failed', 'skipped']])
              ->addColumn('error_message', 'text', ['null' => true])
              ->addColumn('execution_data', 'text', ['null' => true, 'comment' => 'Dados da execução JSON'])
              ->addColumn('executed_at', 'datetime', ['null' => true])
              ->addForeignKey('automation_id', 'crm_automations', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['automation_id'])
              ->addIndex(['entity_type', 'entity_id'])
              ->addIndex(['executed_at'])
              ->create();
    }
}

