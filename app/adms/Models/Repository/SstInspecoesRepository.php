<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstInspecoesRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, d.name AS departamento_nome, u.name AS inspetor_nome,
                       (SELECT COUNT(*) FROM adms_sst_inspecao_itens i WHERE i.adms_sst_inspecao_id = t.id) AS total_itens,
                       (SELECT COUNT(*) FROM adms_sst_inspecao_itens i WHERE i.adms_sst_inspecao_id = t.id AND i.classificacao = 'Não conforme' AND i.status <> 'Concluído') AS nao_conformes_abertas
                FROM adms_sst_inspecoes t
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_users u ON u.id = t.inspetor_adms_user_id
                {$whereClause}
                ORDER BY t.data_inspecao DESC, t.id DESC
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_inspecoes t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, d.name AS departamento_nome, u.name AS inspetor_nome
                FROM adms_sst_inspecoes t
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_users u ON u.id = t.inspetor_adms_user_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getItens(int $inspecaoId): array
    {
        $sql = "SELECT i.*, u.name AS responsavel_nome
                FROM adms_sst_inspecao_itens i
                LEFT JOIN adms_users u ON u.id = i.responsavel_adms_user_id
                WHERE i.adms_sst_inspecao_id = :id
                ORDER BY FIELD(i.classificacao, 'Não conforme', 'Observação', 'Conforme'), i.id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $inspecaoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_inspecoes
                (titulo, tipo, data_inspecao, adms_department_id, local, inspetor_adms_user_id, participantes,
                 descricao, conclusao, status, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:titulo, :tipo, :data_inspecao, :adms_department_id, :local, :inspetor_adms_user_id, :participantes,
                        :descricao, :conclusao, :status, :observacoes, :created_by, :updated_by, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindInspecao($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE adms_sst_inspecoes SET
                    titulo = :titulo, tipo = :tipo, data_inspecao = :data_inspecao,
                    adms_department_id = :adms_department_id, local = :local,
                    inspetor_adms_user_id = :inspetor_adms_user_id, participantes = :participantes,
                    descricao = :descricao, conclusao = :conclusao, status = :status,
                    observacoes = :observacoes, updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindInspecao($stmt, $data);
        $stmt->bindValue(':updated_by', (int) ($_SESSION['user_id'] ?? 1), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM adms_sst_inspecoes WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function addItem(int $inspecaoId, array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_inspecao_itens
                (adms_sst_inspecao_id, descricao, classificacao, acao_corretiva, responsavel_adms_user_id, prazo, status, created_by, created_at)
                VALUES (:inspecao_id, :descricao, :classificacao, :acao_corretiva, :responsavel, :prazo, :status, :created_by, NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inspecao_id', $inspecaoId, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', (string) ($data['descricao'] ?? ''));
        $stmt->bindValue(':classificacao', (string) ($data['classificacao'] ?? 'Observação'));
        $this->bindNull($stmt, ':acao_corretiva', $data['acao_corretiva'] ?? null);
        $this->bindNull($stmt, ':responsavel', $data['responsavel_adms_user_id'] ?? null, true);
        $this->bindNull($stmt, ':prazo', $data['prazo'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));
        $stmt->bindValue(':created_by', (int) ($_SESSION['user_id'] ?? 1), PDO::PARAM_INT);

        return $stmt->execute() ? (int) $this->getConnection()->lastInsertId() : false;
    }

    public function updateItem(int $itemId, array $data): bool
    {
        $sql = "UPDATE adms_sst_inspecao_itens SET
                    descricao = :descricao, classificacao = :classificacao, acao_corretiva = :acao_corretiva,
                    responsavel_adms_user_id = :responsavel, prazo = :prazo, status = :status
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', (string) ($data['descricao'] ?? ''));
        $stmt->bindValue(':classificacao', (string) ($data['classificacao'] ?? 'Observação'));
        $this->bindNull($stmt, ':acao_corretiva', $data['acao_corretiva'] ?? null);
        $this->bindNull($stmt, ':responsavel', $data['responsavel_adms_user_id'] ?? null, true);
        $this->bindNull($stmt, ':prazo', $data['prazo'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Pendente'));

        return $stmt->execute();
    }

    public function deleteItem(int $itemId): bool
    {
        $sql = 'DELETE FROM adms_sst_inspecao_itens WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function countAbertas(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_inspecoes WHERE status IN ('Aberta', 'Em tratamento')";
        return (int) ($this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.titulo LIKE :search OR t.local LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['tipo'])) {
            $where[] = 't.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function bindInspecao(\PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':titulo', (string) ($data['titulo'] ?? ''));
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'Rotina'));
        $stmt->bindValue(':data_inspecao', (string) ($data['data_inspecao'] ?? date('Y-m-d')));
        $this->bindNull($stmt, ':adms_department_id', $data['adms_department_id'] ?? null, true);
        $this->bindNull($stmt, ':local', $data['local'] ?? null);
        $this->bindNull($stmt, ':inspetor_adms_user_id', $data['inspetor_adms_user_id'] ?? null, true);
        $this->bindNull($stmt, ':participantes', $data['participantes'] ?? null);
        $this->bindNull($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindNull($stmt, ':conclusao', $data['conclusao'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Aberta'));
        $this->bindNull($stmt, ':observacoes', $data['observacoes'] ?? null);
    }

    private function bindNull(\PDOStatement $stmt, string $param, mixed $value, bool $int = false): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, $int ? (int) $value : (string) $value, $int ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}
