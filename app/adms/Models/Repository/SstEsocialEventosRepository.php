<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEsocialEventosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, u.name AS colaborador_nome
                FROM adms_sst_esocial_eventos t
                LEFT JOIN adms_users u ON u.id = t.adms_user_id
                {$whereClause}
                ORDER BY t.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_esocial_eventos t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name AS colaborador_nome, u.cpf AS colaborador_cpf
                FROM adms_sst_esocial_eventos t
                LEFT JOIN adms_users u ON u.id = t.adms_user_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByOrigem(string $tipoEvento, string $origemTabela, int $origemId): ?array
    {
        $sql = "SELECT * FROM adms_sst_esocial_eventos
                WHERE tipo_evento = :tipo AND origem_tabela = :tab AND origem_id = :oid
                  AND status <> 'Cancelado'
                ORDER BY id DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tipo', $tipoEvento);
        $stmt->bindValue(':tab', $origemTabela);
        $stmt->bindValue(':oid', $origemId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function countByStatus(string $status): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM adms_sst_esocial_eventos WHERE status = :status';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_esocial_eventos
                (tipo_evento, origem_tabela, origem_id, adms_user_id, payload_json, status,
                 protocolo, mensagem_retorno, data_geracao, data_envio, created_by, updated_by, created_at, updated_at)
                VALUES (:tipo_evento, :origem_tabela, :origem_id, :adms_user_id, :payload_json, :status,
                        :protocolo, :mensagem_retorno, :data_geracao, :data_envio, :created_by, :updated_by, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindPersist($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_esocial_eventos', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_esocial_eventos SET
                    payload_json = :payload_json, status = :status, protocolo = :protocolo,
                    mensagem_retorno = :mensagem_retorno, data_geracao = :data_geracao, data_envio = :data_envio,
                    updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindPersist($stmt, $data, false);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();

        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_esocial_eventos', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    private function bindPersist(\PDOStatement $stmt, array $data, bool $includeOrigem = true): void
    {
        if ($includeOrigem) {
            $stmt->bindValue(':tipo_evento', (string) $data['tipo_evento']);
            $stmt->bindValue(':origem_tabela', (string) $data['origem_tabela']);
            $stmt->bindValue(':origem_id', (int) $data['origem_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
        }
        $this->bindNullable($stmt, ':payload_json', $data['payload_json'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $this->bindNullable($stmt, ':protocolo', $data['protocolo'] ?? null);
        $this->bindNullable($stmt, ':mensagem_retorno', $data['mensagem_retorno'] ?? null);
        $this->bindNullable($stmt, ':data_geracao', $data['data_geracao'] ?? null);
        $this->bindNullable($stmt, ':data_envio', $data['data_envio'] ?? null);
    }

    private function bindNullable(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, (string) $value);
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['tipo_evento'])) {
            $where[] = 't.tipo_evento = :tipo_evento';
            $params[':tipo_evento'] = $filters['tipo_evento'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 't.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }
}
