<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Reaplica a política do Canal de Denúncias nas páginas públicas do grupo:
 * - canaldenuncia (formulário público: public_page=1, não precisa de ACL ampla)
 * - whistleblowing-retention-cron (protegido por token HTTP, não por nível de acesso)
 *
 * Após RestrictWhistleblowingGroupPagesAcl (2026-07-17), essas duas voltaram a
 * permission=1 em praticamente todos os níveis (ex.: Almoxarife Jr), poluindo a
 * matriz. Gestão interna do canal permanece só em Operador/Administrador (+ Super Admin).
 */
final class RevokeWhistleblowingPublicPagesBroadAcl extends AbstractMigration
{
    private const GROUP_NAME = 'Canal de Denúncias';

    private const SUPER_ADMIN_LEVEL_ID = 1;

    /** @var list<string> */
    private const AUTHORIZED_LEVEL_NAMES = [
        'Canal de Denúncias — Operador',
        'Canal de Denúncias — Administrador',
    ];

    /** @var list<string> */
    private const TARGET_CONTROLLERS = [
        'CanalDenuncia',
        'WhistleblowingRetentionCron',
    ];

    public function up(): void
    {
        if (
            !$this->hasTable('adms_groups_pages')
            || !$this->hasTable('adms_pages')
            || !$this->hasTable('adms_access_levels')
            || !$this->hasTable('adms_access_levels_pages')
        ) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();

        $authorizedIds = [self::SUPER_ADMIN_LEVEL_ID];
        foreach (self::AUTHORIZED_LEVEL_NAMES as $name) {
            $row = $this->fetchRow(
                'SELECT id FROM adms_access_levels WHERE name = ' . $conn->quote($name) . ' LIMIT 1'
            );
            if ($row) {
                $authorizedIds[] = (int) $row['id'];
            }
        }
        $inList = implode(',', array_map('intval', array_unique($authorizedIds)));

        $controllersSql = implode(',', array_map(
            static fn (string $c): string => $conn->quote($c),
            self::TARGET_CONTROLLERS
        ));

        $now = date('Y-m-d H:i:s');
        $this->execute(
            "UPDATE adms_access_levels_pages AS alp
             INNER JOIN adms_pages AS ap ON ap.id = alp.adms_page_id
             SET alp.permission = 0, alp.updated_at = '{$now}'
             WHERE ap.controller IN ({$controllersSql})
               AND alp.permission = 1
               AND alp.adms_access_level_id NOT IN ({$inList})"
        );

        // Garante que Operador/Admin/Super Admin mantenham (ou ganhem) permission=1.
        foreach ($authorizedIds as $levelId) {
            foreach (self::TARGET_CONTROLLERS as $controller) {
                $page = $this->fetchRow(
                    'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
                );
                if (!$page) {
                    continue;
                }
                $pageId = (int) $page['id'];
                $exists = $this->fetchRow(
                    "SELECT id FROM adms_access_levels_pages
                     WHERE adms_access_level_id = {$levelId} AND adms_page_id = {$pageId} LIMIT 1"
                );
                if ($exists) {
                    $this->execute(
                        "UPDATE adms_access_levels_pages
                         SET permission = 1, updated_at = '{$now}'
                         WHERE id = " . (int) $exists['id']
                    );
                } else {
                    $this->execute(
                        "INSERT INTO adms_access_levels_pages
                            (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                         VALUES (1, {$levelId}, {$pageId}, '{$now}', '{$now}')"
                    );
                }
            }
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        // Não reabrir ACL ampla às páginas públicas do canal.
        $this->bumpMenuPermissionCache();
    }

    private function bumpMenuPermissionCache(): void
    {
        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
            return;
        }
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
