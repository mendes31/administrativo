<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhIdentidadeRepository extends DbConnection
{
    public const VINCULO_ATIVO = 'ativo';
    public const VINCULO_ENCERRADO = 'encerrado';

    /**
     * @return array<string, mixed>|null
     */
    public function getPessoaById(int $id): ?array
    {
        try {
            $sql = 'SELECT p.*,
                           u.name AS conta_nome,
                           u.email AS conta_email,
                           u.status AS conta_status
                    FROM rh_pessoas p
                    LEFT JOIN adms_users u ON u.id = p.adms_user_id
                    WHERE p.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar pessoa.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPessoaByUserId(int $userId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_pessoas WHERE adms_user_id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function listPessoas(array $filters, int $page, int $perPage): array
    {
        try {
            $where = ['1=1'];
            $params = [];
            if (!empty($filters['q'])) {
                $where[] = '(p.nome LIKE :q OR p.email LIKE :q OR p.cpf LIKE :q)';
                $params[':q'] = '%' . trim((string) $filters['q']) . '%';
            }
            $whereSql = implode(' AND ', $where);

            $countStmt = $this->getConnection()->prepare(
                "SELECT COUNT(*) FROM rh_pessoas p WHERE {$whereSql}"
            );
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $offset = max(0, ($page - 1) * $perPage);
            $sql = "SELECT p.*,
                           v.status AS vinculo_status,
                           v.data_inicio AS vinculo_inicio,
                           v.data_fim AS vinculo_fim
                    FROM rh_pessoas p
                    LEFT JOIN rh_vinculos v ON v.adms_user_id = p.adms_user_id
                         AND v.id = (
                             SELECT MAX(v2.id) FROM rh_vinculos v2 WHERE v2.adms_user_id = p.adms_user_id
                         )
                    WHERE {$whereSql}
                    ORDER BY p.nome ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
                'total' => $total,
            ];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar pessoas.', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVinculoAtualByUserId(int $userId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_vinculos
                 WHERE adms_user_id = :id
                 ORDER BY CASE WHEN status = \'ativo\' THEN 0 ELSE 1 END, id DESC
                 LIMIT 1'
            );
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLotacaoVigente(int $vinculoId): ?array
    {
        try {
            $sql = 'SELECT l.*,
                           d.name AS departamento_nome,
                           c.name AS cargo_nome,
                           g.name AS gestor_nome
                    FROM rh_lotacoes l
                    LEFT JOIN adms_departments d ON d.id = l.departamento_id
                    LEFT JOIN adms_positions c ON c.id = l.cargo_id
                    LEFT JOIN adms_users g ON g.id = l.gestor_user_id
                    WHERE l.rh_vinculo_id = :id AND l.vigente = 1
                    ORDER BY l.id DESC
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $vinculoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertPessoaFromUser(array $data): int|false
    {
        try {
            $userId = (int) $data['adms_user_id'];
            $existing = $this->getPessoaByUserId($userId);
            $cpf = $this->normalizeCpf($data['cpf'] ?? null);

            if ($existing) {
                $stmt = $this->getConnection()->prepare(
                    'UPDATE rh_pessoas
                     SET cpf = :cpf, nome = :nome, email = :email,
                         data_nascimento = :nasc, sexo = :sexo, updated_at = NOW()
                     WHERE id = :id'
                );
                $this->bindNullableStr($stmt, ':cpf', $cpf);
                $stmt->bindValue(':nome', (string) $data['nome'], PDO::PARAM_STR);
                $this->bindNullableStr($stmt, ':email', $data['email'] ?? null);
                $this->bindNullableStr($stmt, ':nasc', $data['data_nascimento'] ?? null);
                $this->bindNullableStr($stmt, ':sexo', $data['sexo'] ?? null);
                $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    return false;
                }

                return (int) $existing['id'];
            }

            $stmt = $this->getConnection()->prepare(
                'INSERT INTO rh_pessoas
                    (adms_user_id, cpf, nome, email, data_nascimento, sexo, created_at, updated_at)
                 VALUES
                    (:user_id, :cpf, :nome, :email, :nasc, :sexo, NOW(), NOW())'
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $this->bindNullableStr($stmt, ':cpf', $cpf);
            $stmt->bindValue(':nome', (string) $data['nome'], PDO::PARAM_STR);
            $this->bindNullableStr($stmt, ':email', $data['email'] ?? null);
            $this->bindNullableStr($stmt, ':nasc', $data['data_nascimento'] ?? null);
            $this->bindNullableStr($stmt, ':sexo', $data['sexo'] ?? null);
            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao upsert pessoa.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertVinculoAtivo(array $data): int|false
    {
        try {
            $userId = (int) $data['adms_user_id'];
            $pessoaId = (int) $data['rh_pessoa_id'];
            $status = (string) ($data['status'] ?? self::VINCULO_ATIVO);
            $atual = $this->getVinculoAtualByUserId($userId);

            if ($atual && ($atual['status'] ?? '') === self::VINCULO_ATIVO) {
                $stmt = $this->getConnection()->prepare(
                    'UPDATE rh_vinculos
                     SET status = :status, data_inicio = :inicio, data_fim = :fim, updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                $this->bindNullableStr($stmt, ':inicio', $data['data_inicio'] ?? null);
                $this->bindNullableStr($stmt, ':fim', $data['data_fim'] ?? null);
                $stmt->bindValue(':id', (int) $atual['id'], PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    return false;
                }

                return (int) $atual['id'];
            }

            if ($atual && $status === self::VINCULO_ENCERRADO) {
                $stmt = $this->getConnection()->prepare(
                    'UPDATE rh_vinculos
                     SET status = :status, data_fim = :fim, updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->bindValue(':status', self::VINCULO_ENCERRADO, PDO::PARAM_STR);
                $this->bindNullableStr($stmt, ':fim', $data['data_fim'] ?? null);
                $stmt->bindValue(':id', (int) $atual['id'], PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    return false;
                }

                return (int) $atual['id'];
            }

            $stmt = $this->getConnection()->prepare(
                'INSERT INTO rh_vinculos
                    (rh_pessoa_id, adms_user_id, status, data_inicio, data_fim, created_at, updated_at)
                 VALUES
                    (:pessoa_id, :user_id, :status, :inicio, :fim, NOW(), NOW())'
            );
            $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $this->bindNullableStr($stmt, ':inicio', $data['data_inicio'] ?? null);
            $this->bindNullableStr($stmt, ':fim', $data['data_fim'] ?? null);
            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao upsert vínculo.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sincronizarLotacaoVigente(int $vinculoId, array $data): bool
    {
        try {
            $pdo = $this->getConnection();
            $atual = $this->getLotacaoVigente($vinculoId);
            $dep = isset($data['departamento_id']) && (int) $data['departamento_id'] > 0
                ? (int) $data['departamento_id'] : null;
            $cargo = isset($data['cargo_id']) && (int) $data['cargo_id'] > 0
                ? (int) $data['cargo_id'] : null;
            $gestor = isset($data['gestor_user_id']) && (int) $data['gestor_user_id'] > 0
                ? (int) $data['gestor_user_id'] : null;

            if ($atual) {
                $same = (int) ($atual['departamento_id'] ?? 0) === (int) ($dep ?? 0)
                    && (int) ($atual['cargo_id'] ?? 0) === (int) ($cargo ?? 0)
                    && (int) ($atual['gestor_user_id'] ?? 0) === (int) ($gestor ?? 0);
                if ($same) {
                    return true;
                }
                $close = $pdo->prepare(
                    'UPDATE rh_lotacoes
                     SET vigente = 0, data_fim = COALESCE(:fim, CURDATE()), updated_at = NOW()
                     WHERE id = :id'
                );
                $this->bindNullableStr($close, ':fim', $data['data_inicio'] ?? null);
                $close->bindValue(':id', (int) $atual['id'], PDO::PARAM_INT);
                $close->execute();
            }

            $stmt = $pdo->prepare(
                'INSERT INTO rh_lotacoes
                    (rh_vinculo_id, departamento_id, cargo_id, gestor_user_id, vigente,
                     data_inicio, data_fim, created_at, updated_at)
                 VALUES
                    (:vinculo_id, :dep, :cargo, :gestor, 1, :inicio, NULL, NOW(), NOW())'
            );
            $stmt->bindValue(':vinculo_id', $vinculoId, PDO::PARAM_INT);
            $stmt->bindValue(':dep', $dep, $dep !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':cargo', $cargo, $cargo !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':gestor', $gestor, $gestor !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $this->bindNullableStr($stmt, ':inicio', $data['data_inicio'] ?? date('Y-m-d'));

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao sincronizar lotação.', [
                'vinculo_id' => $vinculoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function encerrarLotacoesDoVinculo(int $vinculoId, ?string $dataFim): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE rh_lotacoes
                 SET vigente = 0, data_fim = COALESCE(:fim, CURDATE()), updated_at = NOW()
                 WHERE rh_vinculo_id = :id AND vigente = 1'
            );
            $this->bindNullableStr($stmt, ':fim', $dataFim);
            $stmt->bindValue(':id', $vinculoId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function normalizeCpf(mixed $cpf): ?string
    {
        if ($cpf === null || $cpf === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $cpf);

        return $digits !== null && $digits !== '' ? $digits : null;
    }

    private function bindNullableStr(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, (string) $value, PDO::PARAM_STR);
    }
}
