<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhOnboardingRepository extends DbConnection
{
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
                           c.nome AS candidato_nome
                    FROM rh_onboarding_planos p
                    LEFT JOIN adms_users u ON u.id = p.adms_user_id
                    LEFT JOIN rh_candidatos c ON c.id = p.rh_candidato_id
                    WHERE p.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar plano de onboarding.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPlanoByConversaoId(int $conversaoId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_onboarding_planos WHERE rh_conversao_id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $conversaoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar onboarding por conversão.', [
                'conversao_id' => $conversaoId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPlano(array $data): int|false
    {
        try {
            $sql = 'INSERT INTO rh_onboarding_planos
                        (rh_conversao_id, adms_user_id, rh_candidato_id, status,
                         data_inicio, data_limite, observacoes, created_by_user_id, created_at, updated_at)
                    VALUES
                        (:conversao_id, :user_id, :candidato_id, :status,
                         :data_inicio, :data_limite, :observacoes, :created_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':conversao_id', (int) $data['rh_conversao_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $cand = $data['rh_candidato_id'] ?? null;
            $stmt->bindValue(
                ':candidato_id',
                $cand !== null ? (int) $cand : null,
                $cand !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_EM_ANDAMENTO), PDO::PARAM_STR);
            $inicio = $data['data_inicio'] ?? date('Y-m-d');
            $stmt->bindValue(':data_inicio', $inicio, PDO::PARAM_STR);
            $limite = $data['data_limite'] ?? null;
            $stmt->bindValue(
                ':data_limite',
                $limite !== null && $limite !== '' ? $limite : null,
                $limite !== null && $limite !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
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
            GenerateLog::generateLog('error', 'Erro ao criar plano de onboarding.', [
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
                'INSERT INTO rh_onboarding_itens
                    (rh_onboarding_plano_id, codigo, titulo, obrigatorio, status, created_at, updated_at)
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
            GenerateLog::generateLog('error', 'Erro ao semear itens de onboarding.', [
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
                'SELECT * FROM rh_onboarding_itens
                 WHERE rh_onboarding_plano_id = :id
                 ORDER BY obrigatorio DESC, id ASC'
            );
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar itens de onboarding.', [
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
            $sets = ['status = :status', 'observacoes = :observacoes', 'updated_at = NOW()'];
            if (in_array($status, [self::ITEM_CONCLUIDO, self::ITEM_DISPENSADO], true)) {
                $sets[] = 'completed_at = NOW()';
                $sets[] = 'completed_by_user_id = :completed_by';
            } else {
                $sets[] = 'completed_at = NULL';
                $sets[] = 'completed_by_user_id = NULL';
            }

            $sql = 'UPDATE rh_onboarding_itens SET ' . implode(', ', $sets)
                . ' WHERE id = :id AND rh_onboarding_plano_id = :plano_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(
                ':observacoes',
                $observacoes !== null && $observacoes !== '' ? $observacoes : null,
                $observacoes !== null && $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $stmt->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
            if (in_array($status, [self::ITEM_CONCLUIDO, self::ITEM_DISPENSADO], true)) {
                $stmt->bindValue(
                    ':completed_by',
                    $userId,
                    $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
                );
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar item de onboarding.', [
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
                'UPDATE rh_onboarding_planos SET status = :status, updated_at = NOW() WHERE id = :id'
            );
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $planoId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar status do plano de onboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
