<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsAccountsPlan extends AbstractMigration
{
    /**
     * Cria a tabela AdmsChartAccounts.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_accounts_plan` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_accounts_plan' não existe no banco de dados
        if (!$this->hasTable('adms_accounts_plan')) {
            // Cria a tabela 'adms_accounts_plan'
            $table = $this->table('adms_accounts_plan');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['limit' => 255, 'null' => false])              //nome do plano de contas
                ->addColumn('account', 'string', ['limit' => 50, 'null' => true])               //nº da conta do plano de contas
                ->addColumn('created_at', 'timestamp')                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])     //data da atualização do registro
                // NOTA: Índices únicos não são criados aqui porque:
                // - 'name' é VARCHAR(255) com utf8mb4 = 1020 bytes > 767 bytes (limite MySQL)
                // - A validação de unicidade é feita na aplicação PHP.
                ->addIndex(['name'], ['unique' => false, 'name' => 'idx_name']) // Índice não-único para performance
                ->addIndex(['account'], ['unique' => false, 'name' => 'idx_account']) // Índice não-único para performance

                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_accounts_plan.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_accounts_plan` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_accounts_plan' do banco de dados
        $this->table('adms_accounts_plan')->drop()->save();
    }
}
