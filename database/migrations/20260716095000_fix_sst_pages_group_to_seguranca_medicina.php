<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas SST criadas por migrations usavam adms_groups_page_id = 41 fixo.
 * No banco real, 41 = SAC e "Segurança e Medicina" = outro ID (ex.: 42).
 * Resultado: "Autorizar Grupo" no módulo SST não liberava equipamentos, GHE,
 * treinamentos, matriz, fichas EPI, etc.
 */
final class FixSstPagesGroupToSegurancaMedicina extends AbstractMigration
{
    private const SST_GROUP_NAME = 'Segurança e Medicina';

    /** Páginas-seed típicas do módulo; quem já as tem liberadas recebe as que estavam no grupo errado. */
    private const REFERENCE_CONTROLLERS = [
        'SstDashboard',
        'SstListCids',
        'SstListEpis',
        'SstListExames',
        'SstListMedicos',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = '" . addslashes(self::SST_GROUP_NAME) . "' LIMIT 1"
        );
        if (!$group) {
            return;
        }

        $sstGroupId = (int) $group['id'];
        $now = date('Y-m-d H:i:s');

        // 1) Reatribuir todas as páginas directory=sst ao grupo correto (por nome).
        $this->execute(
            "UPDATE adms_pages
             SET adms_groups_page_id = {$sstGroupId}, updated_at = '{$now}'
             WHERE directory = 'sst'
               AND adms_groups_page_id <> {$sstGroupId}"
        );

        if (!$this->hasTable('adms_access_levels_pages') || !$this->hasTable('adms_access_levels')) {
            $this->bumpMenuPermissionCache();
            return;
        }

        // 2) Garantir linhas ACL (permission=0) para todas as páginas SST em todos os níveis.
        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages
                (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, p.id, '{$now}', '{$now}'
             FROM adms_access_levels al
             CROSS JOIN adms_pages p
             WHERE p.directory = 'sst'"
        );

        // 3) Níveis que já tinham o módulo (referência seed) passam a ter as páginas que estavam no grupo errado.
        $in = implode(',', array_map(
            static fn (string $c): string => "'" . addslashes($c) . "'",
            self::REFERENCE_CONTROLLERS
        ));

        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id AND p.directory = 'sst'
             INNER JOIN (
                 SELECT DISTINCT alp2.adms_access_level_id AS level_id
                 FROM adms_access_levels_pages alp2
                 INNER JOIN adms_pages pref ON pref.id = alp2.adms_page_id
                 WHERE alp2.permission = 1
                   AND pref.controller IN ({$in})
             ) levels ON levels.level_id = alp.adms_access_level_id
             SET alp.permission = 1, alp.updated_at = '{$now}'
             WHERE alp.permission = 0"
        );

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        // Não reverte o group_id: o valor 41 fixo era incorreto.
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
