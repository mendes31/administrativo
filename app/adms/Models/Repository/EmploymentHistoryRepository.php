<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar histórico de admissões e desligamentos
 */
class EmploymentHistoryRepository extends DbConnection
{
    /**
     * Criar registro de histórico
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_employment_history 
                (adms_user_id, data_admissao, data_desligamento, motivo_desligamento, tipo_impacto_desligamento,
                 tipo_periodo, observacoes, created_at, updated_at)
                VALUES 
                (:adms_user_id, :data_admissao, :data_desligamento, :motivo_desligamento, :tipo_impacto_desligamento,
                 :tipo_periodo, :observacoes, NOW(), NOW())";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_user_id', $data['adms_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':data_admissao', $data['data_admissao']);
        $stmt->bindValue(':data_desligamento', $data['data_desligamento'] ?? null);
        $stmt->bindValue(':motivo_desligamento', $data['motivo_desligamento'] ?? null);
        $ti = $data['tipo_impacto_desligamento'] ?? null;
        $stmt->bindValue(
            ':tipo_impacto_desligamento',
            $ti !== null && $ti !== '' ? $ti : null,
            $ti !== null && $ti !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':tipo_periodo', $data['tipo_periodo'] ?? 'Admissão');
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar histórico por ID do usuário
     */
    public function getByUserId(int $userId): array
    {
        $sql = "SELECT * FROM adms_employment_history 
                WHERE adms_user_id = :user_id 
                ORDER BY data_admissao DESC, created_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Histórico de vínculos para vários usuários (uma consulta).
     *
     * @param int[] $userIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function getGroupedByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($userIds === []) {
            return [];
        }

        $ph = [];
        $params = [];
        foreach ($userIds as $i => $id) {
            $key = ':uid_' . $i;
            $ph[] = $key;
            $params[$key] = $id;
        }

        $sql = 'SELECT * FROM adms_employment_history
                WHERE adms_user_id IN (' . implode(',', $ph) . ')
                ORDER BY adms_user_id ASC, data_admissao DESC, created_at DESC';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byUser = [];
        foreach ($rows as $row) {
            $uid = (int) ($row['adms_user_id'] ?? 0);
            if ($uid < 1) {
                continue;
            }
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [];
            }
            $byUser[$uid][] = $row;
        }

        return $byUser;
    }

    /**
     * Buscar período atual (sem data de desligamento)
     */
    public function getCurrentPeriod(int $userId): array|false
    {
        $sql = "SELECT * FROM adms_employment_history 
                WHERE adms_user_id = :user_id 
                AND data_desligamento IS NULL
                ORDER BY data_admissao DESC
                LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /**
     * Atualizar data de desligamento no período atual
     */
    public function updateTermination(int $userId, string $dataDesligamento, ?string $motivo = null, ?string $tipoImpactoDesligamento = null): bool
    {
        $sql = "UPDATE adms_employment_history 
                SET data_desligamento = :data_desligamento,
                    motivo_desligamento = :motivo_desligamento,
                    tipo_impacto_desligamento = :tipo_impacto_desligamento,
                    updated_at = NOW()
                WHERE adms_user_id = :user_id 
                AND data_desligamento IS NULL
                ORDER BY data_admissao DESC
                LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':data_desligamento', $dataDesligamento);
        $stmt->bindValue(':motivo_desligamento', $motivo);
        $stmt->bindValue(
            ':tipo_impacto_desligamento',
            $tipoImpactoDesligamento !== null && $tipoImpactoDesligamento !== '' ? $tipoImpactoDesligamento : null,
            $tipoImpactoDesligamento !== null && $tipoImpactoDesligamento !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Buscar registro histórico por ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT * FROM adms_employment_history 
                WHERE id = :id 
                LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /**
     * Atualizar registro histórico
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE adms_employment_history 
                SET data_admissao = :data_admissao,
                    data_desligamento = :data_desligamento,
                    motivo_desligamento = :motivo_desligamento,
                    tipo_impacto_desligamento = :tipo_impacto_desligamento,
                    tipo_periodo = :tipo_periodo,
                    observacoes = :observacoes,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':data_admissao', $data['data_admissao']);
        $stmt->bindValue(':data_desligamento', !empty($data['data_desligamento']) ? $data['data_desligamento'] : null);
        $stmt->bindValue(':motivo_desligamento', !empty($data['motivo_desligamento']) ? $data['motivo_desligamento'] : null);
        $ti = $data['tipo_impacto_desligamento'] ?? null;
        $stmt->bindValue(
            ':tipo_impacto_desligamento',
            $ti !== null && $ti !== '' ? $ti : null,
            $ti !== null && $ti !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':tipo_periodo', $data['tipo_periodo'] ?? 'Admissão');
        $stmt->bindValue(':observacoes', !empty($data['observacoes']) ? $data['observacoes'] : null);
        
        return $stmt->execute();
    }

    /**
     * Deletar registro histórico
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_employment_history WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Calcular tempo total de casa (soma de todos os períodos)
     */
    public function calculateTotalTenure(int $userId): array
    {
        $sql = "SELECT 
                    SUM(DATEDIFF(COALESCE(data_desligamento, CURDATE()), data_admissao)) as total_dias,
                    COUNT(*) as total_periodos
                FROM adms_employment_history 
                WHERE adms_user_id = :user_id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalDias = (int)($result['total_dias'] ?? 0);
        $anos = floor($totalDias / 365);
        $meses = floor(($totalDias % 365) / 30);
        $dias = $totalDias % 30;
        
        return [
            'total_dias' => $totalDias,
            'anos' => $anos,
            'meses' => $meses,
            'dias' => $dias,
            'total_periodos' => (int)($result['total_periodos'] ?? 0),
            'formatted' => $anos > 0 
                ? "{$anos} ano(s), {$meses} mês(es) e {$dias} dia(s)"
                : ($meses > 0 ? "{$meses} mês(es) e {$dias} dia(s)" : "{$dias} dia(s)")
        ];
    }
}

