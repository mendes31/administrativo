<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstEquipamentoVistoriasRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT v.*, e.codigo AS equipamento_codigo, e.localizacao, t.nome AS tipo_nome,
                       u.name AS executor_nome, d.name AS departamento_nome, ur.name AS responsavel_nome
                FROM adms_sst_equipamento_vistorias v
                INNER JOIN adms_sst_equipamentos e ON e.id = v.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_users u ON u.id = v.executor_adms_user_id
                LEFT JOIN adms_users ur ON ur.id = e.responsavel_adms_user_id
                LEFT JOIN adms_departments d ON d.id = e.adms_department_id
                {$where}
                ORDER BY v.data_prevista DESC, v.id DESC
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
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total
                FROM adms_sst_equipamento_vistorias v
                INNER JOIN adms_sst_equipamentos e ON e.id = v.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT v.*, e.codigo AS equipamento_codigo, e.localizacao, e.adms_department_id,
                       e.responsavel_adms_user_id, e.adms_sst_equipamento_tipo_id,
                       t.nome AS tipo_nome, u.name AS executor_nome, d.name AS departamento_nome,
                       ur.name AS responsavel_nome
                FROM adms_sst_equipamento_vistorias v
                INNER JOIN adms_sst_equipamentos e ON e.id = v.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_users u ON u.id = v.executor_adms_user_id
                LEFT JOIN adms_users ur ON ur.id = e.responsavel_adms_user_id
                LEFT JOIN adms_departments d ON d.id = e.adms_department_id
                WHERE v.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function existsForCompetencia(int $equipamentoId, string $competencia): bool
    {
        $sql = 'SELECT 1 FROM adms_sst_equipamento_vistorias
                WHERE adms_sst_equipamento_id = :eq AND competencia = :comp LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eq', $equipamentoId, PDO::PARAM_INT);
        $stmt->bindValue(':comp', $competencia);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function hasPending(int $equipamentoId): bool
    {
        $sql = "SELECT 1 FROM adms_sst_equipamento_vistorias
                WHERE adms_sst_equipamento_id = :eq AND status IN ('Pendente','Em andamento','Vencida') LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eq', $equipamentoId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function getLastCompletedCompetencia(int $equipamentoId): ?string
    {
        $sql = "SELECT competencia FROM adms_sst_equipamento_vistorias
                WHERE adms_sst_equipamento_id = :eq AND status = 'Concluída'
                ORDER BY competencia DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eq', $equipamentoId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchColumn();

        return $row !== false ? (string) $row : null;
    }

    public function createWithRespostas(int $equipamentoId, string $competencia, string $dataPrevista, array $checklistItens): int|false
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();
            $sql = 'INSERT INTO adms_sst_equipamento_vistorias
                    (adms_sst_equipamento_id, competencia, data_prevista, status, created_at, updated_at)
                    VALUES (:eq, :comp, :prev, \'Pendente\', NOW(), NOW())';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':eq', $equipamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':comp', $competencia);
            $stmt->bindValue(':prev', $dataPrevista);
            if (!$stmt->execute()) {
                $conn->rollBack();

                return false;
            }
            $vistoriaId = (int) $conn->lastInsertId();
            $this->insertRespostasFromChecklist($vistoriaId, $checklistItens);
            $conn->commit();

            return $vistoriaId;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            return false;
        }
    }

    /** @param list<array<string, mixed>> $checklistItens */
    public function insertRespostasFromChecklist(int $vistoriaId, array $checklistItens): void
    {
        $sql = 'INSERT INTO adms_sst_equipamento_vistoria_respostas
                (adms_sst_equipamento_vistoria_id, adms_sst_equipamento_checklist_item_id,
                 descricao_snapshot, ordem, created_at, updated_at)
                VALUES (:vid, :cid, :desc, :ordem, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($checklistItens as $item) {
            $stmt->bindValue(':vid', $vistoriaId, PDO::PARAM_INT);
            $stmt->bindValue(':cid', (int) $item['id'], PDO::PARAM_INT);
            $stmt->bindValue(':desc', $item['descricao']);
            $stmt->bindValue(':ordem', (int) ($item['ordem'] ?? 0), PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /** @return list<array<string, mixed>> */
    public function getRespostas(int $vistoriaId): array
    {
        $sql = 'SELECT * FROM adms_sst_equipamento_vistoria_respostas
                WHERE adms_sst_equipamento_vistoria_id = :id
                ORDER BY ordem ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $vistoriaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markEmAndamento(int $vistoriaId, int $userId): bool
    {
        $sql = "UPDATE adms_sst_equipamento_vistorias SET
                status = 'Em andamento', executor_adms_user_id = COALESCE(executor_adms_user_id, :uid), updated_at = NOW()
                WHERE id = :id AND status IN ('Pendente','Vencida','Em andamento')";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $vistoriaId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /** @param list<array{id: int, resposta: ?string, observacao: ?string}> $respostas */
    public function saveRespostasAndConclude(int $vistoriaId, array $respostas, ?string $observacaoGeral, int $userId): bool
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();
            $sqlUp = 'UPDATE adms_sst_equipamento_vistoria_respostas SET resposta = :resp, observacao = :obs, updated_at = NOW() WHERE id = :id';
            $stmtUp = $conn->prepare($sqlUp);
            $hasNc = false;
            foreach ($respostas as $r) {
                $resp = $r['resposta'] ?? null;
                if ($resp === 'Não conforme') {
                    $hasNc = true;
                }
                $stmtUp->bindValue(':resp', $resp);
                $stmtUp->bindValue(':obs', $r['observacao'] ?? null);
                $stmtUp->bindValue(':id', (int) $r['id'], PDO::PARAM_INT);
                $stmtUp->execute();
            }
            $resultado = $hasNc ? 'Não conforme' : 'Conforme';
            $sqlV = "UPDATE adms_sst_equipamento_vistorias SET
                     status = 'Concluída', resultado = :resultado, observacao = :obs,
                     data_realizada = NOW(), executor_adms_user_id = :uid, updated_at = NOW()
                     WHERE id = :id";
            $stmtV = $conn->prepare($sqlV);
            $stmtV->bindValue(':resultado', $resultado);
            $stmtV->bindValue(':obs', $observacaoGeral);
            $stmtV->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmtV->bindValue(':id', $vistoriaId, PDO::PARAM_INT);
            $stmtV->execute();
            $conn->commit();

            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            return false;
        }
    }

    public function markOverdue(string $today): int
    {
        $sql = "UPDATE adms_sst_equipamento_vistorias SET status = 'Vencida', updated_at = NOW()
                WHERE status IN ('Pendente','Em andamento') AND data_prevista < :today";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':today', $today);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /** @return array{pendentes: int, vencidas: int, hoje: int} */
    public function countMinhasResumo(int $userId, ?int $userDepartmentId): array
    {
        $filters = [
            'minhas' => true,
            'adms_user_id' => $userId,
            'adms_department_id_user' => $userDepartmentId,
            'status_open' => true,
        ];
        [$where, $params] = $this->buildWhere($filters);
        $today = date('Y-m-d');
        $sql = "SELECT
                    SUM(CASE WHEN v.status IN ('Pendente','Em andamento') THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN v.status = 'Vencida' OR (v.status IN ('Pendente','Em andamento') AND v.data_prevista < :today) THEN 1 ELSE 0 END) AS vencidas,
                    SUM(CASE WHEN v.data_prevista = :today AND v.status IN ('Pendente','Em andamento','Vencida') THEN 1 ELSE 0 END) AS hoje
                FROM adms_sst_equipamento_vistorias v
                INNER JOIN adms_sst_equipamentos e ON e.id = v.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':today', $today);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pendentes' => (int) ($row['pendentes'] ?? 0),
            'vencidas' => (int) ($row['vencidas'] ?? 0),
            'hoje' => (int) ($row['hoje'] ?? 0),
        ];
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(e.codigo LIKE :search OR e.localizacao LIKE :search OR t.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['competencia'])) {
            $where[] = 'v.competencia = :competencia';
            $params[':competencia'] = $filters['competencia'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['resultado'])) {
            $where[] = 'v.resultado = :resultado';
            $params[':resultado'] = $filters['resultado'];
        }
        if (!empty($filters['adms_sst_equipamento_tipo_id'])) {
            $where[] = 'e.adms_sst_equipamento_tipo_id = :tipo_id';
            $params[':tipo_id'] = (int) $filters['adms_sst_equipamento_tipo_id'];
        }
        if (!empty($filters['status_open'])) {
            $where[] = "v.status IN ('Pendente','Em andamento','Vencida')";
        }
        if (!empty($filters['minhas'])) {
            $userId = (int) ($filters['adms_user_id'] ?? 0);
            $deptId = $filters['adms_department_id_user'] ?? null;
            $parts = [];
            if ($userId > 0) {
                $parts[] = 'e.responsavel_adms_user_id = :minhas_user';
                $params[':minhas_user'] = $userId;
            }
            if ($deptId) {
                $parts[] = '(e.responsavel_adms_user_id IS NULL AND e.adms_department_id = :minhas_dept)';
                $params[':minhas_dept'] = (int) $deptId;
            }
            $parts[] = '(e.responsavel_adms_user_id IS NULL AND e.adms_department_id IS NULL)';
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }
}
