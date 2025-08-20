<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFinancialFieldsToAdmsMovements extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_movements')) {
            $table = $this->table('adms_movements');
            $table
                ->addColumn('discount_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0])
                ->addColumn('fine_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0])
                ->addColumn('interest_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_movements')) {
            $table = $this->table('adms_movements');
            $table
                ->removeColumn('discount_value')
                ->removeColumn('fine_value')
                ->removeColumn('interest_value')
                ->update();
        }
    }
} 