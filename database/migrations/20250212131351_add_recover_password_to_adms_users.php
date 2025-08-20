<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRecoverPasswordToAdmsUsers extends AbstractMigration
{

    /** 
     * Adicionar as colunas recover_password e validate_recover_password
     */
    public function up(): void
    {

        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {
            
            // Altera a tabela e adiciona as colunas recover_password e validate_recover_password
            $table = $this->table('adms_users');

            $table->addColumn('recover_password', 'string', [
                'null' => true,
                'after' => 'password' // Indica que a coluna será criada após a coluna 'password'
            ])
                ->addColumn('validate_recover_password', 'datetime', [
                    'null' => true,
                    'after' => 'recover_password' // Indica que a coluna será criada após a coluna 'recover_password'
                ])
                ->update();
        }
    }

    // Método down() para reverter a migração (caso necessário)
    public function down(): void
    {
        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {

            // Alterar a tabela e remover as colunas recover_password e validate_recover_password
            $table = $this->table('adms_users');

            $table->removeColumn('recover_password')
                ->removeColumn('validate_recover_password')
                ->update();
        }
    }
}
