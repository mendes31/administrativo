<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBranchIdToAdmsUsers extends AbstractMigration
{
    /**
     * Adiciona o campo branch_id na tabela adms_users.
     * 
     * Este campo vincula o usuário à sua filial principal.
     */
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            $table->addColumn('user_branch_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'user_position_id',
                'comment' => 'ID da filial principal do usuário'
            ])
            ->addForeignKey('user_branch_id', 'adms_branches', 'id', [
                'delete' => 'RESTRICT', 
                'update' => 'CASCADE'
            ])
            ->update();
        }
    }

    /**
     * Remove o campo branch_id da tabela adms_users.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            $table->removeColumn('user_branch_id')->update();
        }
    }
} 