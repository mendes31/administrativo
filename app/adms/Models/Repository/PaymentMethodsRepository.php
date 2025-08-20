<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular formas de pagamento no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar formas de pagamento no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class PaymentMethodsRepository extends DbConnection
{

    /**
     * Recuperar todos os formas de pagamento com paginação e filtro por nome.
     *
     * @param int $page Número da página para recuperação de formas de pagamento (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @return array Lista de formas de pagamento recuperados do banco de dados.
     */
    public function getAllPaymentMethods(int $page = 1, int $limitResult = 10, string $filterName = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);

        $sql = 'SELECT id, name
                FROM adms_payment_method';
        $params = [];
        if (!empty($filterName)) {
            $sql .= ' WHERE name LIKE :name';
            $params[':name'] = '%' . $filterName . '%';
        }
        $sql .= ' ORDER BY id ASC
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
     * Recuperar a quantidade total de formas de pagamento para paginação e filtro por nome.
     *
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @return int Quantidade total de formas de pagamento encontrados no banco de dados.
     */
    public function getAmountPaymentMethods(string $filterName = ''): int
    {
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_payment_method';
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
     * Recuperar um formas de pagamento específico pelo ID.
     *
     * Este método retorna os detalhes de um formas de pagamento específico identificado pelo ID.
     *
     * @param int $id ID do formas de pagamentoa ser recuperado.
     * @return array|bool Detalhes do formas de pagamento recuperado ou `false` se não encontrado.
     */
    public function getPaymentMethod(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, created_at, updated_at
                FROM adms_payment_method
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
     * Cadastrar um novo formas de pagamento    
     * 
     * Este método insere um novo formas de pagamentona tabela `adms_payment_method`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do formas de pagamentoa ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o formas de pagamentofoi criado com sucesso ou `false` em caso de erro.
     */
    public function createPaymentMethod(array $data): bool|int
    {
        try {

            // QUERY para cadastrar formas de pagamento            
            $sql = 'INSERT INTO adms_payment_method (name, created_at) VALUES (:name, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do formas de pagamentorecém cadastrado
            $paymentMethodId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($paymentMethodId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_payment_method',
                    $paymentMethodId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $paymentMethodId;

        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Forma de pagamento não cadastrada.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um formas de pagamento existente.
     *
     * Este método atualiza as informações de um formas de pagamento existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do formas de pagamento incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePaymentMethod(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getPaymentMethod($data['id']);
            
            // QUERY para atualizar formas de pagamento            
            $sql = 'UPDATE adms_payment_method SET name = :name, updated_at = :updated_at';

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
                    'adms_payment_method',
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
            GenerateLog::generateLog("error", "Forma de pagamento não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um formas de pagamento pelo ID.
     *
     * Este método remove um formas de pagamento específico da tabela `adms_payment_method`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do formas de pagamentoa ser deletado.
     * @return bool `true` se o formas de pagamento foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deletePaymentMethod(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getPaymentMethod($id);
            
            // QUERY para deletar formas de pagamento            
            $sql = 'DELETE FROM adms_payment_method WHERE id = :id LIMIT 1';

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
                        'adms_payment_method',
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
                GenerateLog::generateLog("error", "Forma de pagamento não apagada.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Forma de pagamento não apagada.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllPaymentMethodsSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_payment_method                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
