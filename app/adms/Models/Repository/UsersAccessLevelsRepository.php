<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class UsersAccessLevelsRepository extends DbConnection
{
    public function getUserAccessLevel(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT lev.name
        FROM adms_users_access_levels AS usr_lev
        INNER JOIN adms_access_levels AS lev ON lev.id = usr_lev.adms_access_level_id
        WHERE usr_lev.adms_user_id = :adms_user_id
        ORDER BY usr_lev.id DESC';

        // Preparar a quey
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir o link pelo valor
        $stmt->bindValue(':adms_user_id', $id, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserAccessLevelArray(int $id): array|bool
    {
        // Query para recuperar o registro do banco de dados
        $sql = 'SELECT adms_access_level_id
                FROM adms_users_access_levels
                WHERE adms_user_id = :adms_user_id';

        // Preparar a query 
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_user_id', $id, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar os valores de 'adms_access_level_id' como array simples
        return $result ? array_column($result, 'adms_access_level_id') : false;
    }

    // Obter todos os níveis de acesso
    public function getAllAccessLevels(): array|bool
    {
        // Query para recuperar o registro do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_access_levels
                ORDER BY name ASC';

        // Preparar a query 
        $stmt = $this->getConnection()->prepare($sql);
        // $stmt->bindValue(':adms_user_id', $id, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar os valores de 'adms_access_level_id' como array simples
        return $result;
    }

    /**
     * Recupera todos os usuários vinculados a um nível de acesso específico.
     *
     * @param int $accessLevelId ID do nível de acesso
     * @return array Array com dados dos usuários ou array vazio se não houver
     */
    public function getUsersByAccessLevel(int $accessLevelId): array
    {
        $sql = 'SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.username,
                    u.status,
                    u.bloqueado,
                    d.name as department_name,
                    p.name as position_name
                FROM adms_users u
                INNER JOIN adms_users_access_levels ual ON u.id = ual.adms_user_id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                LEFT JOIN adms_positions p ON u.user_position_id = p.id
                WHERE ual.adms_access_level_id = :access_level_id
                AND u.status = 1
                ORDER BY u.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':access_level_id', $accessLevelId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    public function updateUserAccessLevel(array $data): bool
    {
        // Criar o elemento userAccesLevels no array quando não vem nível de acesso do formulário
        $userAccessLevelsArray = $data['userAccessLevelsArray'] ?? [];


        // var_dump($data);
        // var_dump($data['userAccessLevelsArray']);
        // var_dump($userAccessLevelsArray);

        try {
            // Recuperar os níveis de acesso do usuário em formato de array
            $userAccessLevelsArrayDB = $this->getUserAccessLevelArray($data['adms_user_id']);
            $userAccessLevelsArrayDB = $userAccessLevelsArrayDB ? $userAccessLevelsArrayDB : [];

            // var_dump($userAccessLevelsArrayDB);
            // exit;      

            // Perceorrer o array com os valores de acesso e liberar acesso
            foreach ($data['userAccessLevelsArray'] as $userAccessLevel) {

                // var_dump($userAccessLevelsArrayDB);
                // exit;

                // var_dump($userAccessLevel);

                // Se o usuário já tem o nível de acesso liberado, remove do array
                if (in_array($userAccessLevel, $userAccessLevelsArrayDB)) {
                    $userAccessLevelsArrayDB = array_diff($userAccessLevelsArrayDB, [$userAccessLevel]);
                    // var_dump($userAccessLevelsArrayDB);
                } else {
                    // Cadastrar o nível de acesso

                    // QUERY para cadastrar o nível de acsso para o usuário
                    $sql = 'INSERT INTO adms_users_access_levels (adms_user_id, adms_access_level_id, created_at) VALUES (:adms_user_id, :adms_access_level_id, :created_at)';

                    // Preparar a query
                    $stmt = $this->getConnection()->prepare($sql);

                    // Substiruir os parâmentros da query pelos valores
                    $stmt->bindValue(':adms_user_id', $data['adms_user_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':adms_access_level_id', $userAccessLevel);
                    $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

                    // Executar a query 
                    $stmt->execute();

                    GenerateLog::generateLog("info", "Novo nível de acesso cadastrado para o usuário.", ['id' => $data['adms_user_id'], 'adms_access_level_id' => $userAccessLevel]);
                }

                // var_dump($userAccessLevelsArrayDB);
            }
            // var_dump($userAccessLevelsArrayDB);

            // Percorrer o array com os níveis de acesso e bloquear o acesso
            foreach ($userAccessLevelsArrayDB as $userAccessLevel) {

                // QUERY para apagar o nível de acesso do usuário
                $sql = 'DELETE FROM adms_users_access_levels 
                        WHERE adms_user_id = :adms_user_id 
                        AND adms_access_level_id = :adms_access_level_id 
                        LIMIT 1';

                // Preparar a QUERY
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':adms_user_id', $data['adms_user_id'], PDO::PARAM_INT);
                $stmt->bindValue(':adms_access_level_id', $userAccessLevel, PDO::PARAM_INT);

                // Executar a QUERY
                $stmt->execute();

                GenerateLog::generateLog("info", "Removido nível de acesso para o usuário.", ['id' => $data['adms_user_id'], 'adms_access_level_id' => $userAccessLevel]);
            }
            return true;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Nível de acesso do usuário não editado.", ['id' => $data['adms_user_id'], 'error' => $e->getMessage()]);

            return false;
        }
    }
}
