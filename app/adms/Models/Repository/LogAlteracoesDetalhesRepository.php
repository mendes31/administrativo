<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LogAlteracoesDetalhesRepository extends DbConnection
{
    public function getByLogAlteracaoId(int $logAlteracaoId): array
    {
        $sql = 'SELECT * FROM adms_log_alteracoes_detalhes WHERE log_alteracao_id = :log_alteracao_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':log_alteracao_id', $logAlteracaoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO adms_log_alteracoes_detalhes (log_alteracao_id, campo, valor_anterior, valor_novo) VALUES (:log_alteracao_id, :campo, :valor_anterior, :valor_novo)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':log_alteracao_id', $data['log_alteracao_id']);
            $stmt->bindValue(':campo', $data['campo']);
            $stmt->bindValue(':valor_anterior', $data['valor_anterior']);
            $stmt->bindValue(':valor_novo', $data['valor_novo']);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
} 