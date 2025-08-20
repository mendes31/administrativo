<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class UsersDepartmentsRepository extends DbConnection
{
    public function getUserDepartments(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT 
        dep.name
        FROM adms_users_departments AS usr_dep
        INNER JOIN adms_departments AS dep ON dep.id = usr_dep.adms_department_id
        WHERE usr_dep.adms_user_id = :adms_user_id
        ORDER BY usr_dep.id DESC';

        // Preparar a quey
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir o link pelo valor
        $stmt->bindValue(':adms_user_id', $id, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
