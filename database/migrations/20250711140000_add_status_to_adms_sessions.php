<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddStatusToAdmsSessions extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_sessions');
        if (!$table->hasColumn('status')) {
            $table->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'ativa',
                'null' => false,
                'after' => 'session_id',
                'comment' => 'ativa|invalidada'
            ])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('adms_sessions');
        if ($table->hasColumn('status')) {
            $table->removeColumn('status')->update();
        }
    }
} 