<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular departamentos no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar departamentos no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class DepartmentsRepository extends DbConnection
{

    /**
     * Recuperar todos os departamentos com paginação.
     *
     * Este método retorna uma lista de departamentos da tabela `adms_departments`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de departamentos (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param string $filterName Filtro opcional por nome.
     * @return array Lista de depasrtamentos recuperados do banco de dados.
     */
    public function getAllDepartments(int $page = 1, int $limitResult = 10, string $filterName = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $where = '';
        $params = [];
        if (!empty($filterName)) {
            $where = 'WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql = 'SELECT id, name FROM adms_departments '
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
     * Recuperar a quantidade total de departamentos para paginação.
     *
     * Este método retorna a quantidade total de departamentos na tabela `adms_departments`, útil para a paginação.
     *
     * @param string $filterName Filtro opcional por nome.
     * @return int Quantidade total de departamentos encontrados no banco de dados.
     */
    public function getAmountDepartments(string $filterName = ''): int
    {
        $where = '';
        $params = [];
        if (!empty($filterName)) {
            $where = 'WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_departments ' . $where;
        $stmt = $this->getConnection()->prepare($sql);
        if (!empty($filterName)) {
            $stmt->bindValue(':name', $params[':name'], PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um departmento específico pelo ID.
     *
     * Este método retorna os detalhes de um departmento específico identificado pelo ID.
     *
     * @param int $id ID do departmento a ser recuperado.
     * @return array|bool Detalhes do departmento recuperado ou `false` se não encontrado.
     */
    public function getDepartment(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, create_at, update_at
                FROM adms_departments
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
     * Cadastrar um novo departamento
     *
     * Este método insere um novo departamento na tabela `adms_departments`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do departamento a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o departamento foi criado com sucesso ou `false` em caso de erro.
     */
    public function createDepartment(array $data): bool|int
    {
        try {

            // QUERY para cadastrar departamento
            $sql = 'INSERT INTO adms_departments (name, create_at) VALUES (:name, :create_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':create_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do departamento recém cadastrado
            $departmentId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($departmentId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_departments',
                    $departmentId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $departmentId;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Departamento não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um departamento existente.
     *
     * Este método atualiza as informações de um departamento existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do departamento, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateDepartment(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getDepartment($data['id']);
            
            // QUERY para atualizar departamento
            $sql = 'UPDATE adms_departments SET name = :name, update_at = :update_at';

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
                    'adms_departments',
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
            GenerateLog::generateLog("error", "Departamento não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um departamento pelo ID.
     *
     * Este método remove um nível de acesso específico da tabela `adms_departments`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do Departamento a ser deletado.
     * @return bool `true` se o Departamento foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteDepartment(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getDepartment($id);
            
            // QUERY para deletar departamento
            $sql = 'DELETE FROM adms_departments WHERE id = :id LIMIT 1';

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
                        'adms_departments',
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
                GenerateLog::generateLog("error", "Departamento não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Departamento não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllDepartmentsSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_departments                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter departamento pelo nome (case-insensitive, trim)
     */
    public function getByName(string $name): array|bool
    {
        $sql = 'SELECT id, name FROM adms_departments WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: false;
    }
}
