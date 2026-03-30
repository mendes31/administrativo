<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTimelineBioToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $this->table('adms_users')
                ->addColumn('timeline_bio', 'text', ['null' => true])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $this->table('adms_users')
                ->removeColumn('timeline_bio')
                ->update();
        }
    }
}
