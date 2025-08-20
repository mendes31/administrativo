<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddScopeToAdmsAccessLevelsPages extends AbstractMigration
{
    /**
     * Adiciona os campos de escopo na tabela adms_access_levels_pages.
     * 
     * Estes campos definem o tipo de controle de escopo para usuários com níveis de acesso específicos:
     * - department_scope: tipo de controle de departamento (all, own, specific)
     * - branch_scope: tipo de controle de filial (all, own, specific)
     * 
     * Quando o escopo é 'specific', os relacionamentos são definidos nas tabelas:
     * - adms_access_levels_pages_departments (departamentos específicos)
     * - adms_access_levels_pages_branches (filiais específicas)
     * - adms_access_levels_pages_branch_departments (combinações filial+departamento específicas)
     */
    public function up(): void
    {
        if ($this->hasTable('adms_access_levels_pages')) {
            $table = $this->table('adms_access_levels_pages');
            
            // Adiciona campo para controle de escopo por departamento
            $table->addColumn('department_scope', 'enum', [
                'values' => ['all', 'own', 'specific'],
                'default' => 'all',
                'after' => 'permission',
                'null' => false,
                'comment' => 'Escopo de departamento: all - todos, own - próprio, specific - específicos',
            ]);
            
            // Adiciona campo para controle de escopo por filial
            $table->addColumn('branch_scope', 'enum', [
                'values' => ['all', 'own', 'specific'],
                'default' => 'all',
                'after' => 'department_scope',
                'null' => false,
                'comment' => 'Escopo de filial: all - todas, own - própria, specific - específicas',
            ]);
            
            // Adiciona campo para controle de combinações específicas
            $table->addColumn('use_branch_department_combinations', 'boolean', [
                'default' => 0,
                'after' => 'branch_scope',
                'null' => false,
                'comment' => 'Usar combinações específicas filial+departamento: 1 - sim, 0 - não',
            ])->update();
        }
    }

    /**
     * Remove os campos de escopo da tabela adms_access_levels_pages.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_access_levels_pages')) {
            $table = $this->table('adms_access_levels_pages');
            $table->removeColumn('use_branch_department_combinations')->update();
            $table->removeColumn('branch_scope')->update();
            $table->removeColumn('department_scope')->update();
        }
    }
} 