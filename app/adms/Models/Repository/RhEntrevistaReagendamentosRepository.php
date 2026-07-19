<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Histórico append-only de reagendamentos de entrevista (Expand Fase 2).
 */
class RhEntrevistaReagendamentosRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listByEntrevista(int $entrevistaId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT r.*, u.name AS reagendado_por_nome
             FROM rh_entrevista_reagendamentos r
             LEFT JOIN adms_users u ON u.id = r.reagendado_por
             WHERE r.rh_entrevista_id = :entrevista_id
             ORDER BY r.created_at DESC, r.id DESC'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array{
     *   data_hora_anterior: string,
     *   data_hora_nova: string,
     *   motivo: string,
     *   reagendado_por?: int|null
     * } $data
     */
    public function append(int $entrevistaId, array $data): int
    {
        if ($entrevistaId <= 0) {
            throw new Exception('Entrevista inválida para reagendamento.');
        }

        $motivo = trim((string) ($data['motivo'] ?? ''));
        if ($motivo === '') {
            throw new Exception('Informe o motivo do reagendamento.');
        }
        if (mb_strlen($motivo) > 500) {
            throw new Exception('Motivo do reagendamento deve ter no máximo 500 caracteres.');
        }

        $anterior = trim((string) ($data['data_hora_anterior'] ?? ''));
        $nova = trim((string) ($data['data_hora_nova'] ?? ''));
        if ($anterior === '' || $nova === '') {
            throw new Exception('Datas do reagendamento são obrigatórias.');
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO rh_entrevista_reagendamentos
                    (rh_entrevista_id, data_hora_anterior, data_hora_nova, motivo, reagendado_por, created_at)
                 VALUES
                    (:entrevista_id, :anterior, :nova, :motivo, :reagendado_por, NOW())'
            );
            $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $stmt->bindValue(':anterior', $anterior, PDO::PARAM_STR);
            $stmt->bindValue(':nova', $nova, PDO::PARAM_STR);
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $actorId = isset($data['reagendado_por']) ? (int) $data['reagendado_por'] : 0;
            $stmt->bindValue(
                ':reagendado_por',
                $actorId > 0 ? $actorId : null,
                $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->execute();

            return (int) $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao registrar reagendamento de entrevista.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
