<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsBankTransfers extends AbstractMigration
{
    public function up(): void
{
    // Verifica se a tabela 'adms_bank_transfers' não existe no banco de dados
    if (!$this->hasTable('adms_bank_transfers')) {
        // Cria a tabela 'adms_bank_transfers'
        $table = $this->table('adms_bank_transfers');

        // Define as colunas da tabela
        $table->addColumn('origin_id', 'integer', ['signed' => false, 'null' => false])
              ->addColumn('destination_id', 'integer', ['signed' => false, 'null' => false])
              ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
              ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'default' => null]) // <-- NOVA COLUNA
              ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])

              // Define as chaves estrangeiras
              ->addForeignKey('origin_id', 'adms_bank_accounts', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
              ->addForeignKey('destination_id', 'adms_bank_accounts', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])

              // Cria a tabela
              ->create();
    }
}


    /**
    * Reverte a criação da tabela adms_bank_transfers.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_bank_transfers` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_bank_transfers' do banco de dados
        $this->table('adms_bank_transfers')->drop()->save();
    }
}
