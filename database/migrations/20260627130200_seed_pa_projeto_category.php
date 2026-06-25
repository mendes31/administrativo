<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Categoria PA - PROJETO para simulação de novos produtos (custeio fabril).
 */
final class SeedPaProjetoCategory extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_categories')) {
            return;
        }

        $row = $this->fetchRow("SELECT id FROM inv_categories WHERE name = 'PA - PROJETO' LIMIT 1");
        if ($row) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('inv_categories')->insert([
            [
                'name' => 'PA - PROJETO',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_categories')) {
            return;
        }

        $this->execute("DELETE FROM inv_categories WHERE name = 'PA - PROJETO' LIMIT 1");
    }
}
