<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Contract Fase 0.5 — ativa filtro real de entrevistas.
 *
 * Mesmo critério do Contract de Vagas: mantém RhEntrevistasViewAll em níveis
 * RH / DP / Super Admin; revoga nos demais.
 */
final class ContractRhEntrevistasViewAllScope extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistasViewAll' LIMIT 1"
        );
        if (!$page) {
            return;
        }

        $pageId = (int) $page['id'];
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_access_levels al ON al.id = alp.adms_access_level_id
             SET alp.permission = 0, alp.updated_at = " . $conn->quote($now) . "
             WHERE alp.adms_page_id = {$pageId}
               AND alp.permission = 1
               AND al.id <> 1
               AND LOWER(al.name) NOT REGEXP 'recursos[[:space:]]+humanos'
               AND LOWER(al.name) NOT REGEXP 'departamento[[:space:]]+pessoal'
               AND LOWER(al.name) NOT REGEXP '(^|[^a-z])rh([^a-z]|$)'
               AND LOWER(al.name) NOT REGEXP '(^|[^a-z])dp([^a-z]|$)'
               AND LOWER(al.name) NOT LIKE '%super admin%'"
        );

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistasViewAll' LIMIT 1"
        );
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistas' LIMIT 1"
        );
        if (!$page || !$ref) {
            return;
        }

        $pageId = (int) $page['id'];
        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }
}
