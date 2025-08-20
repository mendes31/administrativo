<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsAccessLevelsPagesBranchDepartments extends AbstractMigration
{
    /**
     * Cria a tabela `adms_access_levels_pages_branch_departments`.
     *
     * Esta tabela permite definir combinações específicas de filial + departamento
     * que um nível de acesso pode acessar em uma determinada página.
     * 
     * Exemplo: Usuário pode acessar:
     * - Filial 1 + Departamento 1
     * - Filial 1 + Departamento 2  
     * - Filial 2 + Departamento 3
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages_branch_departments')) {
            $table = $this->table('adms_access_levels_pages_branch_departments');
            
            $table->addColumn('adms_access_level_id', 'integer', [
                'null' => false, 
                'signed' => false,
                'comment' => 'ID do nível de acesso'
            ])
            ->addForeignKey('adms_access_level_id', 'adms_access_levels', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('adms_page_id', 'integer', [
                'null' => false, 
                'signed' => false,
                'comment' => 'ID da página'
            ])
            ->addForeignKey('adms_page_id', 'adms_pages', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('adms_branch_id', 'integer', [
                'null' => false, 
                'signed' => false,
                'comment' => 'ID da filial'
            ])
            ->addForeignKey('adms_branch_id', 'adms_branches', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('adms_department_id', 'integer', [
                'null' => false, 
                'signed' => false,
                'comment' => 'ID do departamento'
            ])
            ->addForeignKey('adms_department_id', 'adms_departments', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('created_at', 'timestamp')
            ->addColumn('updated_at', 'timestamp')
            
            ->addIndex(['adms_access_level_id', 'adms_page_id', 'adms_branch_id', 'adms_department_id'], [
                'unique' => true,
                'name' => 'unique_access_level_page_branch_department'
            ])
            ->create();
        }
    }

    /**
     * Remove a tabela `adms_access_levels_pages_branch_departments`.
     */
    public function down(): void
    {
        $this->table('adms_access_levels_pages_branch_departments')->drop()->save();
    }
} 