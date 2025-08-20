<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsAccessLevelsPagesDepartments extends AbstractMigration
{
    /**
     * Cria a tabela `adms_access_levels_pages_departments`.
     *
     * Esta tabela permite definir departamentos específicos que um nível de acesso
     * pode acessar em uma determinada página, quando o escopo não é 'all'.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages_departments')) {
            $table = $this->table('adms_access_levels_pages_departments');
            
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
            
            ->addColumn('adms_department_id', 'integer', [
                'null' => false, 
                'signed' => false,
                'comment' => 'ID do departamento permitido'
            ])
            ->addForeignKey('adms_department_id', 'adms_departments', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('created_at', 'timestamp')
            ->addColumn('updated_at', 'timestamp')
            
            ->addIndex(['adms_access_level_id', 'adms_page_id', 'adms_department_id'], [
                'unique' => true,
                'name' => 'unique_access_level_page_department'
            ])
            ->create();
        }
    }

    /**
     * Remove a tabela `adms_access_levels_pages_departments`.
     */
    public function down(): void
    {
        $this->table('adms_access_levels_pages_departments')->drop()->save();
    }
} 