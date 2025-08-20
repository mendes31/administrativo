<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsPay extends AbstractMigration
{
    /**
     * Cria a tabela AdmsPay.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_pay` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_pay' não existe no banco de dados
        if (!$this->hasTable('adms_pay')) {
            // Cria a tabela 'adms_pay'
            $table = $this->table('adms_pay');

            // Define as colunas da tabela
            $table->addColumn('description', 'string', ['limit' => 255, 'null' => true])    //descrição da conta
                ->addColumn('num_doc', 'string', ['limit' => 50, 'null' => false])          //numero do documento (NF, boleto etc)
                ->addColumn('file', 'string', ['limit' => 255, 'null' => true])            //caminho para o arquivo
                ->addColumn('paid', 'boolean', ['default' => false, 'null' => false])       //pago (inicia com Não e só altera quando encerraro o pagamento)

                ->addColumn('partner_id', 'integer', ['null' => false])         //id parceiro de negócio
                ->addColumn('bank_id', 'integer', ['null' => false])            //id banco saída
                ->addColumn('cost_center_id', 'integer', ['null' => false])     //id centro de custo
                ->addColumn('user_pay_id', 'integer', ['null' => false])        //id dusuário pagamento
                ->addColumn('user_launch_id', 'integer', ['null' => false])     //id do usuário lançamento
                ->addColumn('frequency_id', 'integer', ['null' => false])       //id frequencia ou recorrência da conta
                ->addColumn('pay_method_id', 'integer', ['null' => false])      //id forma de pagamento
                ->addColumn('account_id', 'integer', ['null' => false])         //id plano de contas

                ->addColumn('value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])             //valor
                ->addColumn('original_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])    //valor original
                ->addColumn('total_value_old', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])   //valor total antigo
                ->addColumn('subtotal', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])          //subtotal
                ->addColumn('amount_paid', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])       //valor pago
                ->addColumn('discount_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])    //desconto
                ->addColumn('fine_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])        //multa
                ->addColumn('interest', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])          //juros   
                ->addColumn('residual_total', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])    //total residual
               

                ->addColumn('doc_date', 'timestamp', ['null' => false])                         //data do lançameto 
                ->addColumn('due_date', 'timestamp', ['null' => false])                         //data do vencimento
                ->addColumn('expected_date', 'timestamp', ['null' => true])                     //data prevista para pagamento - Pode ser NULL até o pagamento ser previsto
                ->addColumn('pay_date', 'timestamp', ['null' => true])                          //data do pagamento -Pode ser NULL até o pagamento ser feito

                ->addColumn('created_at', 'timestamp')                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])     //data da atualização do registro

                ->create();
        }
    }   

    /**
     * Reverte a criação da tabela adms_pay.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_pay` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_pay' do banco de dados
        $this->table('adms_pay')->drop()->save();
    }
}