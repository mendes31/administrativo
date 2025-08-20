<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular níveis de acesso no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar níveis de acesso no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Cesar <cesar@celke.com.br>
 */
class AccessLevelsRepository extends DbConnection
{

    /**
     * Recuperar todos os níveis de acesso com paginação.
     *
     * Este método retorna uma lista de níveis de acesso da tabela `adms_access_levels`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de níveis de acesso (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param string $filterName Filtro opcional por nome.
     * @return array Lista de níveis de acesso recuperados do banco de dados.
     */
    public function getAllAccessLevels(int $page = 1, int $limitResult = 10, string $filterName = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $where = '';
        $params = [];
        if (!empty($filterName)) {
            $where = 'WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql = 'SELECT id, name FROM adms_access_levels '
            . $where . ' ORDER BY id ASC LIMIT :limit OFFSET :offset';
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
     * Recuperar a quantidade total de níveis de acesso para paginação.
     *
     * Este método retorna a quantidade total de níveis de acesso na tabela `adms_access_levels`, útil para a paginação.
     *
     * @param string $filterName Filtro opcional por nome.
     * @return int Quantidade total de níveis de acesso encontrados no banco de dados.
     */
    public function getAmountAccessLevels(string $filterName = ''): int
    {
        $where = '';
        $params = [];
        if (!empty($filterName)) {
            $where = 'WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_access_levels ' . $where;
        $stmt = $this->getConnection()->prepare($sql);
        if (!empty($filterName)) {
            $stmt->bindValue(':name', $params[':name'], PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um nível de acesso específico pelo ID.
     *
     * Este método retorna os detalhes de um nível de acesso específico identificado pelo ID.
     *
     * @param int $id ID do nível de acesso a ser recuperado.
     * @return array|bool Detalhes do nível de acesso recuperado ou `false` se não encontrado.
     */
    public function getAccessLevel(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, create_at, update_at
                FROM adms_access_levels
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
     * Cadastrar um novo nível de acesso
     *
     * Este método insere um novo nível de acessona tabela `adms_acess_levels`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do nível de acessoa ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o nível de acesso foi criado com sucesso ou `false` em caso de erro.
     */
    public function createAccessLevel(array $data): bool|int
    {
        try {

            // QUERY para cadastrar nível de acesso
            $sql = 'INSERT INTO adms_access_levels (name, create_at) VALUES (:name, :create_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':create_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do nivel recém cadastrado
            $accessLevelId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($accessLevelId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_access_levels',
                    $accessLevelId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $accessLevelId;

            
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Nível de acesso não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um nível de acesso existente.
     *
     * Este método atualiza as informações de um nível de acesso existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do nível de acesso, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateAccessLevel(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getAccessLevel($data['id']);
            
            // QUERY para atualizar nível de acesso
            $sql = 'UPDATE adms_access_levels SET name = :name, update_at = :update_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':update_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_access_levels',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Nível de acesso não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um nível de acesso pelo ID.
     *
     * Este método remove um nível de acesso específico da tabela `adms_access_levels`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do nível de acesso a ser deletado.
     * @return bool `true` se o nível de acesso foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteAccessLevel(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getAccessLevel($id);
            
            // QUERY para deletar nível de acesso
            $sql = 'DELETE FROM adms_access_levels WHERE id = :id LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            // Executar a QUERY
            $stmt->execute();

            // Verificar o número de linhas afetadas
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                // Registrar log de alteração
                if ($oldData) {
                    $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                    LogAlteracaoService::registrarAlteracao(
                        'adms_access_levels',
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
                GenerateLog::generateLog("error", "Nível de acesso não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Nível de acesso não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllAccessLevelsSelect(): array
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_access_levels                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter nível de acesso pelo nome (case-insensitive, trim)
     */
    public function getByName(string $name): array|bool
    {
        $sql = 'SELECT id, name FROM adms_access_levels WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: false;
    }
}
