<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Níveis dedicados do Canal de Denúncias (secundários por usuário) + ACL das páginas.
 *
 * Operador: dashboard, listar, ver, responder, status.
 * Administrador: comitês, configuração, governança LGPD (+ tudo do operador).
 */
final class WhistleblowingAccessLevels extends AbstractMigration
{
    public const OPERATOR_LEVEL_NAME = 'Canal de Denúncias — Operador';

    public const ADMIN_LEVEL_NAME = 'Canal de Denúncias — Administrador';

    /** @var list<string> */
    private const OPERATOR_CONTROLLERS = [
        'WhistleblowingDashboard',
        'WhistleblowingListReports',
        'WhistleblowingViewReport',
        'WhistleblowingReplyReport',
        'WhistleblowingUpdateStatus',
    ];

    /** @var list<string> */
    private const ADMIN_ONLY_CONTROLLERS = [
        'WhistleblowingListCommittees',
        'WhistleblowingCreateCommittee',
        'WhistleblowingUpdateCommittee',
        'WhistleblowingConfig',
        'WhistleblowingGovernanceLgpd',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels') || !$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $operatorLevelId = $this->ensureAccessLevel(self::OPERATOR_LEVEL_NAME, $now);
        $adminLevelId = $this->ensureAccessLevel(self::ADMIN_LEVEL_NAME, $now);

        if ($operatorLevelId <= 0 || $adminLevelId <= 0) {
            return;
        }

        $this->grantPagesToLevel($operatorLevelId, self::OPERATOR_CONTROLLERS, $now);
        $this->grantPagesToLevel($adminLevelId, array_merge(self::OPERATOR_CONTROLLERS, self::ADMIN_ONLY_CONTROLLERS), $now);

        $this->syncExistingCommitteeMembers($operatorLevelId);
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_access_levels')) {
            return;
        }

        foreach ([self::OPERATOR_LEVEL_NAME, self::ADMIN_LEVEL_NAME] as $name) {
            $row = $this->fetchRow("SELECT id FROM adms_access_levels WHERE name = " . $this->getAdapter()->getConnection()->quote($name) . ' LIMIT 1');
            if (!$row) {
                continue;
            }
            $levelId = (int) $row['id'];
            if ($this->hasTable('adms_users_access_levels')) {
                $this->execute("DELETE FROM adms_users_access_levels WHERE adms_access_level_id = {$levelId}");
            }
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_access_level_id = {$levelId}");
            }
            $this->execute("DELETE FROM adms_access_levels WHERE id = {$levelId}");
        }
    }

    private function ensureAccessLevel(string $name, string $now): int
    {
        $quoted = $this->getAdapter()->getConnection()->quote($name);
        $row = $this->fetchRow("SELECT id FROM adms_access_levels WHERE name = {$quoted} LIMIT 1");
        if ($row) {
            return (int) $row['id'];
        }

        $this->table('adms_access_levels')->insert([
            'name' => $name,
            'create_at' => $now,
            'update_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');

        return (int) ($newRow['id'] ?? 0);
    }

    /**
     * @param list<string> $controllers
     */
    private function grantPagesToLevel(int $levelId, array $controllers, string $now): void
    {
        foreach ($controllers as $controller) {
            $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = " . $this->getAdapter()->getConnection()->quote($controller) . ' LIMIT 1');
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 VALUES (1, {$levelId}, {$pageId}, '{$now}', '{$now}')
                 ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
            );
        }
    }

    private function syncExistingCommitteeMembers(int $operatorLevelId): void
    {
        if (!$this->hasTable('adms_whistleblowing_committee_members') || !$this->hasTable('adms_users_access_levels')) {
            return;
        }

        $rows = $this->fetchAll('SELECT DISTINCT user_id FROM adms_whistleblowing_committee_members');
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $exists = $this->fetchRow(
                "SELECT id FROM adms_users_access_levels
                 WHERE adms_user_id = {$userId} AND adms_access_level_id = {$operatorLevelId} LIMIT 1"
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_users_access_levels')->insert([
                'adms_user_id' => $userId,
                'adms_access_level_id' => $operatorLevelId,
                'created_at' => $now,
            ])->save();
        }
    }
}
