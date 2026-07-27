<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Repository para gerenciar tipos de solicitações
 */
class RequestTypesRepository extends DbConnection
{
    /**
     * Buscar todos os tipos (com filtros opcionais futuros)
     */
    public function getAll(): array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                ORDER BY rt.name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar todos os tipos ativos
     */
    public function getAllActive(): array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE (rt.status = 1 OR rt.is_active = 1)
                ORDER BY rt.name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por código
     */
    public function getByCode(string $code): ?array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE rt.code = :code";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT rt.*, 
                       u.name as default_responsible_name
                FROM adms_request_types rt
                LEFT JOIN adms_users u ON rt.default_responsible_user_id = u.id
                WHERE rt.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    public static function decodeLevelIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map('intval', $raw))));
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $decoded))));
    }

    /**
     * @param mixed $ids
     */
    public static function encodeLevelIds(mixed $ids): ?string
    {
        $clean = self::decodeLevelIds($ids);
        if ($clean === []) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Criar tipo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_request_types 
                (code, name, description, requires_responsible, default_responsible_user_id, 
                 requires_quantity, is_active)
                VALUES 
                (:code, :name, :description, :requires_responsible, :default_responsible_user_id,
                 :requires_quantity, :is_active)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':requires_responsible', $data['requires_responsible'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':default_responsible_user_id', $data['default_responsible_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':requires_quantity', $data['requires_quantity'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':is_active', $data['is_active'] ?? true, PDO::PARAM_BOOL);
        
        $stmt->execute();
        
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_request_types',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    /**
     * Atualizar tipo
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'code', 'name', 'description', 'requires_responsible', 'default_responsible_user_id',
            'requires_quantity', 'is_active',             'requires_manager_approval', 'requires_hr_approval', 'requires_dates',
            'requires_days', 'requires_amount', 'icon', 'color', 'status', 'sort_order',
            'skip_immediate_requester_level_ids', 'skip_immediate_supervisor_level_ids',
        ];
        
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                if (in_array($field, [
                    'requires_responsible', 'requires_quantity', 'is_active',
                    'requires_manager_approval', 'requires_hr_approval', 'requires_dates', 'requires_days',
                    'requires_amount', 'status',
                ], true)) {
                    $params[":$field"] = !empty($data[$field]) ? 1 : 0;
                } elseif (in_array($field, [
                    'skip_immediate_requester_level_ids',
                    'skip_immediate_supervisor_level_ids',
                ], true)) {
                    $params[":$field"] = self::encodeLevelIds($data[$field]);
                } else {
                    $params[":$field"] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_request_types 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);

        $oldRow = $this->getById($id);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_request_types',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }

            if (array_key_exists('requires_manager_approval', $data)
                || array_key_exists('requires_hr_approval', $data)
            ) {
                $row = $this->getById($id) ?: [];
                $stagesRepo = new RequestTypeStagesRepository();
                $stagesRepo->ensureStagesForType(
                    $id,
                    !empty($data['requires_manager_approval'] ?? $row['requires_manager_approval']),
                    array_key_exists('requires_hr_approval', $data)
                        ? !empty($data['requires_hr_approval'])
                        : !empty($row['requires_hr_approval'] ?? true)
                );
            }
        }

        return $ok;
    }

    /**
     * Deletar tipo
     */
    public function delete(int $id): bool
    {
        // Verificar se está em uso
        $checkSql = "SELECT COUNT(*) as total FROM adms_booking_additional_requests WHERE request_type = (SELECT code FROM adms_request_types WHERE id = :id)";
        $checkStmt = $this->getConnection()->prepare($checkSql);
        $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ((int)($result['total'] ?? 0) > 0) {
            return false; // Não pode deletar se está em uso
        }

        $oldRow = $this->getById($id);
        
        $sql = "DELETE FROM adms_request_types WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_request_types',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    /**
     * @return list<int>
     */
    public function getSkipImmediateRequesterLevelIds(array $type): array
    {
        return self::decodeLevelIds($type['skip_immediate_requester_level_ids'] ?? null);
    }

    /**
     * @return list<int>
     */
    public function getSkipImmediateSupervisorLevelIds(array $type): array
    {
        return self::decodeLevelIds($type['skip_immediate_supervisor_level_ids'] ?? null);
    }
}
