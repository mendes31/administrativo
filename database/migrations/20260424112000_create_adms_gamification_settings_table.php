<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsGamificationSettingsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('adms_gamification_settings', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('setting_key', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('setting_value', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['setting_key'], ['unique' => true, 'name' => 'uk_gamification_setting_key'])
            ->create();
    }
}
