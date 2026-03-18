<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar tipos de solicitações adicionais de Reserva de Salas
 *
 * Tabela: adms_room_request_types
 */
class RoomRequestTypesRepository extends DbConnection
{
    /**
     * Buscar todos os tipos (opcionalmente apenas ativos)
     */
    public function getAll(bool $onlyActive = false): array
    {
        $sql = "SELECT rt.*,
                       u.name AS default_responsible_name,
                       g.name AS default_group_name
                FROM adms_room_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                LEFT JOIN adms_room_request_groups g ON rt.default_responsible_group_id = g.id";

        if ($onlyActive) {
            $sql .= " WHERE rt.is_active = 1";
        }

        $sql .= " ORDER BY rt.name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT rt.*,
                       u.name AS default_responsible_name,
                       g.name AS default_group_name
                FROM adms_room_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                LEFT JOIN adms_room_request_groups g ON rt.default_responsible_group_id = g.id
                WHERE rt.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Buscar por código
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT rt.*,
                       u.name AS default_responsible_name,
                       g.name AS default_group_name
                FROM adms_room_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                LEFT JOIN adms_room_request_groups g ON rt.default_responsible_group_id = g.id
                WHERE rt.code = :code";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Criar tipo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_room_request_types
                (code, name, description, requires_responsible, default_responsible_group_id, default_responsible_user_id,
                 requires_quantity, is_active, created_at, updated_at)
                VALUES
                (:code, :name, :description, :requires_responsible, :default_responsible_group_id, :default_responsible_user_id,
                 :requires_quantity, :is_active, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':requires_responsible', !empty($data['requires_responsible']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':default_responsible_group_id', $data['default_responsible_group_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':default_responsible_user_id', $data['default_responsible_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':requires_quantity', !empty($data['requires_quantity']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);

        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Atualizar tipo
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'name',
            'description',
            'requires_responsible',
            'default_responsible_group_id',
            'default_responsible_user_id',
            'requires_quantity',
            'is_active',
        ];

        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $placeholder = ':' . $field;
                $sets[] = "$field = $placeholder";
                $params[$placeholder] = in_array($field, ['requires_responsible', 'requires_quantity', 'is_active'], true)
                    ? (!empty($data[$field]) ? 1 : 0)
                    : $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = "updated_at = NOW()";

        $sql = "UPDATE adms_room_request_types
                SET " . implode(', ', $sets) . "
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        return $stmt->execute();
    }

    /**
     * Deletar tipo (se não estiver em uso)
     */
    public function delete(int $id): bool
    {
        // Verificar se há reservas usando este tipo
        $checkSql = "SELECT COUNT(*) AS total
                     FROM adms_booking_additional_requests
                     WHERE request_type = (SELECT code FROM adms_room_request_types WHERE id = :id)";
        $checkStmt = $this->getConnection()->prepare($checkSql);
        $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ((int)($result['total'] ?? 0) > 0) {
            return false;
        }

        $sql = "DELETE FROM adms_room_request_types WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}

