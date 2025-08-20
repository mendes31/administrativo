<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBalanceToAdmsBankAccounts extends AbstractMigration
{
    /**
     * Adiciona o campo 'balance' à tabela 'adms_bank_accounts'.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('adms_bank_accounts');
        $table->addColumn('balance', 'decimal', [
            'precision' => 15,
            'scale' => 2,
            'default' => 0.00,
            'null' => false,
            'after' => 'agency' // ajusta a posição, se necessário
        ])->update();
    }

    /**
     * Remove o campo 'balance' da tabela 'adms_bank_accounts'.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('adms_bank_accounts');
        $table->removeColumn('balance')->update();
    }
    
}
