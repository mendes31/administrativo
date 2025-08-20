<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular clientes no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar clientes no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CustomerRepository extends DbConnection
{

    /**
     * Recuperar todos os clientes com paginação.
     *
     * Este método retorna uma lista de clientes da tabela `adms_customer`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de clientes (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de clientes recuperados do banco de dados.
     */
    public function getAllCustomers(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at, updated_at
                FROM adms_customer               
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
     * Recuperar a quantidade total de clientes para paginação.
     *
     * Este método retorna a quantidade total de clientes na tabela `adms_customer`, útil para a paginação.
     *
     * @return int Quantidade total de clientes encontrados no banco de dados.
     */
    public function getAmountCustomers(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_customer';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um Cliente específico pelo ID.
     *
     * Este método retorna os detalhes de um Cliente específico identificado pelo ID.
     *
     * @param int $id ID do Cliente a ser recuperado.
     * @return array|bool Detalhes do Cliente recuperado ou `false` se não encontrado.
     */
    public function getCustomer(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at, updated_at
                FROM adms_customer
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
     * Cadastrar um novo Cliente
     *
     * Este método insere um novo Cliente na tabela `adms_customer`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do Cliente a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o Cliente foi criado com sucesso ou `false` em caso de erro.
     */
    public function createCustomer(array $data): bool|int
    {
        try {

            // QUERY para cadastrar Cliente
            $sql = 'INSERT INTO 
                adms_customer (card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at) 
                VALUES (:card_code, :card_name, :type_person, :doc, :phone, :email, :address, :description, :active, :date_birth, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':card_code', $data['card_code'], PDO::PARAM_STR);
            $stmt->bindValue(':card_name', $data['card_name'], PDO::PARAM_STR);
            $stmt->bindValue(':type_person', $data['type_person'], PDO::PARAM_STR);
            $stmt->bindValue(':doc', $data['doc'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(':active', $data['active'], PDO::PARAM_STR);

            $dateBirth = isset($data['date_birth']) ? date("Y-m-d H:i:s", strtotime($data['date_birth'])) : null;
            $stmt->bindValue(':date_birth', $dateBirth, PDO::PARAM_STR); // data nascimento

            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do cliente recém cadastrado
            $customerId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($customerId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_customer',
                    $customerId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $customerId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Cliente não cadastrada.", ['name' => $data['card_name'], 'error' => $e->getMessage()]);

            return false;
        }
    }


    /**
     * Atualizar os dados de um Cliente existente.
     *
     * Este método atualiza as informações de um Cliente existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Cliente, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateCustomer(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getCustomer($data['id']);
            
            // QUERY para atualizar Cliente
            $sql = 'UPDATE adms_customer 
                SET card_code = :card_code, card_name = :card_name, type_person = :type_person, doc = :doc, phone = :phone,
                    email = :email, address = :address, description = :description, active = :active, date_birth = :date_birth, 
                    updated_at = :updated_at
                WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':card_code', $data['card_code'], PDO::PARAM_STR);
            $stmt->bindValue(':card_name', $data['card_name'], PDO::PARAM_STR);
            $stmt->bindValue(':type_person', $data['type_person'], PDO::PARAM_STR);
            $stmt->bindValue(':doc', $data['doc'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);

            // Converter active para int (0 ou 1)
            $stmt->bindValue(':active', (int) $data['active'], PDO::PARAM_INT);

            // Garantir que date_birth está formatado corretamente
            $dateBirth = isset($data['date_birth']) ? date("Y-m-d", strtotime($data['date_birth'])) : null;
            $stmt->bindValue(':date_birth', $dateBirth, PDO::PARAM_STR);

            // Definir o timestamp de atualização
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"), PDO::PARAM_STR);

            // Garantir que o ID seja um inteiro
            $stmt->bindValue(':id', (int) $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_customer',
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
            GenerateLog::generateLog("error", "Cliente não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }


    /**
     * Deletar um Cliente pelo ID.
     *
     * Este método remove um Cliente específico da tabela `adms_customer`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do Cliente a ser deletado.
     * @return bool `true` se o Cliente foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteCustomer(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getCustomer($id);
            
            // QUERY para deletar Cliente
            $sql = 'DELETE FROM adms_customer WHERE id = :id LIMIT 1';

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
                        'adms_customer',
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
                GenerateLog::generateLog("error", "Cliente não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Cliente não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllCustomersSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, card_code, card_name 
                FROM adms_customer                
                ORDER BY card_name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextCustomerCode(): string
{
    try {
        // Consulta para obter o último código de cliente
        $sql = "SELECT card_code FROM adms_customer ORDER BY card_code DESC LIMIT 1";

        // Preparar e executar a query
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        // Obtém o último card_code
        $lastCode = $stmt->fetchColumn();

        // Verifica se encontrou um código
        if ($lastCode) {
            // Extrai a parte numérica (assumindo formato 'C00001')
            $numericPart = (int) substr($lastCode, 1);
            $nextNumber = $numericPart + 1;
        } else {
            // Caso não haja código, começa com 1
            $nextNumber = 1;
        }

        // Formata o próximo código com "C" seguido de número com 5 dígitos
        return 'C' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        // Gera log do erro e retorna um código padrão
        GenerateLog::generateLog("error", "Erro ao obter o próximo código de cliente.", ['error' => $e->getMessage()]);
        return 'C00001';
    }
}

}
