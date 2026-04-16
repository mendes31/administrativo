<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFrontendDebugFlagToLogSettings extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_settings')) {
            return;
        }

        $table = $this->table('adms_log_settings');
        if (!$table->hasColumn('frontend_debug_logs')) {
            $table->addColumn('frontend_debug_logs', 'boolean', ['default' => 0, 'null' => false]);
            $table->update();
        }

        $this->execute(
            "UPDATE adms_log_settings
             SET frontend_debug_logs = COALESCE(frontend_debug_logs, 0),
                 updated_at = NOW()
             WHERE id = 1"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_log_settings')) {
            return;
        }
        $table = $this->table('adms_log_settings');
        if ($table->hasColumn('frontend_debug_logs')) {
            $table->removeColumn('frontend_debug_logs');
            $table->update();
        }
    }
}
