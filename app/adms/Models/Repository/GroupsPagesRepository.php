<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular grupos no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar grupos no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class GroupsPagesRepository extends DbConnection
{

    /**
     * Recuperar todos os grupos com paginação e filtro por nome.
     *
     * @param int $page Número da página para recuperação de grupos (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @return array Lista de grupos recuperados do banco de dados.
     */
    public function getAllGroupsPages(int $page = 1, int $limitResult = 10, string $filterName = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $sql = 'SELECT id, name 
                FROM adms_groups_pages';
        $params = [];
        if (!empty($filterName)) {
            $sql .= ' WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql .= ' ORDER BY name ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        if (!empty($filterName)) {
            $stmt->bindValue(':name', $params[':name'], PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de grupos para paginação e filtro por nome.
     *
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @return int Quantidade total de grupos encontrados no banco de dados.
     */
    public function getAmountGroupsPages(string $filterName = ''): int
    {
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_groups_pages';
        $params = [];
        if (!empty($filterName)) {
            $sql .= ' WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $stmt = $this->getConnection()->prepare($sql);
        if (!empty($filterName)) {
            $stmt->bindValue(':name', $params[':name'], PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um grupo específico pelo ID.
     *
     * Este método retorna os detalhes de um grupo específico identificado pelo ID.
     *
     * @param int $id ID do grupo a ser recuperado.
     * @return array|bool Detalhes do grupo recuperado ou `false` se não encontrado.
     */
    public function getGroupPage(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, obs, created_at, updated_at
                FROM adms_groups_pages
                WHERE id = :id
                ORDER BY id DESC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo grupo.
     *
     * Este método insere um novo grupo na tabela `adms_groups_pages`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do grupo a ser cadastrado, incluindo `name` e `obs`.
     * @return bool|int `true` se o grupo foi criado com sucesso ou `false` em caso de erro. Retorna o ID do grupo criado em caso de sucesso.
     */
    public function createGroupPage(array $data): bool|int
    {
        try {            

            // QUERY para cadastrar grupo
            $sql = 'INSERT INTO adms_groups_pages (name, obs, created_at) VALUES (:name, :obs, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do grupo recém-cadastrado
            $groupPageId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($groupPageId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'obs' => $data['obs'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_groups_pages',
                    $groupPageId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $groupPageId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Grupo não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um grupo existente.
     *
     * Este método atualiza as informações de um grupo existente. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do grupo, incluindo `id`, `name`, e `obs`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateGroupPage(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getGroupPage($data['id']);
            
            // QUERY para atualizar grupo
            $sql = 'UPDATE adms_groups_pages SET name = :name, obs = :obs, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores 
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'name' => $data['name'],
                    'obs' => $data['obs'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_groups_pages',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }

            return $result;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Grupo não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um grupo pelo ID.
     *
     * Este método remove um grupo específico da tabela `adms_groups_pages`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do grupo a ser deletado.
     * @return bool `true` se o grupo foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteGroupPage(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getGroupPage($id);
            
            // QUERY para deletar grupo
            $sql = 'DELETE FROM adms_groups_pages WHERE id = :id LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            // Executar a QUERY
            $stmt->execute();

            // Verificar o número de linhas afetadas
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                // Registrar log de alteração se a exclusão foi bem-sucedida
                if ($oldData) {
                    $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                    LogAlteracaoService::registrarAlteracao(
                        'adms_groups_pages',
                        $id,
                        $usuarioId,
                        'DELETE',
                        $oldData,
                        []
                    );
                }
                
                return true;
            } else {
                // Gerar log de erro
                GenerateLog::generateLog("error", "Grupo não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Grupo não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllGroupsPagesSelect(): array
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_groups_pages                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
