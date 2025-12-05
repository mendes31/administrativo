<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmployeePortalTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Solicitações do Colaborador
        if (!$this->hasTable('adms_employee_requests')) {
            $table = $this->table('adms_employee_requests', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador'])
                ->addColumn('request_type', 'string', ['limit' => 50, 'comment' => 'Tipo: vacation, time_off, document, salary_advance, other'])
                ->addColumn('title', 'string', ['limit' => 255, 'comment' => 'Título da solicitação'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição'])
                ->addColumn('start_date', 'date', ['null' => true, 'comment' => 'Data de início (para férias/afastamento)'])
                ->addColumn('end_date', 'date', ['null' => true, 'comment' => 'Data de término'])
                ->addColumn('days_requested', 'integer', ['null' => true, 'comment' => 'Dias solicitados'])
                ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Valor (para adiantamento)'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, approved, rejected, cancelled'])
                ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID de quem aprovou'])
                ->addColumn('approved_at', 'datetime', ['null' => true, 'comment' => 'Data de aprovação'])
                ->addColumn('rejection_reason', 'text', ['null' => true, 'comment' => 'Motivo da rejeição'])
                ->addColumn('attachments', 'text', ['null' => true, 'comment' => 'JSON com anexos'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['employee_id'])
                ->addIndex(['request_type'])
                ->addIndex(['status'])
                ->addIndex(['start_date', 'end_date'])
                ->addIndex(['approved_by'])
                
                ->addForeignKey('employee_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('approved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Chamados/Tickets
        if (!$this->hasTable('adms_employee_tickets')) {
            $table = $this->table('adms_employee_tickets', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador'])
                ->addColumn('ticket_type', 'string', ['limit' => 50, 'comment' => 'Tipo: hr, it, facilities, payroll, other'])
                ->addColumn('priority', 'string', ['limit' => 20, 'default' => 'medium', 'comment' => 'Prioridade: low, medium, high, urgent'])
                ->addColumn('title', 'string', ['limit' => 255, 'comment' => 'Título do chamado'])
                ->addColumn('description', 'text', ['null' => false, 'comment' => 'Descrição do problema'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'open', 'comment' => 'Status: open, in_progress, resolved, closed'])
                ->addColumn('assigned_to', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID de quem está responsável'])
                ->addColumn('department', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Departamento responsável'])
                ->addColumn('resolution', 'text', ['null' => true, 'comment' => 'Resolução do chamado'])
                ->addColumn('resolved_at', 'datetime', ['null' => true, 'comment' => 'Data de resolução'])
                ->addColumn('resolved_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID de quem resolveu'])
                ->addColumn('attachments', 'text', ['null' => true, 'comment' => 'JSON com anexos'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['employee_id'])
                ->addIndex(['ticket_type'])
                ->addIndex(['status'])
                ->addIndex(['priority'])
                ->addIndex(['assigned_to'])
                ->addIndex(['created_at'])
                
                ->addForeignKey('employee_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('assigned_to', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('resolved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Histórico de Chamados (comentários/atualizações)
        if (!$this->hasTable('adms_employee_ticket_history')) {
            $table = $this->table('adms_employee_ticket_history', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('ticket_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do chamado'])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que fez a atualização'])
                ->addColumn('action', 'string', ['limit' => 50, 'comment' => 'Ação: comment, status_change, assignment, resolution'])
                ->addColumn('old_value', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Valor anterior'])
                ->addColumn('new_value', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Valor novo'])
                ->addColumn('comment', 'text', ['null' => true, 'comment' => 'Comentário'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['ticket_id'])
                ->addIndex(['user_id'])
                ->addIndex(['action'])
                ->addIndex(['created_at'])
                
                ->addForeignKey('ticket_id', 'adms_employee_tickets', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}

