<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInstallmentFieldsToAdmsPay extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_pay')) {
            $table = $this->table('adms_pay');
            $table
                ->addColumn('installment_number', 'integer', ['null' => true, 'after' => 'expected_date'])
                ->addColumn('issue_date', 'timestamp', ['null' => true, 'after' => 'installment_number'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pay')) {
            $table = $this->table('adms_pay');
            $table
                ->removeColumn('installment_number')
                ->removeColumn('issue_date')
                ->update();
        }
    }
} 