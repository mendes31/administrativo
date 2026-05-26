<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;

/**
 * Monta relatório de entrega push (PWA) de um informativo,
 * cruzando o cache de dedup com inscrições ativas e dados do usuário.
 */
final class InformativoPushStatusService
{
    /**
     * @return array{
     *   users: list<array{user_id:int, name:string, email:string, department:string, push_status:string, push_sent_at:string, devices:list<array{label:string, updated_at:string}>}>,
     *   summary: array{total:int, sent:int, no_subscription:int, pending:int}
     * }
     */
    public static function buildReport(int $informativoId): array
    {
        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById($informativoId);

        if (!$informativo) {
            return ['users' => [], 'summary' => ['total' => 0, 'sent' => 0, 'no_subscription' => 0, 'pending' => 0]];
        }

        $departmentIds = $repo->getNotifyDepartmentsIds($informativoId);
        $authorId = (int) ($informativo['usuario_id'] ?? 0);
        $exclude = $authorId > 0 ? [$authorId] : [];

        $userIds = ContentPublishRecipientsResolver::activeUserIds($departmentIds, $exclude);
        if ($userIds === []) {
            return ['users' => [], 'summary' => ['total' => 0, 'sent' => 0, 'no_subscription' => 0, 'pending' => 0]];
        }

        $scope = PublishPushDedupCache::scopeForEntity('informativo', $informativoId);
        $dedupData = self::readDedupScope($scope);

        $subRepo = new PushSubscriptionRepository();

        $usersData = self::fetchUsersData($userIds);

        $rows = [];
        $summary = ['total' => 0, 'sent' => 0, 'no_subscription' => 0, 'pending' => 0];

        foreach ($userIds as $uid) {
            $userData = $usersData[$uid] ?? null;
            if ($userData === null) {
                continue;
            }

            $summary['total']++;

            $sentTs = $dedupData[$uid] ?? null;
            $devices = $subRepo->listDevicesForUser($uid);

            if ($sentTs !== null) {
                $status = 'ENTREGUE';
                $sentAt = date('d/m/Y H:i', $sentTs);
                $summary['sent']++;
            } elseif ($devices === []) {
                $status = 'SEM INSCRIÇÃO';
                $sentAt = '—';
                $summary['no_subscription']++;
            } else {
                $status = 'PENDENTE';
                $sentAt = '—';
                $summary['pending']++;
            }

            $deviceList = [];
            foreach ($devices as $dev) {
                $deviceList[] = [
                    'label' => $dev['label'],
                    'updated_at' => $dev['updated_at_fmt'],
                ];
            }

            $rows[] = [
                'user_id' => $uid,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'department' => $userData['department_name'] ?? '',
                'push_status' => $status,
                'push_sent_at' => $sentAt,
                'devices' => $deviceList,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $order = ['ENTREGUE' => 0, 'PENDENTE' => 1, 'SEM INSCRIÇÃO' => 2];
            $diff = ($order[$a['push_status']] ?? 9) - ($order[$b['push_status']] ?? 9);
            return $diff !== 0 ? $diff : strcasecmp($a['name'], $b['name']);
        });

        return ['users' => $rows, 'summary' => $summary];
    }

    /**
     * @return array<int, int> userId => timestamp
     */
    private static function readDedupScope(string $scope): array
    {
        $path = self::dedupFilePath($scope);
        if (!is_file($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['keys']) || !is_array($decoded['keys'])) {
            return [];
        }

        $result = [];
        foreach ($decoded['keys'] as $uid => $ts) {
            $result[(int) $uid] = (int) $ts;
        }
        return $result;
    }

    private static function dedupFilePath(string $scope): string
    {
        $dir = dirname(__DIR__, 4)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'push_dedup';
        return $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_\-]/i', '_', $scope) . '.json';
    }

    /**
     * @param list<int> $userIds
     * @return array<int, array{name:string, email:string, department_name:string}>
     */
    private static function fetchUsersData(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $pdo = (new \App\adms\Models\Repository\UsersRepository())->getConnection();

        $placeholders = [];
        $params = [];
        foreach ($userIds as $i => $uid) {
            $ph = ':u' . $i;
            $placeholders[] = $ph;
            $params[$ph] = $uid;
        }

        $sql = 'SELECT u.id, u.name, u.email, COALESCE(d.name, \'\') AS department_name
                FROM adms_users u
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                WHERE u.id IN (' . implode(',', $placeholders) . ')';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $ph => $val) {
            $stmt->bindValue($ph, $val, \PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
            $result[(int) $row['id']] = [
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'department_name' => (string) $row['department_name'],
            ];
        }
        return $result;
    }
}
