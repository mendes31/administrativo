<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Prioridade para ordenação (menções acima das demais).
 */
final class AddPriorityToAdmsNotifications extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_notifications')) {
            return;
        }

        $table = $this->table('adms_notifications');
        if ($table->hasColumn('priority')) {
            return;
        }

        $table
            ->addColumn('priority', 'integer', [
                'signed' => false,
                'null' => false,
                'default' => 0,
                'comment' => 'Maior = mais urgente; menções (timeline/projeto) = 100.',
            ])
            ->update();

        $this->execute(
            "UPDATE adms_notifications SET priority = 100
             WHERE type IN ('timeline_mention', 'comentario_mencao')"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_notifications')) {
            return;
        }

        $table = $this->table('adms_notifications');
        if ($table->hasColumn('priority')) {
            $table->removeColumn('priority')->update();
        }
    }
}
