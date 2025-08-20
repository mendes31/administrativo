<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTypeToAdmsBankAccounts extends AbstractMigration
{
    /**
     * Adiciona o campo 'type' à tabela 'adms_bank_accounts'.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('adms_bank_accounts');
        $table->addColumn('type', 'enum', [
            'values' => ['Corrente', 'Aplicação'],
            'default' => 'Corrente',
            'null' => false,
            'after' => 'bank'
        ])->update();
    }

    /**
     * Remove o campo 'type' da tabela 'adms_bank_accounts'.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('adms_bank_accounts');
        $table->removeColumn('type')->update();
    }
} 