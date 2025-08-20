<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsFrequency extends AbstractMigration
{
    /**
     * Cria a tabela AdmsCustomer.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_frequency` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_customer' não existe no banco de dados
        if (!$this->hasTable('adms_frequency')) {
            // Cria a tabela 'adms_frequency'
            $table = $this->table('adms_frequency');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['limit' => 25, 'null' => false])   //nome frequância
                ->addColumn('days', 'integer', ['null' => false])                   //nº de dias
                ->addColumn('created_at', 'timestamp')                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])     //data da atualização do registro
                ->addIndex(['name'], ['unique' => true, 'name' => 'idx_unique_name'])           // Adiciona índice único com o nome específico

                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_frequency.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_frequency` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_frequency' do banco de dados
        $this->table('adms_frequency')->drop()->save();
    }
}
