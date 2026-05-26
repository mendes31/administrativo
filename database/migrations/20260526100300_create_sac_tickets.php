<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacTickets extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_tickets')) {
            return;
        }

        $table = $this->table('sac_tickets');

        $table->addColumn('code', 'string', ['limit' => 20])
              ->addColumn('subject', 'string', ['limit' => 255])
              ->addColumn('description', 'text')
              ->addColumn('client_id', 'integer', ['signed' => false])
              ->addColumn('category_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('priority', 'enum', ['values' => ['Baixa', 'Média', 'Alta', 'Urgente'], 'default' => 'Média'])
              ->addColumn('channel', 'enum', ['values' => ['WhatsApp', 'E-mail', 'Telefone', 'Portal'], 'default' => 'Portal'])
              ->addColumn('status', 'enum', ['values' => ['Aberto', 'Em análise', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'], 'default' => 'Aberto'])
              ->addColumn('assigned_user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('department_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('sla_response_deadline', 'datetime', ['null' => true])
              ->addColumn('sla_resolution_deadline', 'datetime', ['null' => true])
              ->addColumn('first_response_at', 'datetime', ['null' => true])
              ->addColumn('resolved_at', 'datetime', ['null' => true])
              ->addColumn('closed_at', 'datetime', ['null' => true])
              ->addColumn('sla_response_breached', 'boolean', ['default' => 0])
              ->addColumn('sla_resolution_breached', 'boolean', ['default' => 0])
              ->addColumn('satisfaction_rating', 'integer', ['null' => true, 'signed' => false, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
              ->addColumn('satisfaction_comment', 'text', ['null' => true])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['code'], ['unique' => true])
              ->addIndex(['client_id'])
              ->addIndex(['category_id'])
              ->addIndex(['priority'])
              ->addIndex(['status'])
              ->addIndex(['assigned_user_id'])
              ->addIndex(['department_id'])
              ->addIndex(['sla_response_deadline'])
              ->addIndex(['sla_resolution_deadline'])
              ->addIndex(['created_at'])

              ->addForeignKey('client_id', 'sac_clients', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('category_id', 'sac_categories', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('assigned_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
