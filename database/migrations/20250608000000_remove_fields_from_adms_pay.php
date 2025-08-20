<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveFieldsFromAdmsPay extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_pay')) {
            $table = $this->table('adms_pay');
            
            // Remove os campos especificados
            $table->removeColumn('bank_id')
                  ->removeColumn('user_pay_id')
                  ->removeColumn('pay_method_id')
                  ->removeColumn('value')
                  ->removeColumn('total_value_old')
                  ->removeColumn('subtotal')
                  ->removeColumn('amount_paid')
                  ->removeColumn('discount_value')
                  ->removeColumn('fine_value')
                  ->removeColumn('interest')
                  ->removeColumn('residual_total')
                  ->removeColumn('pay_date')
                  ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pay')) {
            $table = $this->table('adms_pay');
            
            // Recria os campos removidos
            $table->addColumn('bank_id', 'integer', ['null' => false])
                  ->addColumn('user_pay_id', 'integer', ['null' => false])
                  ->addColumn('pay_method_id', 'integer', ['null' => false])
                  ->addColumn('value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('total_value_old', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('subtotal', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('amount_paid', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('discount_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('fine_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('interest', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('residual_total', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('pay_date', 'timestamp', ['null' => true])
                  ->update();
        }
    }
} 