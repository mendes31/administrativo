<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSlowRequestProfilerSettingsAndTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_log_settings')) {
            $table = $this->table('adms_log_settings');
            if (!$table->hasColumn('slow_request_profiler_enabled')) {
                $table->addColumn('slow_request_profiler_enabled', 'boolean', ['default' => 0, 'null' => false]);
            }
            if (!$table->hasColumn('slow_request_threshold_ms')) {
                $table->addColumn('slow_request_threshold_ms', 'integer', ['default' => 700, 'null' => false, 'signed' => false]);
            }
            if (!$table->hasColumn('slow_request_retention_days')) {
                $table->addColumn('slow_request_retention_days', 'integer', ['default' => 7, 'null' => false, 'signed' => false]);
            }
            $table->update();

            $this->execute(
                "UPDATE adms_log_settings
                 SET slow_request_profiler_enabled = COALESCE(slow_request_profiler_enabled, 0),
                     slow_request_threshold_ms = COALESCE(slow_request_threshold_ms, 700),
                     slow_request_retention_days = COALESCE(slow_request_retention_days, 7),
                     updated_at = NOW()
                 WHERE id = 1"
            );
        }

        if (!$this->hasTable('adms_slow_request_profiles')) {
            $this->table('adms_slow_request_profiles')
                ->addColumn('request_method', 'string', ['limit' => 12, 'null' => false])
                ->addColumn('request_uri', 'string', ['limit' => 1024, 'null' => false])
                ->addColumn('route_label', 'string', ['limit' => 160, 'null' => false, 'default' => 'unknown'])
                ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('duration_ms', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
                ->addColumn('memory_mb', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addIndex(['created_at'])
                ->addIndex(['duration_ms'])
                ->addIndex(['route_label'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_slow_request_profiles')) {
            $this->table('adms_slow_request_profiles')->drop()->save();
        }

        if ($this->hasTable('adms_log_settings')) {
            $table = $this->table('adms_log_settings');
            if ($table->hasColumn('slow_request_retention_days')) {
                $table->removeColumn('slow_request_retention_days');
            }
            if ($table->hasColumn('slow_request_threshold_ms')) {
                $table->removeColumn('slow_request_threshold_ms');
            }
            if ($table->hasColumn('slow_request_profiler_enabled')) {
                $table->removeColumn('slow_request_profiler_enabled');
            }
            $table->update();
        }
    }
}
