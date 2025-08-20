<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular Centros de Custo no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar centros de custo no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CostCentersRepository extends DbConnection
{

    /**
     * Recuperar todos os centros de custo com paginação.
     *
     * Este método retorna uma lista de centros de custo da tabela `adms_cost_center`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de centros de custo (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de centros de custo recuperados do banco de dados.
     */
    public function getAllCostCenters(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name
                FROM adms_cost_center               
                ORDER BY id ASC
                LIMIT :limit OFFSET :offset';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir os parâmetros da QUERY pelos valores
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de centros de custo para paginação.
     *
     * Este método retorna a quantidade total de centros de custo na tabela `adms_cost_center`, útil para a paginação.
     *
     * @return int Quantidade total de centros de custo encontrados no banco de dados.
     */
    public function getAmountCostCenters(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_cost_center';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um centro de custo específico pelo ID.
     *
     * Este método retorna os detalhes de um centro de custo específico identificado pelo ID.
     *
     * @param int $id ID do centro de custo a ser recuperado.
     * @return array|bool Detalhes do centro de custo recuperado ou `false` se não encontrado.
     */
    public function getCostCenter(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, created_at, updated_at
                FROM adms_cost_center
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
     * Cadastrar um novo centro de custo
     *
     * Este método insere um novo centro de custo na tabela `adms_cost_center`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do centro de custo a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o centro de custo foi criado com sucesso ou `false` em caso de erro.
     */
    public function createCostCenter(array $data): bool|int
    {
        try {

            // QUERY para cadastrar centro de custo
            $sql = 'INSERT INTO adms_cost_center (name, created_at) VALUES (:name, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do departamento recém cadastrado
            $costCenterId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($costCenterId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_cost_center',
                    $costCenterId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $costCenterId;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Centro de custo não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um centro de custo existente.
     *
     * Este método atualiza as informações de um centro de custo existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do centro de custo, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateCostCenter(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getCostCenter($data['id']);
            
            // QUERY para atualizar centro de custo
            $sql = 'UPDATE adms_cost_center SET name = :name, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'name' => $data['name'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_cost_center',
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
            GenerateLog::generateLog("error", "Centro de custo não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um centro de custo pelo ID.
     *
     * Este método remove um centro de custo específico da tabela `adms_cost_center`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do centro de custo a ser deletado.
     * @return bool `true` se o centro de custo foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteCostCenter(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getCostCenter($id);
            
            // QUERY para deletar centro de custo
            $sql = 'DELETE FROM adms_cost_center WHERE id = :id LIMIT 1';

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
                        'adms_cost_center',
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
                GenerateLog::generateLog("error", "Centro de Custo não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Centro de Custo não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllCostCenterSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_cost_center                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter centro de custo pelo nome (case-insensitive, trim)
     */
    public function getByName(string $name): array|bool
    {
        $sql = 'SELECT id, name FROM adms_cost_center WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: false;
    }
}
