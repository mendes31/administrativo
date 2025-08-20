<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular fornecedores no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar fornecedores no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class SupplierRepository extends DbConnection
{

    /**
     * Recuperar todos os fornecedores com paginação.
     *
     * Este método retorna uma lista de fornecedores da tabela `adms_supplier`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de fornecedores (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de fornecedores recuperados do banco de dados.
     */
    public function getAllSuppliers(array $criteria, int $page = 1, int $limitResult = 20): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // Inicializa a parte WHERE da consulta
        $whereClauses = [];
        $parameters = [];

        // Verifica se o 'search' foi passado e adiciona à cláusula WHERE
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'card_code LIKE :search OR card_name LIKE :search'; // busca por 'card_code' ou 'card_name'
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }

        // Verifica se cada critério de pesquisa adicional foi passado
        if (!empty($criteria['card_code'])) {
            $whereClauses[] = 'card_code LIKE :card_code';
            $parameters[':card_code'] = '%' . $criteria['card_code'] . '%';
        }

        if (!empty($criteria['card_name'])) {
            $whereClauses[] = 'card_name LIKE :card_name';
            $parameters[':card_name'] = '%' . $criteria['card_name'] . '%';
        }

        if (!empty($criteria['type_person'])) {
            $whereClauses[] = 'type_person = :type_person';
            $parameters[':type_person'] = $criteria['type_person'];
        }

        if (!empty($criteria['email'])) {
            $whereClauses[] = 'email LIKE :email';
            $parameters[':email'] = '%' . $criteria['email'] . '%';
        }

        if (isset($criteria['active'])) {
            $whereClauses[] = 'active = :active';
            $parameters[':active'] = (int) $criteria['active'];
        }

        // Se houver cláusulas WHERE, junta elas com 'AND'
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Consulta SQL com a parte WHERE dinâmica
        $sql = 'SELECT id, card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at, updated_at
            FROM adms_supplier ' . $whereSql . '
            ORDER BY card_code ASC
            LIMIT :limit OFFSET :offset';

        // Preparar a consulta
        $stmt = $this->getConnection()->prepare($sql);

        // Bind dos parâmetros
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Executar a consulta
        $stmt->execute();

        // Retornar os resultados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Recuperar a quantidade total de fornecedores para paginação.
     *
     * Este método retorna a quantidade total de fornecedores na tabela `adms_supplier`, útil para a paginação.
     *
     * @return int Quantidade total de fornecedores encontrados no banco de dados.
     */
    public function getAmountSuppliers(array $criteria = []): int
    {
        $whereClauses = [];
        $parameters = [];
        if (!empty($criteria['search'])) {
            $whereClauses[] = 'card_code LIKE :search OR card_name LIKE :search';
            $parameters[':search'] = '%' . $criteria['search'] . '%';
        }
        if (!empty($criteria['card_code'])) {
            $whereClauses[] = 'card_code LIKE :card_code';
            $parameters[':card_code'] = '%' . $criteria['card_code'] . '%';
        }
        if (!empty($criteria['card_name'])) {
            $whereClauses[] = 'card_name LIKE :card_name';
            $parameters[':card_name'] = '%' . $criteria['card_name'] . '%';
        }
        if (!empty($criteria['type_person'])) {
            $whereClauses[] = 'type_person = :type_person';
            $parameters[':type_person'] = $criteria['type_person'];
        }
        if (!empty($criteria['email'])) {
            $whereClauses[] = 'email LIKE :email';
            $parameters[':email'] = '%' . $criteria['email'] . '%';
        }
        if (isset($criteria['active'])) {
            $whereClauses[] = 'active = :active';
            $parameters[':active'] = (int) $criteria['active'];
        }
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_supplier ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um Fornecedor específico pelo ID.
     *
     * Este método retorna os detalhes de um Fornecedor específico identificado pelo ID.
     *
     * @param int $id ID do Fornecedor a ser recuperado.
     * @return array|bool Detalhes do Fornecedor recuperado ou `false` se não encontrado.
     */
    public function getSupplier(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at, updated_at
                FROM adms_supplier
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
     * Cadastrar um novo Fornecedor
     *
     * Este método insere um novo Fornecedor na tabela `adms_supplier`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do Fornecedor a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o Fornecedor foi criado com sucesso ou `false` em caso de erro.
     */
    public function createSupplier(array $data): bool|int
    {
        try {

            // QUERY para cadastrar Fornecedor
            $sql = 'INSERT INTO 
                adms_supplier (card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at) 
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

            // Retornar o ID do fornecedor recém cadastrado
            $supplierId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($supplierId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_supplier',
                    $supplierId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $supplierId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Fornecedor não cadastrada.", ['name' => $data['card_name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function importSupplier(array $data): bool|int
    {
        try {
            // Validar os dados necessários (mínimo de campos esperados)
            if (count($data) < 10) {
                throw new Exception("Dados incompletos para importação do fornecedor.");
            }

            $sql = 'INSERT INTO 
            adms_supplier (card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at) 
            VALUES (:card_code, :card_name, :type_person, :doc, :phone, :email, :address, :description, :active, :date_birth, :created_at)';

            $stmt = $this->getConnection()->prepare($sql);

            // Bind de parâmetros
            $stmt->bindValue(':card_code', $data[0], PDO::PARAM_STR);
            $stmt->bindValue(':card_name', $data[1], PDO::PARAM_STR);
            $stmt->bindValue(':type_person', $data[2], PDO::PARAM_STR);
            $stmt->bindValue(':doc', $data[3], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data[4], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data[5], PDO::PARAM_STR);
            $stmt->bindValue(':address', $data[6], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data[7], PDO::PARAM_STR);
            $stmt->bindValue(':active', (int) $data[8], PDO::PARAM_INT);

            // Tratar data de nascimento
            $dateBirth = !empty($data[9]) ? date("Y-m-d H:i:s", strtotime($data[9])) : null;
            $stmt->bindValue(':date_birth', $dateBirth, PDO::PARAM_STR);

            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            $stmt->execute();

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            // Evitar erro de índice inexistente ao tentar logar o nome
            $cardName = $data[1] ?? 'Desconhecido';

            GenerateLog::generateLog("error", "Fornecedor não cadastrado via importação.", [
                'card_name' => $cardName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }


    public function validaSupplier(string $card_code): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, card_code, card_name, type_person, doc, phone, email, address, description, active, date_birth, created_at, updated_at
                FROM adms_supplier
                WHERE card_code = :card_code';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':card_code', $card_code, PDO::PARAM_STR);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * Atualizar os dados de um Fornecedor existente.
     *
     * Este método atualiza as informações de um Fornecedor existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Fornecedor, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateSupplier(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getSupplier($data['id']);
            
            // QUERY para atualizar Fornecedor
            $sql = 'UPDATE adms_supplier 
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
                    'adms_supplier',
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
            GenerateLog::generateLog("error", "Fornecedor não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }


    /**
     * Deletar um Fornecedor pelo ID.
     *
     * Este método remove um Fornecedor específico da tabela `adms_supplier`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do Fornecedor a ser deletado.
     * @return bool `true` se o Fornecedor foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteSupplier(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getSupplier($id);
            
            // QUERY para deletar Fornecedor
            $sql = 'DELETE FROM adms_supplier WHERE id = :id LIMIT 1';

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
                        'adms_supplier',
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
                GenerateLog::generateLog("error", "Fornecedor não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Fornecedor não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllSuppliersSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, card_code, card_name 
                FROM adms_supplier
                WHERE active = 1               
                ORDER BY card_name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextSupplierCode(): string
    {
        try {
            // Consulta para obter o último código de Fornecedor
            $sql = "SELECT card_code FROM adms_supplier ORDER BY card_code DESC LIMIT 1";

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
            return 'F' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            // Gera log do erro e retorna um código padrão
            GenerateLog::generateLog("error", "Erro ao obter o próximo código de Fornecedor.", ['error' => $e->getMessage()]);
            return 'F00001';
        }
    }
    public function getSupplierName(int $partner_id): string
    {
        // var_dump($partner_id);
        // exit;
        $sql = "SELECT 	card_name FROM adms_supplier WHERE id = :partner_id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':partner_id', $partner_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() ?: null;
    }
}
