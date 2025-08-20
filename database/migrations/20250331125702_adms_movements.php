<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsMovements extends AbstractMigration
{
    /**
     * Cria a tabela AdmsMovements.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_moviments` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_moviments' não existe no banco de dados
        if (!$this->hasTable('adms_movements')) {
            // Cria a tabela 'adms_movements'
            $table = $this->table('adms_movements');

            // Define as colunas da tabela
            $table
                ->addColumn('type', 'string', ['null' => false])                
                ->addColumn('movement', 'string', ['null' => false])
                ->addColumn('description', 'string', ['null' => false])
                ->addColumn('movement_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])    //valor
                ->addColumn('user_id', 'integer', ['null' => false])                                            //id do usuário lançamento
                ->addColumn('created_at', 'timestamp')                                                          //data da criação do registro
                ->addColumn('bank_id', 'integer', ['null' => false])                                            //Banco Saída
                ->addColumn('method_id', 'integer', ['null' => false])                                          //Forma de PGTO
                ->addColumn('movement_id', 'integer', ['null' => false])                                        //ID da conta 
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])                     //data da atualização do registro

                ->create();

        }
    }

    /**
     * Reverte a criação da tabela adms_movements.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_movements` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_movements' do banco de dados
        $this->table('adms_movements')->drop()->save();
    }
}