<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsPartialValue extends AbstractMigration
{
    /**
     * Cria a tabela AdmsPartialValue.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_partial_value` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_partial_value' não existe no banco de dados
        if (!$this->hasTable('adms_partial_value')) {
            // Cria a tabela 'adms_partial_value'
            $table = $this->table('adms_partial_value');

            // Define as colunas da tabela
            $table->addColumn('account_id', 'integer', ['null' => false])
                ->addColumn('type', 'string', ['null' => false])
                ->addColumn('partial_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])     //valor
                ->addColumn('user_id', 'integer', ['null' => false])                                            //id do usuário lançamento
                ->addColumn('created_at', 'timestamp')                                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])                     //data da atualização do registro

                ->create();

        }
    }

    /**
     * Reverte a criação da tabela adms_partial_value.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_partial_value` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_partial_value' do banco de dados
        $this->table('adms_partial_value')->drop()->save();
    }
}



