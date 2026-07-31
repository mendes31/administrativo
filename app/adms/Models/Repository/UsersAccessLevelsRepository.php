<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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

    public function getAccessLevelIdByName(string $name): ?int
    {
        if ($name === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT id FROM adms_access_levels WHERE name = :name LIMIT 1');
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * Concede nível secundário se o usuário ainda não o possui.
     */
    public function grantAccessLevelToUser(int $userId, int $accessLevelId): bool
    {
        if ($userId <= 0 || $accessLevelId <= 0) {
            return false;
        }
        if ($this->findUsersAccessLevelRow($userId, $accessLevelId) !== null) {
            return true;
        }

        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_users_access_levels (adms_user_id, adms_access_level_id, created_at)
             VALUES (:uid, :lid, :now)'
        );

        return $stmt->execute([
            ':uid' => $userId,
            ':lid' => $accessLevelId,
            ':now' => date('Y-m-d H:i:s'),
        ]);
    }

    public function revokeAccessLevelFromUser(int $userId, int $accessLevelId): bool
    {
        if ($userId <= 0 || $accessLevelId <= 0) {
            return false;
        }

        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_users_access_levels
             WHERE adms_user_id = :uid AND adms_access_level_id = :lid LIMIT 1'
        );

        return $stmt->execute([':uid' => $userId, ':lid' => $accessLevelId]);
    }

    /**
     * Linha completa em adms_users_access_levels (por PK).
     *
     * @return array<string, mixed>|null
     */
    public function getUsersAccessLevelRowById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_users_access_levels WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findUsersAccessLevelRow(int $userId, int $accessLevelId): ?array
    {
        if ($userId <= 0 || $accessLevelId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_users_access_levels
             WHERE adms_user_id = :u AND adms_access_level_id = :l LIMIT 1'
        );
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':l', $accessLevelId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
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

    public function updateUserAccessLevel(array $data): bool
    {
        // Criar o elemento userAccesLevels no array quando não vem nível de acesso do formulário
        $userAccessLevelsArray = $data['userAccessLevelsArray'] ?? [];


        // var_dump($data);
        // var_dump($data['userAccessLevelsArray']);
        // var_dump($userAccessLevelsArray);

        try {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            // Recuperar os níveis de acesso do usuário em formato de array
            $userAccessLevelsArrayDB = $this->getUserAccessLevelArray($data['adms_user_id']);
            $userAccessLevelsArrayDB = $userAccessLevelsArrayDB ? $userAccessLevelsArrayDB : [];

            // Perceorrer o array com os valores de acesso e liberar acesso
            foreach (($data['userAccessLevelsArray'] ?? []) as $userAccessLevel) {

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

                    $newId = (int) $this->getConnection()->lastInsertId();
                    if ($newId > 0) {
                        $newRow = $this->getUsersAccessLevelRowById($newId);
                        if (is_array($newRow)) {
                            LogAlteracaoService::registrarAlteracao(
                                'adms_users_access_levels',
                                $newId,
                                $usuarioId,
                                'INSERT',
                                [],
                                $newRow
                            );
                        }
                    }

                    GenerateLog::generateLog("info", "Novo nível de acesso cadastrado para o usuário.", ['id' => $data['adms_user_id'], 'adms_access_level_id' => $userAccessLevel]);
                }

                // var_dump($userAccessLevelsArrayDB);
            }
            // var_dump($userAccessLevelsArrayDB);

            // Percorrer o array com os níveis de acesso e bloquear o acesso
            foreach ($userAccessLevelsArrayDB as $userAccessLevel) {
                $oldRow = $this->findUsersAccessLevelRow((int) $data['adms_user_id'], (int) $userAccessLevel);

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

                if (is_array($oldRow)) {
                    $pk = (int) ($oldRow['id'] ?? 0);
                    if ($pk > 0) {
                        LogAlteracaoService::registrarAlteracao(
                            'adms_users_access_levels',
                            $pk,
                            $usuarioId,
                            'DELETE',
                            $oldRow,
                            []
                        );
                    }
                }

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
