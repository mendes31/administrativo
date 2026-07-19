<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class RhPersonnelRequestsRepository extends DbConnection
{
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    public function create(array $data): int|bool
    {
        try {
            $justificativa = trim((string) ($data['justificativa'] ?? ''));
            if ($justificativa === '') {
                throw new Exception('Justificativa é obrigatória.');
            }

            $requesterId = (int) ($data['requester_id'] ?? ($_SESSION['user_id'] ?? 0));
            if ($requesterId <= 0) {
                throw new Exception('Solicitante inválido.');
            }

            $sql = 'INSERT INTO rh_personnel_requests
                        (requester_id, area_id, cargo_id, quantidade, tipo_contrato, motivo_tipo,
                         justificativa, data_desejada, salario_min, salario_max, status, created_at)
                    VALUES
                        (:requester_id, :area_id, :cargo_id, :quantidade, :tipo_contrato, :motivo_tipo,
                         :justificativa, :data_desejada, :salario_min, :salario_max, :status, NOW())';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':requester_id', $requesterId, PDO::PARAM_INT);
            $stmt->bindValue(':area_id', !empty($data['area_id']) ? (int) $data['area_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':cargo_id', !empty($data['cargo_id']) ? (int) $data['cargo_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':quantidade', max(1, (int) ($data['quantidade'] ?? 1)), PDO::PARAM_INT);
            $stmt->bindValue(':tipo_contrato', $data['tipo_contrato'] ?? 'CLT', PDO::PARAM_STR);
            $stmt->bindValue(':motivo_tipo', $data['motivo_tipo'] ?? 'aumento', PDO::PARAM_STR);
            $stmt->bindValue(':justificativa', $justificativa, PDO::PARAM_STR);
            $stmt->bindValue(
                ':data_desejada',
                !empty($data['data_desejada']) ? $data['data_desejada'] : null,
                !empty($data['data_desejada']) ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':salario_min',
                $data['salario_min'] !== null && $data['salario_min'] !== '' ? $data['salario_min'] : null,
                PDO::PARAM_STR
            );
            $stmt->bindValue(
                ':salario_max',
                $data['salario_max'] !== null && $data['salario_max'] !== '' ? $data['salario_max'] : null,
                PDO::PARAM_STR
            );
            $stmt->bindValue(':status', self::STATUS_PENDING, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                return false;
            }

            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0 && !empty($_SESSION['user_id'])) {
                LogAlteracaoService::registrarAlteracao(
                    'rh_personnel_requests',
                    $id,
                    (int) $_SESSION['user_id'],
                    'INSERT',
                    [],
                    array_merge($data, ['id' => $id, 'status' => self::STATUS_PENDING])
                );
            }

            return $id;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao criar requisição de pessoal.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT r.*,
                       u.name AS requester_nome,
                       a.name AS area_nome,
                       p.name AS cargo_nome,
                       ap.name AS approved_by_nome
                FROM rh_personnel_requests r
                LEFT JOIN adms_users u ON u.id = r.requester_id
                LEFT JOIN adms_departments a ON a.id = r.area_id
                LEFT JOIN adms_positions p ON p.id = r.cargo_id
                LEFT JOIN adms_users ap ON ap.id = r.approved_by
                WHERE r.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function getAll(array $filters, int $page, int $perPage): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['area_id'])) {
            $where[] = 'r.area_id = :area_id';
            $params[':area_id'] = (int) $filters['area_id'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $perPage);

        $sqlCount = "SELECT COUNT(*) FROM rh_personnel_requests r WHERE {$whereSql}";
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        $sql = "SELECT r.*,
                       u.name AS requester_nome,
                       a.name AS area_nome,
                       p.name AS cargo_nome
                FROM rh_personnel_requests r
                LEFT JOIN adms_users u ON u.id = r.requester_id
                LEFT JOIN adms_departments a ON a.id = r.area_id
                LEFT JOIN adms_positions p ON p.id = r.cargo_id
                WHERE {$whereSql}
                ORDER BY r.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
        ];
    }

    public function lockById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM rh_personnel_requests WHERE id = :id FOR UPDATE'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateStatus(
        int $id,
        string $status,
        ?int $approvedBy = null,
        ?string $rejectionReason = null,
        ?int $convertedVagaId = null
    ): bool {
        $sql = 'UPDATE rh_personnel_requests
                SET status = :status,
                    approved_by = :approved_by,
                    approved_at = CASE WHEN :status_check IN (\'approved\', \'rejected\') THEN NOW() ELSE approved_at END,
                    rejection_reason = :rejection_reason,
                    converted_vaga_id = COALESCE(:converted_vaga_id, converted_vaga_id),
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':status_check', $status, PDO::PARAM_STR);
        $stmt->bindValue(':approved_by', $approvedBy, $approvedBy !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(
            ':rejection_reason',
            $rejectionReason,
            $rejectionReason !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':converted_vaga_id',
            $convertedVagaId,
            $convertedVagaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
