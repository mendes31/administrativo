<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWorkflowFieldsToAdmsStrategicPlans extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_strategic_plans')) {
            $table = $this->table('adms_strategic_plans');
            
            if (!$table->hasColumn('workflow_status')) {
                $table->addColumn('workflow_status', 'enum', [
                    'values' => ['draft', 'submitted', 'approved', 'in_progress', 'completed', 'rejected'],
                    'default' => 'draft',
                    'null' => false,
                    'after' => 'progress_percentage',
                    'comment' => 'Status do workflow de aprovação'
                ])->update();
            }
            
            if (!$table->hasColumn('submitted_at')) {
                $table->addColumn('submitted_at', 'datetime', [
                    'null' => true,
                    'after' => 'workflow_status',
                    'comment' => 'Data de submissão para aprovação'
                ])->update();
            }
            
            if (!$table->hasColumn('approved_at')) {
                $table->addColumn('approved_at', 'datetime', [
                    'null' => true,
                    'after' => 'submitted_at',
                    'comment' => 'Data de aprovação'
                ])->update();
            }
            
            if (!$table->hasColumn('approved_by')) {
                $table->addColumn('approved_by', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'approved_at',
                    'comment' => 'ID do usuário que aprovou'
                ])->update();
                
                $table->addForeignKey('approved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
            }
            
            if (!$table->hasColumn('approval_notes')) {
                $table->addColumn('approval_notes', 'text', [
                    'null' => true,
                    'after' => 'approved_by',
                    'comment' => 'Notas da aprovação'
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_strategic_plans')) {
            $table = $this->table('adms_strategic_plans');
            
            if ($table->hasColumn('approval_notes')) {
                $table->removeColumn('approval_notes')->update();
            }
            
            if ($table->hasColumn('approved_by')) {
                $table->removeColumn('approved_by')->update();
            }
            
            if ($table->hasColumn('approved_at')) {
                $table->removeColumn('approved_at')->update();
            }
            
            if ($table->hasColumn('submitted_at')) {
                $table->removeColumn('submitted_at')->update();
            }
            
            if ($table->hasColumn('workflow_status')) {
                $table->removeColumn('workflow_status')->update();
            }
        }
    }
}



