<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige ACL das páginas de reenvio push se a migration anterior liberou para todos os níveis.
 */
final class FixResendPushAclFromUpdatePages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $pairs = [
            ['ResendInformativoPush', 'UpdateInformativo'],
            ['ResendPolicyPush', 'UpdatePolicy'],
        ];

        foreach ($pairs as [$controller, $refController]) {
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
            );
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($refController) . "' LIMIT 1"
            );
            if (!$page || !$ref) {
                continue;
            }

            $pageId = (int) $page['id'];
            $refId = (int) $ref['id'];

            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pageId}");
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$pageId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$refId}"
            );
        }
    }

    public function down(): void
    {
        // Sem rollback automático de ACL
    }
}
