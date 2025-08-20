<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LogJustificativasRepository extends DbConnection
{
    public function getByLogAlteracaoId(int $logAlteracaoId): array|bool
    {
        $sql = 'SELECT * FROM adms_log_justificativas WHERE log_alteracao_id = :log_alteracao_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':log_alteracao_id', $logAlteracaoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO adms_log_justificativas (log_alteracao_id, justificativa, assinatura, data_justificativa) VALUES (:log_alteracao_id, :justificativa, :assinatura, :data_justificativa)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':log_alteracao_id', $data['log_alteracao_id']);
            $stmt->bindValue(':justificativa', $data['justificativa']);
            $stmt->bindValue(':assinatura', $data['assinatura']);
            $stmt->bindValue(':data_justificativa', $data['data_justificativa']);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
} 