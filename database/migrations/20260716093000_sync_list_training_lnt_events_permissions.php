<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante permissão de ListTrainingLntEvents para níveis que já têm
 * qualquer página da equipe de treinamentos (alinha com quem recebe o sino).
 */
final class SyncListTrainingLntEventsPermissions extends AbstractMigration
{
    private const CONTROLLER = 'ListTrainingLntEvents';

    private const SOURCE_CONTROLLERS = [
        'ListTrainingStatus',
        'ListTrainings',
        'CreateTraining',
        'TrainingPositions',
        'LinkTrainingUsers',
        'MatrixByUser',
        'ListTrainingLntEvents',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes(self::CONTROLLER) . "' LIMIT 1"
        );
        if (!$page) {
            return;
        }

        $pageId = (int) $page['id'];
        $now = date('Y-m-d H:i:s');
        $in = implode(',', array_map(
            static fn (string $c): string => "'" . addslashes($c) . "'",
            self::SOURCE_CONTROLLERS
        ));

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT DISTINCT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             WHERE alp.permission = 1
               AND p.controller IN ({$in})
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );

        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }

    public function down(): void
    {
        // Não remove permissões já concedidas.
    }
}
