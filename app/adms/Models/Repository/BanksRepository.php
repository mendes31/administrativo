<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular Bancos no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar Bancos no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class BanksRepository extends DbConnection
{

    /**
     * Recuperar todos os Bancos com paginação.
     *
     * Este método retorna uma lista de Bancos da tabela `adms_bank_accounts`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de Bancos (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de Bancos recuperados do banco de dados.
     */
    public function getAllBanks(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, bank_name, bank, type, account, agency, balance
                FROM adms_bank_accounts               
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
     * Recuperar a quantidade total de Bancos para paginação.
     *
     * Este método retorna a quantidade total de Bancos na tabela `adms_bank_accounts`, útil para a paginação.
     *
     * @return int Quantidade total de Bancos encontrados no banco de dados.
     */
    public function getAmountBanks(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_bank_accounts';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um Banco específico pelo ID.
     *
     * Este método retorna os detalhes de um Banco específico identificado pelo ID.
     *
     * @param int $id ID do Banco a ser recuperado.
     * @return array|bool Detalhes do Banco recuperado ou `false` se não encontrado.
     */
    public function getBank(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, bank_name, bank, type, account, agency, balance, created_at, updated_at
                FROM adms_bank_accounts
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
     * Cadastrar um novo Banco
     *
     * Este método insere um novo Banco na tabela `adms_bank_accounts`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do Banco a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o Banco foi criado com sucesso ou `false` em caso de erro.
     */
    public function createBank(array $data): bool|int
    {
        try {

            // QUERY para cadastrar Banco
            $sql = 'INSERT INTO adms_bank_accounts (bank_name, bank, type, account, agency, balance, created_at) VALUES (:bank_name, :bank, :type, :account, :agency, :balance, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':bank_name', $data['bank_name'], PDO::PARAM_STR);
            $stmt->bindValue(':bank', $data['bank'], PDO::PARAM_STR);
            $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
            $stmt->bindValue(':account', $data['account'], PDO::PARAM_STR);
            $stmt->bindValue(':agency', $data['agency'], PDO::PARAM_STR);
            $stmt->bindValue(':balance', $data['balance'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do Banco recém cadastrado
            $bankId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($bankId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_bank_accounts',
                    $bankId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $bankId;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Banco não cadastrado.", ['name' => $data['bank_name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um Banco existente.
     *
     * Este método atualiza as informações de um Banco existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Banco, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateBank(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getBank($data['id']);
            
            // QUERY para atualizar Banco
            $sql = 'UPDATE adms_bank_accounts SET bank_name = :bank_name, bank = :bank, type = :type, account = :account, agency = :agency, balance = :balance, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':bank_name', $data['bank_name'], PDO::PARAM_STR);
            $stmt->bindValue(':bank', $data['bank'], PDO::PARAM_STR);
            $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
            $stmt->bindValue(':account', $data['account'], PDO::PARAM_STR);
            $stmt->bindValue(':agency', $data['agency'], PDO::PARAM_STR);
            $stmt->bindValue(':balance', $data['balance'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_bank_accounts',
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
            GenerateLog::generateLog("error", "Banco não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um Banco pelo ID.
     *
     * Este método remove um Banco específico da tabela `adms_bank_accounts`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do Banco a ser deletado.
     * @return bool `true` se o Banco foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteBank(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getBank($id);
            
            // QUERY para deletar Banco
            $sql = 'DELETE FROM adms_bank_accounts WHERE id = :id LIMIT 1';

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
                        'adms_bank_accounts',
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
                GenerateLog::generateLog("error", "Banco não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Banco não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllBanksSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, bank_name, bank, type, account, agency, balance
                FROM adms_bank_accounts                
                ORDER BY bank_name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
