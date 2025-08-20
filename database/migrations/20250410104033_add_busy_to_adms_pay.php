<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBusyToAdmsPay extends AbstractMigration
{

    /** 
     * Adicionar as colunas busy e user_temp
     */
    public function up(): void
    {

        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_pay')) {
            
            // Altera a tabela e adiciona as colunas busy e user_temp
            $table = $this->table('adms_pay');

            $table->addColumn('busy', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'account_id' // Indica que a coluna será criada após a coluna 'account_id'
            ])
            ->addColumn('user_temp', 'integer', [
                'null' => true,
                'after' => 'busy' // Indica que a coluna será criada após a coluna 'busy'
                ])
                ->update();
        }
    }

    // Método down() para reverter a migração (caso necessário)
    public function down(): void
    {
        // Acessa o IF quando a tabela existir no banco de dados
        if ($this->hasTable('adms_pay')) {

            // Alterar a tabela e remover as colunas busy e user_temp
            $table = $this->table('adms_pay');

            $table->removeColumn('busy')
                ->removeColumn('user_temp')
                ->update();
        }
    }
}