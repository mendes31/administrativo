<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Conexões à **API intermediária** SAP (gateway). O PHP administrativo não fala com o b1s directamente;
 * a API é que se liga à Service Layer.
 */
class AdmsSapServiceLayerConnectionRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        $sql = 'SELECT id, name, base_url, health_path, company_db, username,
                (CHAR_LENGTH(TRIM(password)) > 0) AS has_password,
                is_active, is_default, sort_order, created_at, updated_at
                FROM adms_sap_service_layer_connections
                ORDER BY is_default DESC, sort_order ASC, name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_sap_service_layer_connections WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Conexão marcada como padrão (activa); senão a primeira activa por sort_order / id.
     *
     * @return array<string, mixed>|null
     */
    public function getDefaultOrFirstActive(): ?array
    {
        $sql = 'SELECT * FROM adms_sap_service_layer_connections
                WHERE is_active = 1
                ORDER BY is_default DESC, sort_order ASC, id ASC
                LIMIT 1';
        $stmt = $this->getConnection()->query($sql);
        if (!$stmt) {
            return null;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM adms_sap_service_layer_connections WHERE name = :name';
        if ($exceptId !== null && $exceptId > 0) {
            $sql .= ' AND id <> :id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', trim($name));
        if ($exceptId !== null && $exceptId > 0) {
            $stmt->bindValue(':id', $exceptId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array{name: string, base_url: string, health_path: string, company_db: string, username: string, password?: string, is_active: int, is_default: int, sort_order: int} $data
     */
    public function insert(array $data): ?int
    {
        $sql = 'INSERT INTO adms_sap_service_layer_connections
            (name, base_url, health_path, company_db, username, password, is_active, is_default, sort_order, created_at, updated_at)
            VALUES (:name, :base_url, :health_path, :company_db, :username, :password, :is_active, :is_default, :sort_order, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':base_url', $data['base_url']);
        $stmt->bindValue(':health_path', $data['health_path'] ?? '/health');
        $stmt->bindValue(':company_db', $data['company_db']);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':password', $data['password'] ?? '');
        $stmt->bindValue(':is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->bindValue(':is_default', $data['is_default'], PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $data['sort_order'], PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return null;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $this->logChange($newId, 'INSERT', [], $this->getById($newId) ?? []);
        }

        return $newId > 0 ? $newId : null;
    }

    /**
     * @param array{password?: string, name: string, base_url: string, health_path: string, company_db: string, username: string, is_active: int, is_default: int, sort_order: int} $data
     */
    public function update(int $id, array $data): bool
    {
        $old = $this->getById($id);
        if (!$old) {
            return false;
        }

        $pwd = trim($data['password'] ?? '');
        if ($pwd !== '') {
            $sql = 'UPDATE adms_sap_service_layer_connections SET
                name = :name, base_url = :base_url, health_path = :health_path, company_db = :company_db, username = :username,
                password = :password, is_active = :is_active, is_default = :is_default, sort_order = :sort_order,
                updated_at = NOW()
                WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':password', $pwd);
        } else {
            $sql = 'UPDATE adms_sap_service_layer_connections SET
                name = :name, base_url = :base_url, health_path = :health_path, company_db = :company_db, username = :username,
                is_active = :is_active, is_default = :is_default, sort_order = :sort_order,
                updated_at = NOW()
                WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
        }

        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':base_url', $data['base_url']);
        $stmt->bindValue(':health_path', $data['health_path'] ?? '/health');
        $stmt->bindValue(':company_db', $data['company_db']);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->bindValue(':is_default', $data['is_default'], PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $data['sort_order'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            $new = $this->getById($id);
            if ($new) {
                $this->logChange($id, 'UPDATE', $old, $new);
            }
        }

        return $ok;
    }

    public function deleteById(int $id): bool
    {
        $old = $this->getById($id);
        if (!$old) {
            return false;
        }
        $sql = 'DELETE FROM adms_sap_service_layer_connections WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            $this->logChange($id, 'DELETE', $old, []);
        }

        return $ok;
    }

    public function clearDefaultExcept(?int $keepId): void
    {
        if ($keepId !== null && $keepId > 0) {
            $sql = 'UPDATE adms_sap_service_layer_connections SET is_default = 0, updated_at = NOW() WHERE id <> :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $keepId, PDO::PARAM_INT);
            $stmt->execute();

            return;
        }
        $this->getConnection()->exec('UPDATE adms_sap_service_layer_connections SET is_default = 0, updated_at = NOW()');
    }

    /** Define uma conexão como única padrão (as restantes ficam is_default = 0). */
    public function setAsOnlyDefault(int $id): void
    {
        $sql = 'UPDATE adms_sap_service_layer_connections SET is_default = (id = :id), updated_at = NOW()';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function logChange(int $id, string $op, array $old, array $new): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $oldLog = $old;
        $newLog = $new;
        if (isset($oldLog['password'])) {
            $oldLog['password'] = '***';
        }
        if (isset($newLog['password'])) {
            $newLog['password'] = '***';
        }
        LogAlteracaoService::registrarAlteracao(
            'adms_sap_service_layer_connections',
            $id,
            $userId,
            $op,
            $oldLog,
            $newLog
        );
    }
}
