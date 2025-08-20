<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsCustomer extends AbstractMigration
{
    /**
     * Cria a tabela AdmsCustomer.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_customer` no banco de dados.
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
        if (!$this->hasTable('adms_customer')) {
            // Cria a tabela 'adms_customer'
            $table = $this->table('adms_customer');

            // Define as colunas da tabela
            $table->addColumn('card_code', 'string', ['limit' => 6, 'null' => true])        //código do pn 
                ->addColumn('card_name', 'string', ['limit' => 255, 'null' => false])       //nome do pn
                ->addColumn('type_person', 'string', ['limit' => 10, 'null' => false])      //tipo de pessoa (fisica ou juridica)
                ->addColumn('doc', 'string', ['limit' => 45, 'null' => true])               //CPF ou CNPJ
                ->addColumn('phone', 'string', ['limit' => 45, 'null' => true])             //telefone
                ->addColumn('email', 'string', ['null' => true])                            //email
                ->addColumn('address', 'string', ['limit' => 255, 'null' => true])          //endereço
                ->addColumn('description', 'string', ['limit' => 255, 'null' => true])      //onservações
                ->addColumn('active', 'boolean', ['default' => false, 'null' => false])     //ativo (inicia com false)

                ->addColumn('date_birth', 'timestamp', ['null' => true])                        //data de nascimento
                ->addColumn('created_at', 'timestamp')                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])     //data da atualização do registro

                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_customer.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_customer` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_customer' do banco de dados
        $this->table('adms_customer')->drop()->save();
    }
}
