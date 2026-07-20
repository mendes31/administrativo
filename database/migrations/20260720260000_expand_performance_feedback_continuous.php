<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Feedback contínuo global — grants ACL + invalidação de cache de menu.
 */
final class ExpandPerformanceFeedbackContinuous extends AbstractMigration
{
    public function up(): void
    {
        $this->ensureGrants();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        $this->bumpMenuCache();
    }

    private function ensureGrants(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = null;
        foreach (['ListPerformanceFeedbacks', 'ListPerformanceGoals', 'ListPerformanceReviews'] as $ctrl) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if ($ref) {
                break;
            }
        }
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');

        foreach ([
            'ListPerformanceFeedbacks',
            'CreatePerformanceFeedback',
            'ViewPerformanceFeedback',
            'UpdatePerformanceFeedback',
            'DeletePerformanceFeedback',
        ] as $controller) {
            $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
                 AND NOT EXISTS (
                     SELECT 1 FROM adms_access_levels_pages x
                     WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
                 )"
            );
        }
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
