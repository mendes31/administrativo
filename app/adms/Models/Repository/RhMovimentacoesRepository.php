<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhMovimentacoesRepository extends DbConnection
{
    public const TIPOS = [
        'transferencia',
        'promocao',
        'alteracao_cargo',
        'alteracao_departamento',
        'alteracao_gestor',
        'outros',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = 'SELECT m.*,
                           u.name AS usuario_nome,
                           u.email AS usuario_email,
                           da.name AS dep_antes_nome,
                           dd.name AS dep_depois_nome,
                           ca.name AS cargo_antes_nome,
                           cd.name AS cargo_depois_nome,
                           ga.name AS gestor_antes_nome,
                           gd.name AS gestor_depois_nome
                    FROM rh_movimentacoes m
                    INNER JOIN adms_users u ON u.id = m.adms_user_id
                    LEFT JOIN adms_departments da ON da.id = m.departamento_id_antes
                    LEFT JOIN adms_departments dd ON dd.id = m.departamento_id_depois
                    LEFT JOIN adms_positions ca ON ca.id = m.cargo_id_antes
                    LEFT JOIN adms_positions cd ON cd.id = m.cargo_id_depois
                    LEFT JOIN adms_users ga ON ga.id = m.gestor_id_antes
                    LEFT JOIN adms_users gd ON gd.id = m.gestor_id_depois
                    WHERE m.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar movimentação.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function list(array $filters, int $page, int $perPage): array
    {
        try {
            $where = ['1=1'];
            $params = [];
            if (!empty($filters['adms_user_id'])) {
                $where[] = 'm.adms_user_id = :user_id';
                $params[':user_id'] = (int) $filters['adms_user_id'];
            }
            if (!empty($filters['tipo'])) {
                $where[] = 'm.tipo = :tipo';
                $params[':tipo'] = (string) $filters['tipo'];
            }
            $whereSql = implode(' AND ', $where);

            $countStmt = $this->getConnection()->prepare(
                "SELECT COUNT(*) FROM rh_movimentacoes m WHERE {$whereSql}"
            );
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $offset = max(0, ($page - 1) * $perPage);
            $sql = "SELECT m.*, u.name AS usuario_nome
                    FROM rh_movimentacoes m
                    INNER JOIN adms_users u ON u.id = m.adms_user_id
                    WHERE {$whereSql}
                    ORDER BY m.data_vigencia DESC, m.id DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
                'total' => $total,
            ];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar movimentações.', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int|false
    {
        try {
            $sql = 'INSERT INTO rh_movimentacoes
                        (adms_user_id, tipo, data_vigencia,
                         departamento_id_antes, departamento_id_depois,
                         cargo_id_antes, cargo_id_depois,
                         gestor_id_antes, gestor_id_depois,
                         motivo, observacoes, created_by_user_id, created_at, updated_at)
                    VALUES
                        (:user_id, :tipo, :vigencia,
                         :dep_antes, :dep_depois,
                         :cargo_antes, :cargo_depois,
                         :gestor_antes, :gestor_depois,
                         :motivo, :observacoes, :created_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':tipo', (string) $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':vigencia', (string) $data['data_vigencia'], PDO::PARAM_STR);
            $this->bindNullableInt($stmt, ':dep_antes', $data['departamento_id_antes'] ?? null);
            $this->bindNullableInt($stmt, ':dep_depois', $data['departamento_id_depois'] ?? null);
            $this->bindNullableInt($stmt, ':cargo_antes', $data['cargo_id_antes'] ?? null);
            $this->bindNullableInt($stmt, ':cargo_depois', $data['cargo_id_depois'] ?? null);
            $this->bindNullableInt($stmt, ':gestor_antes', $data['gestor_id_antes'] ?? null);
            $this->bindNullableInt($stmt, ':gestor_depois', $data['gestor_id_depois'] ?? null);
            $stmt->bindValue(':motivo', (string) $data['motivo'], PDO::PARAM_STR);
            $obs = $data['observacoes'] ?? null;
            $stmt->bindValue(
                ':observacoes',
                $obs !== null && $obs !== '' ? $obs : null,
                $obs !== null && $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $by = $data['created_by_user_id'] ?? null;
            $this->bindNullableInt($stmt, ':created_by', $by);

            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao criar movimentação.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function aplicarLotacaoUsuario(
        int $userId,
        int $departamentoId,
        int $cargoId,
        ?int $gestorId
    ): bool {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE adms_users
                 SET user_department_id = :dep,
                     user_position_id = :cargo,
                     immediate_supervisor_id = :gestor,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':dep', $departamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':cargo', $cargoId, PDO::PARAM_INT);
            $stmt->bindValue(
                ':gestor',
                $gestorId,
                $gestorId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao aplicar lotação na movimentação.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function bindNullableInt(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
    }
}
