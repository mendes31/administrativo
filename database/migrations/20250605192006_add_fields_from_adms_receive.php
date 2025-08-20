<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFieldsFromAdmsReceive extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_receive')) {
            $table = $this->table('adms_receive');
            $table
                ->addColumn('installment_number', 'integer', ['null' => true, 'after' => 'expected_date'])
                ->addColumn('issue_date', 'timestamp', ['null' => true, 'after' => 'installment_number'])
                ->addColumn('busy', 'integer', ['default' => 0,'null' => false,'after' => 'account_id']) // Indica que a coluna será criada após a coluna 'account_id'
                ->addColumn('user_temp', 'integer', ['null' => true,'after' => 'busy']) // Indica que a coluna será criada após a coluna 'busy'
                ->addColumn('num_nota', 'string', ['limit' => 50,'null' => true,'after' => 'num_doc']) // garante que será após o campo num_doc
                ->addColumn('card_code_cliente', 'string', ['limit' => 50,'null' => true,'after' => 'partner_id'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_receive')) {
            $table = $this->table('adms_receive');
            $table
                ->removeColumn('installment_number')
                ->removeColumn('issue_date')
                ->removeColumn('busy')
                ->removeColumn('user_temp')
                ->removeColumn('num_nota')
                ->removeColumn('card_code_cliente')
                ->update();
        }
    }
} 