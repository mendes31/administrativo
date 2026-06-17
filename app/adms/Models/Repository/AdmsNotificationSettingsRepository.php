<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\NotificationSettingsRegistry;
use PDO;

class AdmsNotificationSettingsRepository extends DbConnection
{
    /** @var array<string, bool>|null */
    private static ?array $cache = null;

    public function isEnabled(string $key): bool
    {
        $all = $this->getEnabledMap();
        return !empty($all[$key]);
    }

    /**
     * @return array<string, bool>
     */
    public function getEnabledMap(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $this->ensureDefaults();
        $sql = 'SELECT setting_key, enabled FROM adms_notification_settings';
        $stmt = $this->getConnection()->query($sql);
        $map = [];
        foreach (NotificationSettingsRegistry::keys() as $key) {
            $map[$key] = false;
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $map[(string) $row['setting_key']] = (int) ($row['enabled'] ?? 0) === 1;
        }

        self::$cache = $map;
        return $map;
    }

    /**
     * @return array<int, array{module: string, module_order: int, items: array<int, array<string, mixed>>}>
     */
    public function getGroupedForForm(): array
    {
        $enabled = $this->getEnabledMap();
        $definitions = NotificationSettingsRegistry::definitions();
        $grouped = [];

        foreach ($definitions as $key => $meta) {
            $module = $meta['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'module' => $module,
                    'module_order' => $meta['module_order'],
                    'items' => [],
                ];
            }
            $grouped[$module]['items'][] = array_merge($meta, [
                'key' => $key,
                'enabled' => !empty($enabled[$key]),
            ]);
        }

        foreach ($grouped as &$group) {
            usort($group['items'], static fn(array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        }
        unset($group);

        uasort($grouped, static fn(array $a, array $b): int => ($a['module_order'] ?? 0) <=> ($b['module_order'] ?? 0));

        return array_values($grouped);
    }

    /**
     * @param array<int, string> $enabledKeys
     */
    public function saveEnabledKeys(array $enabledKeys): bool
    {
        $this->ensureDefaults();
        $enabledSet = array_fill_keys($enabledKeys, true);
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $ok = true;

        foreach (NotificationSettingsRegistry::keys() as $key) {
            $newEnabled = isset($enabledSet[$key]) ? 1 : 0;
            $old = $this->getRowByKey($key);
            $oldEnabled = (int) ($old['enabled'] ?? 0);

            if ($oldEnabled === $newEnabled) {
                continue;
            }

            $sql = 'UPDATE adms_notification_settings
                    SET enabled = :enabled, updated_by = :uid, updated_at = NOW()
                    WHERE setting_key = :key';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':enabled', $newEnabled, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $uid > 0 ? $uid : null, $uid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $ok = false;
                continue;
            }

            $newRow = $this->getRowByKey($key);
            if ($old && $newRow) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_notification_settings',
                    (int) $newRow['id'],
                    $uid > 0 ? $uid : 1,
                    'UPDATE',
                    $old,
                    $newRow
                );
            }
        }

        self::$cache = null;
        return $ok;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    private function ensureDefaults(): void
    {
        foreach (NotificationSettingsRegistry::keys() as $key) {
            if ($this->getRowByKey($key)) {
                continue;
            }
            $sql = 'INSERT INTO adms_notification_settings (setting_key, enabled, updated_at)
                    VALUES (:key, 0, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRowByKey(string $key): ?array
    {
        $sql = 'SELECT * FROM adms_notification_settings WHERE setting_key = :key LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
