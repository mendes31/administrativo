<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * IP e User-Agent na trilha de acesso interno do Canal de Denúncias.
 */
final class AddWhistleblowingAccessLogClientMeta extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_access_log')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_access_log');
        if (!$table->hasColumn('ip_address')) {
            $table->addColumn('ip_address', 'string', [
                'limit' => 45,
                'null' => true,
                'after' => 'action',
            ]);
        }
        if (!$table->hasColumn('user_agent')) {
            $table->addColumn('user_agent', 'string', [
                'limit' => 512,
                'null' => true,
                'after' => 'ip_address',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_whistleblowing_access_log')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_access_log');
        if ($table->hasColumn('user_agent')) {
            $table->removeColumn('user_agent');
        }
        if ($table->hasColumn('ip_address')) {
            $table->removeColumn('ip_address');
        }
        $table->update();
    }
}
