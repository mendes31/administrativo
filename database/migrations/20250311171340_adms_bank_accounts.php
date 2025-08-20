<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsBankAccounts extends AbstractMigration
{
    /**
     * Cria a tabela AdmsBankAccounts.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_bank_accounts` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `id`: ID do log (não pode ser nulo)
     * - `date`: data
     * - `time`: Hora 
     * - `table`: Tabela  
     * - `action`: Ação 
     * - `adms_user_id`: Id do usuário 
     * - `id_reg`: Id do registro
     * - `description`: Descrição
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_bank_accounts' não existe no banco de dados
        if (!$this->hasTable('adms_bank_accounts')) {
            // Cria a tabela 'adms_bank_accounts'
            $table = $this->table('adms_bank_accounts');

            // Define as colunas da tabela
            $table->addColumn('bank_name', 'string', ['null' => false])
                ->addColumn('bank', 'string', ['null' => false])
                ->addColumn('account', 'string', ['null' => false])
                ->addColumn('agency', 'string', ['null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')

                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_bank_accounts.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_bank_accounts` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_bank_accounts' do banco de dados
        $this->table('adms_bank_accounts')->drop()->save();
    }
}
