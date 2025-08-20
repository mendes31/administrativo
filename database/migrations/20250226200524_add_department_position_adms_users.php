<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDepartmentPositionAdmsUsers extends AbstractMigration
{

    /** 
     * Adicionar as colunas department_id e position_id
     */
    public function up(): void
    {

        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {
            
            // Altera a tabela e adiciona as colunas department_id e position_id
            $table = $this->table('adms_users');


            $table->addColumn('user_department_id', 'integer', ['null' => false, 'signed' => false, 'after' => 'username' ]) // Indica que a coluna será criada após a coluna 'username'
                ->addForeignKey('user_department_id', 'adms_departments', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                ->addColumn('user_position_id', 'integer', ['null' => false, 'signed' => false, 'after' => 'user_department_id' ]) // Indica que a coluna será criada após a coluna 'department_id'
                ->addForeignKey('user_position_id', 'adms_positions', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                
                ->update();
        }
    }

    // Método down() para reverter a migração (caso necessário)
    public function down(): void
    {
        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {

            // Alterar a tabela e remover as colunas department_id e position_id
            $table = $this->table('adms_users');

            $table->removeColumn('user_department_id')
                ->removeColumn('user_position_id')
                ->update();
        }
    }
}
