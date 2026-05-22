<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\UsersRepository;
use PDO;

/**
 * Resolve IDs de usuários ativos elegíveis para notificação de publicação
 * (informativos, políticas, etc.), com filtro opcional por departamento.
 */
final class ContentPublishRecipientsResolver
{
    /**
     * @param list<int> $departmentIds vazio = todos os departamentos
     * @param list<int> $excludeUserIds usuários que não devem receber (ex.: autor)
     * @return list<int>
     */
    public static function activeUserIds(array $departmentIds = [], array $excludeUserIds = []): array
    {
        $departmentIds = array_values(array_unique(array_filter(
            array_map('intval', $departmentIds),
            static fn (int $id): bool => $id > 0
        )));

        $excludeUserIds = array_values(array_unique(array_filter(
            array_map('intval', $excludeUserIds),
            static fn (int $id): bool => $id > 0
        )));

        $sql = 'SELECT id FROM adms_users WHERE status = :status';
        $params = [':status' => 'Ativo'];

        if ($departmentIds !== []) {
            $placeholders = [];
            foreach ($departmentIds as $idx => $depId) {
                $ph = ':dep' . $idx;
                $placeholders[] = $ph;
                $params[$ph] = $depId;
            }
            $sql .= ' AND user_department_id IN (' . implode(',', $placeholders) . ')';
        }

        if ($excludeUserIds !== []) {
            $placeholders = [];
            foreach ($excludeUserIds as $idx => $uid) {
                $ph = ':ex' . $idx;
                $placeholders[] = $ph;
                $params[$ph] = $uid;
            }
            $sql .= ' AND id NOT IN (' . implode(',', $placeholders) . ')';
        }

        $usersRepo = new UsersRepository();
        $stmt = $usersRepo->getConnection()->prepare($sql);
        foreach ($params as $ph => $value) {
            $stmt->bindValue($ph, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
