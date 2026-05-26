<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SacClientsRepository extends DbConnection
{
    public function getAllClients(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = '(c.razao_social LIKE :search OR c.nome_fantasia LIKE :search OR c.document LIKE :search OR c.email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = 'c.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['segment'])) {
            $whereConditions[] = 'c.segment = :segment';
            $params[':segment'] = $filters['segment'];
        }

        if (!empty($filters['type_person'])) {
            $whereConditions[] = 'c.type_person = :type_person';
            $params[':type_person'] = $filters['type_person'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT c.*
                FROM sac_clients c
                {$whereClause}
                ORDER BY c.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getTotalClients(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = '(razao_social LIKE :search OR nome_fantasia LIKE :search OR document LIKE :search OR email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['segment'])) {
            $whereConditions[] = 'segment = :segment';
            $params[':segment'] = $filters['segment'];
        }

        if (!empty($filters['type_person'])) {
            $whereConditions[] = 'type_person = :type_person';
            $params[':type_person'] = $filters['type_person'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) as total FROM sac_clients {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function getClientById(int $id): ?array
    {
        $sql = "SELECT * FROM sac_clients WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function getActiveClients(): array
    {
        $sql = "SELECT id, razao_social, nome_fantasia, document, email
                FROM sac_clients
                WHERE status = 'Ativo'
                ORDER BY razao_social ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getNextCode(): string
    {
        $sql = "SELECT code FROM sac_clients ORDER BY id DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['code'])) {
            $number = (int)str_replace('SAC-', '', $result['code']);
            return 'SAC-' . str_pad((string)($number + 1), 4, '0', STR_PAD_LEFT);
        }

        return 'SAC-0001';
    }

    public function findByDocument(string $document): ?array
    {
        $sql = "SELECT * FROM sac_clients WHERE document = :document LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':document', $document, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function createClient(array $data): int|false
    {
        $sql = "INSERT INTO sac_clients (code, razao_social, nome_fantasia, type_person, document, contact_name, email, phone, mobile, segment, status, address, number, complement, neighborhood, city, state, zip_code, notes, created_at, updated_at)
                VALUES (:code, :razao_social, :nome_fantasia, :type_person, :document, :contact_name, :email, :phone, :mobile, :segment, :status, :address, :number, :complement, :neighborhood, :city, :state, :zip_code, :notes, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
        $stmt->bindValue(':razao_social', $data['razao_social'], PDO::PARAM_STR);
        $stmt->bindValue(':nome_fantasia', $data['nome_fantasia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':type_person', $data['type_person'] ?? 'PJ', PDO::PARAM_STR);
        $stmt->bindValue(':document', $data['document'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_name', $data['contact_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':mobile', $data['mobile'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':segment', $data['segment'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':number', $data['number'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':complement', $data['complement'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':neighborhood', $data['neighborhood'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':city', $data['city'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':state', $data['state'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':zip_code', $data['zip_code'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        if ($newId > 0) {
            $newData = $this->getClientById($newId);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_clients',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $newId;
    }

    public function updateClient(int $id, array $data): bool
    {
        $oldData = $this->getClientById($id);

        $sql = "UPDATE sac_clients
                SET razao_social = :razao_social, nome_fantasia = :nome_fantasia, type_person = :type_person,
                    document = :document, contact_name = :contact_name, email = :email, phone = :phone, mobile = :mobile,
                    segment = :segment, status = :status, address = :address, number = :number,
                    complement = :complement, neighborhood = :neighborhood, city = :city, state = :state,
                    zip_code = :zip_code, notes = :notes, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':razao_social', $data['razao_social'], PDO::PARAM_STR);
        $stmt->bindValue(':nome_fantasia', $data['nome_fantasia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':type_person', $data['type_person'] ?? 'PJ', PDO::PARAM_STR);
        $stmt->bindValue(':document', $data['document'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_name', $data['contact_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':mobile', $data['mobile'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':segment', $data['segment'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':number', $data['number'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':complement', $data['complement'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':neighborhood', $data['neighborhood'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':city', $data['city'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':state', $data['state'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':zip_code', $data['zip_code'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getClientById($id);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_clients',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    public function deleteClient(int $id): bool
    {
        $checkSql = "SELECT COUNT(*) as total FROM sac_tickets WHERE client_id = :client_id";
        $checkStmt = $this->getConnection()->prepare($checkSql);
        $checkStmt->bindValue(':client_id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ((int)$check['total'] > 0) {
            return false;
        }

        $oldData = $this->getClientById($id);

        $sql = "DELETE FROM sac_clients WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && is_array($oldData)) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_clients',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['razao_social', 'nome_fantasia', 'email', 'address', 'neighborhood', 'city', 'notes'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
}
