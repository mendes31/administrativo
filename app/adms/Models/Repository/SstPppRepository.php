<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstPppRepository extends DbConnection
{
    public function getByUserId(int $userId, int $limit = 20): array
    {
        $sql = "SELECT p.*, u.name AS colaborador_nome, g.name AS gerado_por_nome
                FROM adms_sst_ppp p
                LEFT JOIN adms_users u ON u.id = p.adms_user_id
                LEFT JOIN adms_users g ON g.id = p.gerado_por
                WHERE p.adms_user_id = :uid
                ORDER BY p.versao DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'p.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'u.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT p.*, u.name AS colaborador_nome, g.name AS gerado_por_nome
                FROM adms_sst_ppp p
                LEFT JOIN adms_users u ON u.id = p.adms_user_id
                LEFT JOIN adms_users g ON g.id = p.gerado_por
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $k === ':uid' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        $where = [];
        $params = [];
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'p.adms_user_id = :uid';
            $params[':uid'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'u.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_ppp p LEFT JOIN adms_users u ON u.id = p.adms_user_id {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, str_contains($k, 'uid') ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, u.name AS colaborador_nome, u.cpf AS colaborador_cpf, g.name AS gerado_por_nome
                FROM adms_sst_ppp p
                LEFT JOIN adms_users u ON u.id = p.adms_user_id
                LEFT JOIN adms_users g ON g.id = p.gerado_por
                WHERE p.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getNextVersao(int $userId): int
    {
        $sql = 'SELECT COALESCE(MAX(versao), 0) + 1 AS prox FROM adms_sst_ppp WHERE adms_user_id = :uid';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['prox'] ?? 1);
    }

    public function create(int $userId, int $versao, string $payloadJson, ?string $observacoes = null): int|false
    {
        $sql = "INSERT INTO adms_sst_ppp (adms_user_id, versao, payload_json, observacoes, gerado_por, created_at)
                VALUES (:uid, :versao, :payload, :obs, :gerado_por, NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':versao', $versao, PDO::PARAM_INT);
        $stmt->bindValue(':payload', $payloadJson);
        $stmt->bindValue(':obs', $observacoes);
        $stmt->bindValue(':gerado_por', (int) ($_SESSION['user_id'] ?? 1), PDO::PARAM_INT);

        return $stmt->execute() ? (int) $this->getConnection()->lastInsertId() : false;
    }
}
