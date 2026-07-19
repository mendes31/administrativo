<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhPeriodosExperienciaRepository extends DbConnection
{
    public const STATUS_EM_ANDAMENTO = 'em_andamento';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_REPROVADO = 'reprovado';
    public const STATUS_PRORROGADO = 'prorrogado';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = 'SELECT e.*,
                           u.name AS usuario_nome,
                           u.email AS usuario_email,
                           c.nome AS candidato_nome
                    FROM rh_periodos_experiencia e
                    LEFT JOIN adms_users u ON u.id = e.adms_user_id
                    LEFT JOIN rh_candidatos c ON c.id = e.rh_candidato_id
                    WHERE e.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar período de experiência.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByConversaoId(int $conversaoId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_periodos_experiencia WHERE rh_conversao_id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $conversaoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar experiência por conversão.', [
                'conversao_id' => $conversaoId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int|false
    {
        try {
            $sql = 'INSERT INTO rh_periodos_experiencia
                        (rh_conversao_id, adms_user_id, rh_candidato_id, rh_onboarding_plano_id,
                         status, data_inicio, data_fim_prevista, dias_prorrogacao,
                         created_by_user_id, created_at, updated_at)
                    VALUES
                        (:conversao_id, :user_id, :candidato_id, :onboarding_id,
                         :status, :data_inicio, :data_fim, 0,
                         :created_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':conversao_id', (int) $data['rh_conversao_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $cand = $data['rh_candidato_id'] ?? null;
            $stmt->bindValue(
                ':candidato_id',
                $cand !== null ? (int) $cand : null,
                $cand !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $onb = $data['rh_onboarding_plano_id'] ?? null;
            $stmt->bindValue(
                ':onboarding_id',
                $onb !== null ? (int) $onb : null,
                $onb !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_EM_ANDAMENTO), PDO::PARAM_STR);
            $stmt->bindValue(':data_inicio', (string) $data['data_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(':data_fim', (string) $data['data_fim_prevista'], PDO::PARAM_STR);
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
            GenerateLog::generateLog('error', 'Erro ao criar período de experiência.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function registrarAvaliacao(
        int $id,
        string $status,
        string $resultado,
        ?string $observacoes,
        ?int $avaliadorId,
        ?string $novaDataFim = null,
        int $diasProrrogacao = 0
    ): bool {
        try {
            $sets = [
                'status = :status',
                'resultado = :resultado',
                'observacoes = :observacoes',
                'avaliado_em = NOW()',
                'avaliado_por_user_id = :avaliador',
                'updated_at = NOW()',
            ];
            if ($novaDataFim !== null) {
                $sets[] = 'data_fim_prevista = :data_fim';
                $sets[] = 'dias_prorrogacao = :dias';
                $sets[] = 'prorrogado_em = NOW()';
            }

            $sql = 'UPDATE rh_periodos_experiencia SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':resultado', $resultado, PDO::PARAM_STR);
            $stmt->bindValue(
                ':observacoes',
                $observacoes !== null && $observacoes !== '' ? $observacoes : null,
                $observacoes !== null && $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':avaliador',
                $avaliadorId,
                $avaliadorId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            if ($novaDataFim !== null) {
                $stmt->bindValue(':data_fim', $novaDataFim, PDO::PARAM_STR);
                $stmt->bindValue(':dias', $diasProrrogacao, PDO::PARAM_INT);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao avaliar período de experiência.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function cancelar(int $id): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE rh_periodos_experiencia
                 SET status = :status, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':status', self::STATUS_CANCELADO, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao cancelar período de experiência.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
