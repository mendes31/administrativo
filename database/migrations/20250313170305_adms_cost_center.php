<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsCostCenter extends AbstractMigration
{
    /**
     * Cria a tabela AdmsCostCenter.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_cost_center` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_cost_center' não existe no banco de dados
        if (!$this->hasTable('adms_cost_center')) {
            // Cria a tabela 'adms_cost_center'
            $table = $this->table('adms_cost_center');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['limit' => 100, 'null' => false])              //nome do centro d ecusto 
                ->addColumn('created_at', 'timestamp')                                          //data da criação do registro
                ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])     //data da atualização do registro
                // NOTA: Índice único não é criado aqui. A validação de unicidade é feita na aplicação PHP.
                ->addIndex(['name'], ['unique' => false, 'name' => 'idx_name']) // Índice não-único para performance

                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_cost_center.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_cost_center` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_cost_center' do banco de dados
        $this->table('adms_cost_center')->drop()->save();
    }
}
