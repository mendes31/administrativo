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

    /**
     * Retorna todos os detalhes de alterações para uma lista de logs,
     * já incluindo informações da tabela de log principal (data, tipo, usuário).
     *
     * @param int[] $logIds
     * @return array
     */
    public function getByLogIds(array $logIds): array
    {
        if (empty($logIds)) {
            return [];
        }

        // Garantir IDs inteiros e únicos
        $logIds = array_values(array_unique(array_map('intval', $logIds)));
        $placeholders = implode(',', array_fill(0, count($logIds), '?'));

        $sql = "SELECT 
                    d.*,
                    log.data_alteracao,
                    log.tipo_operacao,
                    log.tabela,
                    log.objeto_id,
                    usr.name AS usuario_nome
                FROM adms_log_alteracoes_detalhes d
                INNER JOIN adms_log_alteracoes log 
                    ON log.id = d.log_alteracao_id
                LEFT JOIN adms_users usr 
                    ON usr.id = log.usuario_id
                WHERE d.log_alteracao_id IN ($placeholders)
                ORDER BY log.data_alteracao ASC, d.id ASC";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($logIds as $index => $logId) {
            $stmt->bindValue($index + 1, $logId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 