<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use Generator;
use PDO;

/**
 * Repositório responsável pelas operações relacionadas aos grupos de dados LGPD.
 *
 * Esta classe gerencia a recuperação, inserção, atualização e exclusão de grupos de dados
 * no banco de dados, seguindo as boas práticas da LGPD e padrões de segurança.
 *
 * @package App\adms\Models\Repository
 */
class LgpdDataGroupsRepository extends DbConnection
{
    /**
     * Recupera todos os grupos de dados com paginação.
     *
     * @param int $page Página atual
     * @param int $limitResult Limite de resultados por página
     * @param string $searchTerm Termo de busca (opcional)
     * @return array|bool Retorna um array com os grupos de dados ou false se não houver resultados
     */
    public function getAll(int $page = 1, int $limitResult = 10, string $searchTerm = ''): array|bool
    {
        $offset = ($page - 1) * $limitResult;

        $sql = "SELECT id, name, category, example_fields, is_sensitive, notes, created_at, updated_at
                FROM lgpd_data_groups
                WHERE 1=1";

        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (name LIKE :search OR category LIKE :search OR example_fields LIKE :search)";
            $params[':search'] = "%{$searchTerm}%";
        }

        $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: false;
    }

    /**
     * Recupera um grupo de dados específico por ID.
     *
     * @param int $id ID do grupo de dados
     * @return array|bool Retorna um array com os dados do grupo ou false se não encontrado
     */
    public function getById(int $id): array|bool
    {
        $sql = "SELECT id, name, category, example_fields, is_sensitive, notes, created_at, updated_at
                FROM lgpd_data_groups
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: false;
    }

    /**
     * Recupera todos os grupos de dados para uso em formulários (select).
     *
     * @param bool $onlySensitive Se true, retorna apenas grupos sensíveis
     * @return array|bool Retorna um array com os grupos de dados ou false se não houver resultados
     */
    public function getAllForSelect(bool $onlySensitive = false): array|bool
    {
        $sql = "SELECT id, name, category, is_sensitive, example_fields
                FROM lgpd_data_groups
                WHERE 1=1";

        if ($onlySensitive) {
            $sql .= " AND is_sensitive = 1";
        }

        $sql .= " ORDER BY name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: false;
    }

    /**
     * Recupera grupos de dados por categoria.
     *
     * @param string $category Categoria (Pessoal ou Sensível)
     * @return array|bool Retorna um array com os grupos de dados ou false se não houver resultados
     */
    public function getByCategory(string $category): array|bool
    {
        $sql = "SELECT id, name, category, example_fields, is_sensitive, notes
                FROM lgpd_data_groups
                WHERE category = :category
                ORDER BY name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: false;
    }

    /**
     * Recupera grupos de dados sensíveis.
     *
     * @return array|bool Retorna um array com os grupos sensíveis ou false se não houver resultados
     */
    public function getSensitiveGroups(): array|bool
    {
        return $this->getByCategory('Sensível');
    }

    /**
     * Recupera grupos de dados pessoais.
     *
     * @return array|bool Retorna um array com os grupos pessoais ou false se não houver resultados
     */
    public function getPersonalGroups(): array|bool
    {
        return $this->getByCategory('Pessoal');
    }

    /**
     * Cria um novo grupo de dados.
     *
     * @param array $data Dados do grupo de dados
     * @return bool Retorna true se a operação foi bem-sucedida, ou false em caso de erro
     */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO lgpd_data_groups (name, category, example_fields, is_sensitive, notes, created_at, updated_at)
                    VALUES (:name, :category, :example_fields, :is_sensitive, :notes, NOW(), NOW())";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':category', $data['category']);
            $stmt->bindValue(':example_fields', $data['example_fields']);
            $stmt->bindValue(':is_sensitive', $data['is_sensitive'], PDO::PARAM_BOOL);
            $stmt->bindValue(':notes', $data['notes']);

            $result = $stmt->execute();

            if ($result) {
                GenerateLog::generateLog("info", "Grupo de dados LGPD criado com sucesso.", ['name' => $data['name']]);
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar grupo de dados LGPD.", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualiza um grupo de dados existente.
     *
     * @param int $id ID do grupo de dados
     * @param array $data Dados atualizados do grupo de dados
     * @return bool Retorna true se a operação foi bem-sucedida, ou false em caso de erro
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE lgpd_data_groups 
                    SET name = :name, category = :category, example_fields = :example_fields, 
                        is_sensitive = :is_sensitive, notes = :notes, updated_at = NOW()
                    WHERE id = :id";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':category', $data['category']);
            $stmt->bindValue(':example_fields', $data['example_fields']);
            $stmt->bindValue(':is_sensitive', $data['is_sensitive'], PDO::PARAM_BOOL);
            $stmt->bindValue(':notes', $data['notes']);

            $result = $stmt->execute();

            if ($result) {
                GenerateLog::generateLog("info", "Grupo de dados LGPD atualizado com sucesso.", ['id' => $id, 'name' => $data['name']]);
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar grupo de dados LGPD.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Exclui um grupo de dados.
     *
     * @param int $id ID do grupo de dados
     * @return bool Retorna true se a operação foi bem-sucedida, ou false em caso de erro
     */
    public function delete(int $id): bool
    {
        try {
            // Verificar se o grupo está sendo usado no inventário
            $sqlCheck = "SELECT COUNT(*) as count FROM lgpd_inventory_data_groups WHERE lgpd_data_group_id = :id";
            $stmtCheck = $this->getConnection()->prepare($sqlCheck);
            $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $usage = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($usage['count'] > 0) {
                GenerateLog::generateLog("warning", "Tentativa de excluir grupo de dados LGPD em uso.", ['id' => $id, 'usage_count' => $usage['count']]);
                return false;
            }

            $sql = "DELETE FROM lgpd_data_groups WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $result = $stmt->execute();

            if ($result) {
                GenerateLog::generateLog("info", "Grupo de dados LGPD excluído com sucesso.", ['id' => $id]);
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao excluir grupo de dados LGPD.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Conta o total de grupos de dados.
     *
     * @param string $searchTerm Termo de busca (opcional)
     * @return int Retorna o total de grupos de dados
     */
    public function getAmount(string $searchTerm = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM lgpd_data_groups WHERE 1=1";

        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (name LIKE :search OR category LIKE :search OR example_fields LIKE :search)";
            $params[':search'] = "%{$searchTerm}%";
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Recupera estatísticas dos grupos de dados.
     *
     * @return array Retorna um array com estatísticas
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_groups,
                    SUM(CASE WHEN is_sensitive = 1 THEN 1 ELSE 0 END) as sensitive_groups,
                    SUM(CASE WHEN is_sensitive = 0 THEN 1 ELSE 0 END) as personal_groups,
                    SUM(CASE WHEN category = 'Pessoal' THEN 1 ELSE 0 END) as personal_category,
                    SUM(CASE WHEN category = 'Sensível' THEN 1 ELSE 0 END) as sensitive_category
                FROM lgpd_data_groups";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera todos os grupos de dados para uso em formulários (select).
     *
     * @return array Lista de grupos de dados para select
     */
    public function getAllDataGroupsForSelect(): array
    {
        $sql = 'SELECT id, name, category, is_sensitive FROM lgpd_data_groups ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}