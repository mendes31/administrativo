<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTwoStageApprovalToEmployeeRequests extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_employee_requests');
        
        // Adicionar campos para aprovação do gestor
        if (!$table->hasColumn('manager_approved_by')) {
            $table->addColumn('manager_approved_by', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'approved_by',
                'comment' => 'ID do gestor que aprovou'
            ])->update();
        }
        
        if (!$table->hasColumn('manager_approved_at')) {
            $table->addColumn('manager_approved_at', 'datetime', [
                'null' => true,
                'after' => 'manager_approved_by',
                'comment' => 'Data de aprovação do gestor'
            ])->update();
        }
        
        if (!$table->hasColumn('manager_rejection_reason')) {
            $table->addColumn('manager_rejection_reason', 'text', [
                'null' => true,
                'after' => 'rejection_reason',
                'comment' => 'Motivo da rejeição pelo gestor'
            ])->update();
        }
        
        // Adicionar campos para aprovação do RH
        if (!$table->hasColumn('hr_approved_by')) {
            $table->addColumn('hr_approved_by', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'manager_approved_at',
                'comment' => 'ID do RH que aprovou'
            ])->update();
        }
        
        if (!$table->hasColumn('hr_approved_at')) {
            $table->addColumn('hr_approved_at', 'datetime', [
                'null' => true,
                'after' => 'hr_approved_by',
                'comment' => 'Data de aprovação do RH'
            ])->update();
        }
        
        if (!$table->hasColumn('hr_rejection_reason')) {
            $table->addColumn('hr_rejection_reason', 'text', [
                'null' => true,
                'after' => 'manager_rejection_reason',
                'comment' => 'Motivo da rejeição pelo RH'
            ])->update();
        }
        
        // Adicionar campo para indicar se precisa de aprovação do gestor
        if (!$table->hasColumn('requires_manager_approval')) {
            $table->addColumn('requires_manager_approval', 'boolean', [
                'default' => true,
                'after' => 'request_type',
                'comment' => 'Se requer aprovação do gestor antes do RH'
            ])->update();
        }
        
        // Adicionar índices
        $table->addIndex(['manager_approved_by'], ['name' => 'idx_manager_approved_by'])->update();
        $table->addIndex(['hr_approved_by'], ['name' => 'idx_hr_approved_by'])->update();
        
        // Adicionar foreign keys
        $table->addForeignKey('manager_approved_by', 'adms_users', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'NO_ACTION',
            'constraint' => 'fk_manager_approved_by'
        ])->update();
        
        $table->addForeignKey('hr_approved_by', 'adms_users', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'NO_ACTION',
            'constraint' => 'fk_hr_approved_by'
        ])->update();
    }
}

