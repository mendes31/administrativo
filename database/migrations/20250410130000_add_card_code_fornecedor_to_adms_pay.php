<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCardCodeFornecedorToAdmsPay extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_pay');
        $table->addColumn('card_code_fornecedor', 'string', [
            'limit' => 50,
            'null' => true,
            'after' => 'partner_id',
        ])->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_pay');
        $table->removeColumn('card_code_fornecedor')->update();
    }
} 