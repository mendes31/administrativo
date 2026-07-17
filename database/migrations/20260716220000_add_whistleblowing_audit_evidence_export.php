<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Página administrativa e trilha de geração do pacote seguro de evidências.
 */
final class AddWhistleblowingAuditEvidenceExport extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_evidence_exports')) {
            $this->table('adms_whistleblowing_evidence_exports')
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('format', 'string', ['limit' => 10, 'null' => false])
                ->addColumn('protocol_reference', 'string', ['limit' => 32, 'null' => true])
                ->addColumn('generated_at', 'datetime', ['null' => false])
                ->addIndex(['user_id'])
                ->addIndex(['report_id'])
                ->addIndex(['generated_at'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('report_id', 'adms_whistleblowing_reports', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'WhistleblowingAuditEvidence' LIMIT 1"
        );
        if (!$page) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1"
            );
            if (!$group) {
                return;
            }
            $now = date('Y-m-d H:i:s');
            $this->table('adms_pages')->insert([
                'name' => 'Evidências auditáveis do Canal de Denúncias',
                'controller' => 'WhistleblowingAuditEvidence',
                'controller_url' => 'whistleblowing-audit-evidence',
                'directory' => 'whistleblowing',
                'obs' => 'Prévia e exportação segura em PDF/Excel, sem conteúdo sensível.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => (int) $group['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'WhistleblowingAuditEvidence' LIMIT 1"
            );
        }

        if (!$page || !$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuPermissionCache();
            return;
        }

        $pageId = (int) $page['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages
                (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );

        $adminLevel = $this->fetchRow(
            "SELECT id FROM adms_access_levels
             WHERE name = 'Canal de Denúncias — Administrador' LIMIT 1"
        );
        if ($adminLevel) {
            $adminLevelId = (int) $adminLevel['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages
                    (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 VALUES (1, {$adminLevelId}, {$pageId}, '{$now}', '{$now}')
                 ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
            );
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "DELETE FROM adms_pages WHERE controller = 'WhistleblowingAuditEvidence'"
            );
        }
        if ($this->hasTable('adms_whistleblowing_evidence_exports')) {
            $this->table('adms_whistleblowing_evidence_exports')->drop()->save();
        }
        $this->bumpMenuPermissionCache();
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
