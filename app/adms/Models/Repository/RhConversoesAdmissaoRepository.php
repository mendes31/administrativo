<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhConversoesAdmissaoRepository extends DbConnection
{
    public const MODO_CRIAR = 'criar';
    public const MODO_VINCULAR = 'vincular';
    public const STATUS_CONCLUIDA = 'concluida';

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_conversoes_admissao WHERE id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar conversão por id.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByOfertaId(int $ofertaId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_conversoes_admissao WHERE rh_oferta_id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $ofertaId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar conversão por oferta.', [
                'oferta_id' => $ofertaId,
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
            $sql = 'INSERT INTO rh_conversoes_admissao
                        (rh_oferta_id, rh_candidatura_id, rh_candidato_id, rh_vaga_id,
                         adms_user_id, modo, status, observacoes, converted_by_user_id, created_at, updated_at)
                    VALUES
                        (:oferta_id, :candidatura_id, :candidato_id, :vaga_id,
                         :user_id, :modo, :status, :observacoes, :converted_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':oferta_id', (int) $data['rh_oferta_id'], PDO::PARAM_INT);
            $stmt->bindValue(':candidatura_id', (int) $data['rh_candidatura_id'], PDO::PARAM_INT);
            $stmt->bindValue(':candidato_id', (int) $data['rh_candidato_id'], PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', (int) $data['rh_vaga_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', (int) $data['adms_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':modo', (string) $data['modo'], PDO::PARAM_STR);
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_CONCLUIDA), PDO::PARAM_STR);
            $obs = $data['observacoes'] ?? null;
            $stmt->bindValue(
                ':observacoes',
                $obs !== null && $obs !== '' ? $obs : null,
                $obs !== null && $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $by = $data['converted_by_user_id'] ?? null;
            $stmt->bindValue(
                ':converted_by',
                $by !== null ? (int) $by : null,
                $by !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );

            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao criar conversão de admissão.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
