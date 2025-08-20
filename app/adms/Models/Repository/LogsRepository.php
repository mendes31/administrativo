<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular usuários no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar usuários no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @return Rafael Mendes
 */
class LogsRepository extends DbConnection
{
    /**
     * Recuperar todos os usuários com paginação.
     *
     * Este método retorna uma lista de usuários da tabela `adms_users`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de usuários (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de usuários recuperados do banco de dados.
     */
    public function insertLogs(array $dataLogs): void
    {
        $sql = ("INSERT INTO adms_logs (date, time, table_name, action, user_id, record_id, description) VALUES (:date, :time, :table_name, :action, :user_id, :record_id, :description)");

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindValue(':date', date("Y-m-d"));
        $stmt->bindValue(':time', date("H:i:s"));
        $stmt->bindParam(':table_name', $dataLogs['table_name']);
        $stmt->bindParam(':action', $dataLogs['action']);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':record_id', $dataLogs['record_id'], PDO::PARAM_INT);
        $stmt->bindValue(':description', $dataLogs['description']);
        
        // echo $dataLogs['table_name'];
        // echo $dataLogs['action'];
        // echo 'user_id';
        // echo 'record_id';
        // echo 'description';

        // var_dump($stmt);

        $stmt->execute();
    }
}
