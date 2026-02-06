<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTwoStageApprovalToEmployeeRequests extends AbstractMigration
{
    public function up(): void
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
        
        // Adicionar índices apenas se não existirem
        try {
            $indexes = $this->query("SHOW INDEX FROM adms_employee_requests")->fetchAll();
            $hasManagerIndex = false;
            $hasHrIndex = false;
            
            foreach ($indexes as $index) {
                if ($index['Key_name'] === 'idx_manager_approved_by') {
                    $hasManagerIndex = true;
                }
                if ($index['Key_name'] === 'idx_hr_approved_by') {
                    $hasHrIndex = true;
                }
            }
            
            if (!$hasManagerIndex) {
                $table->addIndex(['manager_approved_by'], ['name' => 'idx_manager_approved_by'])->update();
            }
            
            if (!$hasHrIndex) {
                $table->addIndex(['hr_approved_by'], ['name' => 'idx_hr_approved_by'])->update();
            }
        } catch (\Exception $e) {
            // Se der erro ao verificar, tentar adicionar mesmo assim
            $table->addIndex(['manager_approved_by'], ['name' => 'idx_manager_approved_by'])->update();
            $table->addIndex(['hr_approved_by'], ['name' => 'idx_hr_approved_by'])->update();
        }
        
        // Adicionar foreign keys apenas se não existirem
        try {
            $constraints = $this->query("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'adms_employee_requests'
                AND CONSTRAINT_NAME IN ('fk_manager_approved_by', 'fk_hr_approved_by')
            ")->fetchAll();
            
            $hasManagerFk = false;
            $hasHrFk = false;
            
            foreach ($constraints as $constraint) {
                if ($constraint['CONSTRAINT_NAME'] === 'fk_manager_approved_by') {
                    $hasManagerFk = true;
                }
                if ($constraint['CONSTRAINT_NAME'] === 'fk_hr_approved_by') {
                    $hasHrFk = true;
                }
            }
            
            if (!$hasManagerFk) {
                $table->addForeignKey('manager_approved_by', 'adms_users', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_manager_approved_by'
                ])->update();
            }
            
            if (!$hasHrFk) {
                $table->addForeignKey('hr_approved_by', 'adms_users', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_hr_approved_by'
                ])->update();
            }
        } catch (\Exception $e) {
            // Se der erro, tentar adicionar mesmo assim
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
    
    public function down(): void
    {
        $table = $this->table('adms_employee_requests');
        
        // Remover foreign keys apenas se existirem
        try {
            $constraints = $this->query("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'adms_employee_requests'
                AND CONSTRAINT_NAME IN ('fk_manager_approved_by', 'fk_hr_approved_by')
            ")->fetchAll();
            
            foreach ($constraints as $constraint) {
                if ($constraint['CONSTRAINT_NAME'] === 'fk_manager_approved_by') {
                    $table->dropForeignKey('manager_approved_by')->update();
                }
                if ($constraint['CONSTRAINT_NAME'] === 'fk_hr_approved_by') {
                    $table->dropForeignKey('hr_approved_by')->update();
                }
            }
        } catch (\Exception $e) {
            // Ignorar se não existir
        }
        
        // Remover índices apenas se existirem
        try {
            $indexes = $this->query("SHOW INDEX FROM adms_employee_requests")->fetchAll();
            
            foreach ($indexes as $index) {
                if ($index['Key_name'] === 'idx_manager_approved_by') {
                    $table->removeIndex(['manager_approved_by'], ['name' => 'idx_manager_approved_by'])->update();
                }
                if ($index['Key_name'] === 'idx_hr_approved_by') {
                    $table->removeIndex(['hr_approved_by'], ['name' => 'idx_hr_approved_by'])->update();
                }
            }
        } catch (\Exception $e) {
            // Ignorar se não existir
        }
        
        // Remover colunas
        if ($table->hasColumn('requires_manager_approval')) {
            $table->removeColumn('requires_manager_approval')->update();
        }
        if ($table->hasColumn('hr_rejection_reason')) {
            $table->removeColumn('hr_rejection_reason')->update();
        }
        if ($table->hasColumn('hr_approved_at')) {
            $table->removeColumn('hr_approved_at')->update();
        }
        if ($table->hasColumn('hr_approved_by')) {
            $table->removeColumn('hr_approved_by')->update();
        }
        if ($table->hasColumn('manager_rejection_reason')) {
            $table->removeColumn('manager_rejection_reason')->update();
        }
        if ($table->hasColumn('manager_approved_at')) {
            $table->removeColumn('manager_approved_at')->update();
        }
        if ($table->hasColumn('manager_approved_by')) {
            $table->removeColumn('manager_approved_by')->update();
        }
    }
}

