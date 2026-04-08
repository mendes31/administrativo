<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNetAmountToEmployeePayrollDocuments extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }

        $table = $this->table('adms_employee_payroll_documents');
        if (!$table->hasColumn('net_amount')) {
            $table
                ->addColumn('net_amount', 'decimal', [
                    'precision' => 12,
                    'scale' => 2,
                    'null' => true,
                    'after' => 'file_size',
                    'comment' => 'Valor líquido extraído do PDF quando identificado',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }
        $table = $this->table('adms_employee_payroll_documents');
        if ($table->hasColumn('net_amount')) {
            $table->removeColumn('net_amount')->update();
        }
    }
}

