<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Calibração avançada: snapshot pré-calibração + bump cache de menu.
 */
final class ExpandPerformanceCalibrationScoresLock extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_performance_reviews')) {
            $table = $this->table('adms_performance_reviews');
            if (!$table->hasColumn('overall_score_pre_calibration')) {
                $table->addColumn('overall_score_pre_calibration', 'decimal', [
                    'precision' => 5, 'scale' => 2, 'null' => true, 'after' => 'potential_score',
                    'comment' => 'Nota de desempenho antes da 1ª calibração',
                ]);
            }
            if (!$table->hasColumn('potential_score_pre_calibration')) {
                $table->addColumn('potential_score_pre_calibration', 'decimal', [
                    'precision' => 5, 'scale' => 2, 'null' => true, 'after' => 'overall_score_pre_calibration',
                    'comment' => 'Nota de potencial antes da 1ª calibração',
                ]);
            }
            if ($table->hasColumn('overall_score_pre_calibration') || $table->hasColumn('potential_score_pre_calibration')) {
                // hasColumn checks above may be stale before save; always save if we added
            }
            $table->update();
        }
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_performance_reviews')) {
            $table = $this->table('adms_performance_reviews');
            if ($table->hasColumn('potential_score_pre_calibration')) {
                $table->removeColumn('potential_score_pre_calibration');
            }
            if ($table->hasColumn('overall_score_pre_calibration')) {
                $table->removeColumn('overall_score_pre_calibration');
            }
            $table->update();
        }
        $this->bumpMenuCache();
    }

    private function bumpMenuCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
