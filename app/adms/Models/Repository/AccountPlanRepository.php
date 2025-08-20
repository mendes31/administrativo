<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular planos de conta no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar planos de conta no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class AccountPlanRepository extends DbConnection
{

    /**
     * Recuperar todos os planos de conta com paginação.
     *
     * Este método retorna uma lista de planos de conta da tabela `adms_accounts_plan`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de planos de conta (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de planos de conta recuperados do banco de dados.
     */
    public function getAllAccountsPlan(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name, account
                FROM adms_accounts_plan               
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
     * Recuperar a quantidade total de planos de conta para paginação.
     *
     * Este método retorna a quantidade total de planos de conta na tabela `adms_accounts_plan`, útil para a paginação.
     *
     * @return int Quantidade total de planos de conta encontrados no banco de dados.
     */
    public function getAmountAccountsPlan(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_accounts_plan';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um Plano de Contas específico pelo ID.
     *
     * Este método retorna os detalhes de um Plano de Contas específico identificado pelo ID.
     *
     * @param int $id ID do Plano de Contas a ser recuperado.
     * @return array|bool Detalhes do Plano de Contas recuperado ou `false` se não encontrado.
     */
    public function getAccountPlan(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, account, created_at, updated_at
                FROM adms_accounts_plan
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
     * Cadastrar um novo Plano de Contas
     *
     * Este método insere um novo Plano de Contas na tabela `adms_accounts_plan`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do Plano de Contas a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o Plano de Contas foi criado com sucesso ou `false` em caso de erro.
     */
    public function createAccountPlan(array $data): bool|int
    {
        try {

            // QUERY para cadastrar Plano de Contas
            $sql = 'INSERT INTO adms_accounts_plan (name, account, created_at) VALUES (:name, :account, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':account', $data['account'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do Plano de contas recém cadastrado
            $accountPlanId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($accountPlanId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'account' => $data['account'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_accounts_plan',
                    $accountPlanId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $accountPlanId;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Plano de contas não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um Plano de Contas existente.
     *
     * Este método atualiza as informações de um Plano de Contas existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Plano de Contas, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateAccountPlan(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getAccountPlan($data['id']);
            
            // QUERY para atualizar Plano de Contas
            $sql = 'UPDATE adms_accounts_plan SET name = :name, account = :account, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':account', $data['account'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'name' => $data['name'],
                    'account' => $data['account'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_accounts_plan',
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
            GenerateLog::generateLog("error", "Plano de Contas não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um Plano de Contas pelo ID.
     *
     * Este método remove um Plano de Contas específico da tabela `adms_accounts_plan`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do Plano de Contas a ser deletado.
     * @return bool `true` se o Plano de Contas foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteAccountPlan(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getAccountPlan($id);
            
            // QUERY para deletar Plano de Contas
            $sql = 'DELETE FROM adms_accounts_plan WHERE id = :id LIMIT 1';

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
                        'adms_accounts_plan',
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
                GenerateLog::generateLog("error", "Plano de Contas não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Plano de Contas não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllAccountsPlanSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name, account
                FROM adms_accounts_plan                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
