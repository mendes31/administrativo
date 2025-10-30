<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImmediateSupervisorToUsers extends AbstractMigration
{
    /**
     * Adiciona o campo immediate_supervisor_id na tabela adms_users.
     * 
     * Este campo cria uma hierarquia de usuários, onde cada usuário
     * pode ter um supervisor imediato. Isso permite:
     * - Gerentes visualizarem dados de seus subordinados
     * - Relatórios filtrados por hierarquia
     * - Permissões baseadas em organograma real
     */
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            
            // Adicionar coluna immediate_supervisor_id
            $table->addColumn('immediate_supervisor_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'user_branch_id',
                'comment' => 'ID do supervisor/gerente imediato deste usuário'
            ])
            ->addForeignKey('immediate_supervisor_id', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE'
            ])
            ->update();
        }
    }

    /**
     * Remove o campo immediate_supervisor_id da tabela adms_users.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            
            // Remover foreign key primeiro
            if ($table->hasForeignKey('immediate_supervisor_id')) {
                $table->dropForeignKey('immediate_supervisor_id');
            }
            
            // Depois remover a coluna
            if ($table->hasColumn('immediate_supervisor_id')) {
                $table->removeColumn('immediate_supervisor_id');
            }
            
            $table->update();
        }
    }
}

