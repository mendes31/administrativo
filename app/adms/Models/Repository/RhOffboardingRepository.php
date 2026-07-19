<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhOffboardingRepository extends DbConnection
{
    public const TIPOS = [
        'pedido_demissao',
        'demissao_sem_justa_causa',
        'demissao_justa_causa',
        'termino_contrato',
        'aposentadoria',
        'outros',
    ];

    public const STATUS_EM_ANDAMENTO = 'em_andamento';
    public const STATUS_CONCLUIDO = 'concluido';
    public const STATUS_CANCELADO = 'cancelado';

    public const ITEM_PENDENTE = 'pendente';
    public const ITEM_EM_ANDAMENTO = 'em_andamento';
    public const ITEM_CONCLUIDO = 'concluido';
    public const ITEM_DISPENSADO = 'dispensado';

    /**
     * @return array<string, mixed>|null
     */
    public function getPlanoById(int $id): ?array
    {
        try {
            $sql = 'SELECT p.*,
                           u.name AS usuario_nome,
                           u.email AS usuario_email,
                           u.status AS usuario_status,
                           u.data_desligamento AS usuario_data_desligamento
                    FROM rh_offboarding_planos p
                    INNER JOIN adms_users u ON u.id = p.adms_user_id
                    WHERE p.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar plano de offboarding.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPlanoEmAndamentoByUser(int $userId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_offboarding_planos
                 WHERE adms_user_id = :user_id AND status = :status
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':status', self::STATUS_EM_ANDAMENTO, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar offboarding em andamento.', [
                'user_id' => $userId,
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
                $where[] = 'p.adms_user_id = :user_id';
                $params[':user_id'] = (int) $filters['adms_user_id'];
            }
            if (!empty($filters['status'])) {
                $where[] = 'p.status = :status';
                $params[':status'] = (string) $filters['status'];
            }
            if (!empty($filters['tipo'])) {
                $where[] = 'p.tipo = :tipo';
                $params[':tipo'] = (string) $filters['tipo'];
            }
            $whereSql = implode(' AND ', $where);

            $countStmt = $this->getConnection()->prepare(
                "SELECT COUNT(*) FROM rh_offboarding_planos p WHERE {$whereSql}"
            );
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $offset = max(0, ($page - 1) * $perPage);
            $sql = "SELECT p.*, u.name AS usuario_nome
                    FROM rh_offboarding_planos p
                    INNER JOIN adms_users u ON u.id = p.adms_user_id
                    WHERE {$whereSql}
                    ORDER BY p.id DESC
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
            GenerateLog::generateLog('error', 'Erro ao listar offboardings.', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPlano(array $data): int|false
    {
        try {
            $sql = 'INSERT INTO rh_offboarding_planos
                        (adms_user_id, tipo, status, data_prevista, motivo, tipo_impacto,
                         observacoes, created_by_user_id, created_at, updated_at)
                    VALUES
                        (:user_id, :tipo, :status, :data_prevista, :motivo, :tipo_impacto,
                         :observacoes, :created_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':tipo', (string) $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_EM_ANDAMENTO), PDO::PARAM_STR);
            $prevista = $data['data_prevista'] ?? null;
            $stmt->bindValue(
                ':data_prevista',
                $prevista !== null && $prevista !== '' ? $prevista : null,
                $prevista !== null && $prevista !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':motivo', (string) $data['motivo'], PDO::PARAM_STR);
            $impacto = $data['tipo_impacto'] ?? null;
            $stmt->bindValue(
                ':tipo_impacto',
                $impacto !== null && $impacto !== '' ? $impacto : null,
                $impacto !== null && $impacto !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $obs = $data['observacoes'] ?? null;
            $stmt->bindValue(
                ':observacoes',
                $obs !== null && $obs !== '' ? $obs : null,
                $obs !== null && $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $by = $data['created_by_user_id'] ?? null;
            $stmt->bindValue(
                ':created_by',
                $by !== null ? (int) $by : null,
                $by !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );

            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao criar plano de offboarding.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param list<array{codigo: string, titulo: string, obrigatorio: bool}> $itens
     */
    public function seedItens(int $planoId, array $itens): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO rh_offboarding_itens
                    (rh_offboarding_plano_id, codigo, titulo, obrigatorio, status, created_at, updated_at)
                 VALUES
                    (:plano_id, :codigo, :titulo, :obrigatorio, :status, NOW(), NOW())'
            );
            foreach ($itens as $item) {
                $stmt->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
                $stmt->bindValue(':codigo', $item['codigo'], PDO::PARAM_STR);
                $stmt->bindValue(':titulo', $item['titulo'], PDO::PARAM_STR);
                $stmt->bindValue(':obrigatorio', !empty($item['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':status', self::ITEM_PENDENTE, PDO::PARAM_STR);
                $stmt->execute();
            }

            return true;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao semear itens de offboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listItens(int $planoId): array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_offboarding_itens
                 WHERE rh_offboarding_plano_id = :id
                 ORDER BY obrigatorio DESC, id ASC'
            );
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar itens de offboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function updateItemStatus(
        int $itemId,
        int $planoId,
        string $status,
        ?string $observacoes,
        ?int $userId
    ): bool {
        $allowed = [
            self::ITEM_PENDENTE,
            self::ITEM_EM_ANDAMENTO,
            self::ITEM_CONCLUIDO,
            self::ITEM_DISPENSADO,
        ];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        try {
            $done = in_array($status, [self::ITEM_CONCLUIDO, self::ITEM_DISPENSADO], true);
            $sql = 'UPDATE rh_offboarding_itens
                    SET status = :status,
                        observacoes = :observacoes,
                        completed_at = ' . ($done ? 'NOW()' : 'NULL') . ',
                        completed_by_user_id = :completed_by,
                        updated_at = NOW()
                    WHERE id = :id AND rh_offboarding_plano_id = :plano_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(
                ':observacoes',
                $observacoes !== null && $observacoes !== '' ? $observacoes : null,
                $observacoes !== null && $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':completed_by',
                $done && $userId !== null ? $userId : null,
                $done && $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $stmt->bindValue(':plano_id', $planoId, PDO::PARAM_INT);

            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar item de offboarding.', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updatePlanoStatus(int $planoId, string $status): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE rh_offboarding_planos SET status = :status, updated_at = NOW() WHERE id = :id'
            );
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar status do offboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function marcarConcluido(
        int $planoId,
        string $dataDesligamento,
        ?int $actorId
    ): bool {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE rh_offboarding_planos
                 SET status = :status,
                     data_desligamento = :data_desligamento,
                     concluded_by_user_id = :actor,
                     concluded_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id AND status = :em_andamento'
            );
            $stmt->bindValue(':status', self::STATUS_CONCLUIDO, PDO::PARAM_STR);
            $stmt->bindValue(':data_desligamento', $dataDesligamento, PDO::PARAM_STR);
            $stmt->bindValue(
                ':actor',
                $actorId !== null ? $actorId : null,
                $actorId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);
            $stmt->bindValue(':em_andamento', self::STATUS_EM_ANDAMENTO, PDO::PARAM_STR);

            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao concluir offboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function aplicarDesligamentoUsuario(
        int $userId,
        string $dataDesligamento,
        string $motivo,
        ?string $tipoImpacto
    ): bool {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE adms_users
                 SET status = :status,
                     bloqueado = 1,
                     data_desligamento = :data_desligamento,
                     motivo_desligamento = :motivo,
                     tipo_impacto_desligamento = :tipo_impacto,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':status', 'Inativo', PDO::PARAM_STR);
            $stmt->bindValue(':data_desligamento', $dataDesligamento, PDO::PARAM_STR);
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $stmt->bindValue(
                ':tipo_impacto',
                $tipoImpacto !== null && $tipoImpacto !== '' ? $tipoImpacto : null,
                $tipoImpacto !== null && $tipoImpacto !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return false;
            }

            $historyRepo = new EmploymentHistoryRepository();
            $periodoAtual = $historyRepo->getCurrentPeriod($userId);
            if ($periodoAtual) {
                $historyRepo->updateTermination(
                    $userId,
                    $dataDesligamento,
                    $motivo,
                    $tipoImpacto
                );
            } else {
                $usersRepo = new UsersRepository();
                $user = $usersRepo->getUser($userId);
                $historyRepo->create([
                    'adms_user_id' => $userId,
                    'data_admissao' => is_array($user) ? ($user['data_admissao'] ?? date('Y-m-d')) : date('Y-m-d'),
                    'data_desligamento' => $dataDesligamento,
                    'motivo_desligamento' => $motivo,
                    'tipo_impacto_desligamento' => $tipoImpacto,
                    'tipo_periodo' => 'Admissão',
                    'observacoes' => 'Desligamento via offboarding RH',
                ]);
            }

            $cacheService = new \App\adms\Models\Services\QueryCacheService();
            $cacheService->forget('users_select_all');

            return true;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao aplicar desligamento no usuário.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function obrigatoriosPendentes(int $planoId): int
    {
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT COUNT(*) FROM rh_offboarding_itens
                 WHERE rh_offboarding_plano_id = :id
                   AND obrigatorio = 1
                   AND status NOT IN ('concluido', 'dispensado')"
            );
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return PHP_INT_MAX;
        }
    }
}
