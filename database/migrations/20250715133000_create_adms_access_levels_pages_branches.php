<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsAccessLevelsPagesBranches extends AbstractMigration
{
    /**
     * Cria a tabela `adms_access_levels_pages_branches`.
     *
     * Esta tabela permite definir filiais específicas que um nível de acesso
     * pode acessar em uma determinada página, quando o escopo não é 'all'.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages_branches')) {
            $table = $this->table('adms_access_levels_pages_branches');
            
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
                'comment' => 'ID da filial permitida'
            ])
            ->addForeignKey('adms_branch_id', 'adms_branches', 'id', [
                'delete' => 'CASCADE', 
                'update' => 'CASCADE'
            ])
            
            ->addColumn('created_at', 'timestamp')
            ->addColumn('updated_at', 'timestamp')
            
            ->addIndex(['adms_access_level_id', 'adms_page_id', 'adms_branch_id'], [
                'unique' => true,
                'name' => 'unique_access_level_page_branch'
            ])
            ->create();
        }
    }

    /**
     * Remove a tabela `adms_access_levels_pages_branches`.
     */
    public function down(): void
    {
        $this->table('adms_access_levels_pages_branches')->drop()->save();
    }
} 