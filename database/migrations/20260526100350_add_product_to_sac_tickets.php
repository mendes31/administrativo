<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductToSacTickets extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sac_tickets');

        if (!$table->hasColumn('product')) {
            $table->addColumn('product', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'description',
            ])->addIndex(['product']);
        }

        if (!$table->hasColumn('batch')) {
            $table->addColumn('batch', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'product',
            ]);
        }

        $table->update();
    }
}
