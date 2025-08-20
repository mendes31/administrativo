<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNumNotaToAdmsPay extends AbstractMigration
{
    /**
     * Adiciona o campo 'num_nota' à tabela 'adms_pay'.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('adms_pay');
        $table->addColumn('num_nota', 'string', [
            'limit' => 50,
            'null' => true,
            'after' => 'num_doc', // garante que será após o campo num_doc
        ])->update();
    }

    /**
     * Remove o campo 'num_nota' da tabela 'adms_pay'.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('adms_pay');
        $table->removeColumn('num_nota')->update();
    }
} 