<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImageAdmsUsers extends AbstractMigration
{

    /** 
     * Adicionar as colunas image
     */
    public function up(): void
    {

        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {
            
            // Altera a tabela e adiciona as colunas image
            $table = $this->table('adms_users');

            $table->addColumn('image', 'string', ['null' => true, 'after' => 'validate_recover_password']) // Coluna será criada após 'validate_recover_password'   

                ->update();
        }
    }

    // Método down() para reverter a migração (caso necessário)
    public function down(): void
    {
        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_users')) {

            // Alterar a tabela e remover as colunas image 
            $table = $this->table('adms_users');

            $table->removeColumn('image')
                
                ->update();
        }
    }
}

