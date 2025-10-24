<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para adicionar campos de cancelamento nas atribuições
 */
final class AlterAdmsEvaluationAssignmentsAddCancelamento extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_evaluation_assignments');
        
        // Data/hora do cancelamento
        if (!$table->hasColumn('cancelado_em')) {
            $table->addColumn('cancelado_em', 'datetime', [
                'null' => true,
                'after' => 'updated_at',
                'comment' => 'Data/hora em que foi cancelado'
            ]);
        }
        
        // Quem cancelou
        if (!$table->hasColumn('cancelado_por')) {
            $table->addColumn('cancelado_por', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'cancelado_em',
                'comment' => 'ID do usuário que cancelou'
            ]);
        }
        
        // Motivo do cancelamento
        if (!$table->hasColumn('motivo_cancelamento')) {
            $table->addColumn('motivo_cancelamento', 'text', [
                'null' => true,
                'after' => 'cancelado_por',
                'comment' => 'Motivo/justificativa do cancelamento'
            ]);
        }
        
        // Adicionar índice para cancelado_em
        if (!$table->hasIndex(['cancelado_em'])) {
            $table->addIndex(['cancelado_em'], ['name' => 'idx_assignment_cancelado']);
        }
        
        // Foreign key para cancelado_por
        if (!$table->hasForeignKey('cancelado_por')) {
            $table->addForeignKey('cancelado_por', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION'
            ]);
        }
        
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_evaluation_assignments');
        
        // Remover foreign key primeiro
        if ($table->hasForeignKey('cancelado_por')) {
            $table->dropForeignKey('cancelado_por');
        }
        
        // Remover índice
        if ($table->hasIndex(['cancelado_em'])) {
            $table->removeIndex(['cancelado_em']);
        }
        
        // Remover colunas
        $columns = ['cancelado_em', 'cancelado_por', 'motivo_cancelamento'];
        foreach ($columns as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        
        $table->update();
    }
}

