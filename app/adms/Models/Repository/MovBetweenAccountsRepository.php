<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class MovBetweenAccountsRepository extends DbConnection
{
    /**
     * Recuperar todas as contas bancárias ordenadas por nome do banco.
     *
     * @return array Lista de contas com saldo.
     */
    public function getAllAccounts(): array
    {
        $sql = 'SELECT id, bank_name, account, balance FROM adms_bank_accounts ORDER BY bank_name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera todas as transferências entre contas com paginação e filtros.
     *
     * @param int $page Página atual
     * @param int $limit Limite de registros por página
     * @param string $filterFrom Conta origem (parcial)
     * @param string $filterTo Conta destino (parcial)
     * @param string $filterDescription Descrição (parcial)
     * @param string $filterUser Usuário (parcial)
     * @param string $filterDate Data (parcial, formato dd/mm/yyyy ou yyyy-mm-dd)
     * @return array Lista de transferências
     */
    public function getAllMovBetweenAccounts(int $page, int $limit, string $filterFrom = '', string $filterTo = '', string $filterDescription = '', string $filterUser = '', string $filterDate = ''): array
    {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        if (!empty($filterFrom)) {
            $where[] = 'ab1.bank_name LIKE :from_bank_name';
            $params[':from_bank_name'] = '%' . $filterFrom . '%';
        }
        if (!empty($filterTo)) {
            $where[] = 'ab2.bank_name LIKE :to_bank_name';
            $params[':to_bank_name'] = '%' . $filterTo . '%';
        }
        if (!empty($filterDescription)) {
            $where[] = 'bt.description LIKE :description';
            $params[':description'] = '%' . $filterDescription . '%';
        }
        if (!empty($filterUser)) {
            $where[] = 'au.name LIKE :user_name';
            $params[':user_name'] = '%' . $filterUser . '%';
        }
        if (!empty($filterDate)) {
            // Permite busca por data no formato brasileiro ou americano
            $where[] = '(DATE_FORMAT(bt.created_at, "%d/%m/%Y") LIKE :created_at OR DATE(bt.created_at) LIKE :created_at2)';
            $params[':created_at'] = '%' . $filterDate . '%';
            $params[':created_at2'] = '%' . $filterDate . '%';
        }
        $sql = 'SELECT  bt.id, ab1.bank_name as from_bank_name, ab2.bank_name as to_bank_name, bt.amount as amount, bt.description as description, au.name as user_name, bt.created_at
                FROM adms_bank_transfers bt
                LEFT JOIN adms_bank_accounts ab1 ON ab1.id = bt.origin_id
                LEFT JOIN adms_bank_accounts ab2 ON ab2.id = bt.destination_id
                LEFT JOIN adms_users au ON au.id = bt.user_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY bt.created_at DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a quantidade total de transferências entre contas com filtros.
     *
     * @param string $filterFrom Conta origem (parcial)
     * @param string $filterTo Conta destino (parcial)
     * @param string $filterDescription Descrição (parcial)
     * @param string $filterUser Usuário (parcial)
     * @param string $filterDate Data (parcial, formato dd/mm/yyyy ou yyyy-mm-dd)
     * @return int Quantidade total de transferências
     */
    public function getAmountMovBetweenAccounts(string $filterFrom = '', string $filterTo = '', string $filterDescription = '', string $filterUser = '', string $filterDate = ''): int
    {
        $where = [];
        $params = [];
        if (!empty($filterFrom)) {
            $where[] = 'ab1.bank_name LIKE :from_bank_name';
            $params[':from_bank_name'] = '%' . $filterFrom . '%';
        }
        if (!empty($filterTo)) {
            $where[] = 'ab2.bank_name LIKE :to_bank_name';
            $params[':to_bank_name'] = '%' . $filterTo . '%';
        }
        if (!empty($filterDescription)) {
            $where[] = 'bt.description LIKE :description';
            $params[':description'] = '%' . $filterDescription . '%';
        }
        if (!empty($filterUser)) {
            $where[] = 'au.name LIKE :user_name';
            $params[':user_name'] = '%' . $filterUser . '%';
        }
        if (!empty($filterDate)) {
            $where[] = '(DATE_FORMAT(bt.created_at, "%d/%m/%Y") LIKE :created_at OR DATE(bt.created_at) LIKE :created_at2)';
            $params[':created_at'] = '%' . $filterDate . '%';
            $params[':created_at2'] = '%' . $filterDate . '%';
        }
        $sql = 'SELECT COUNT(*) as total
                FROM adms_bank_transfers bt
                LEFT JOIN adms_bank_accounts ab1 ON ab1.id = bt.origin_id
                LEFT JOIN adms_bank_accounts ab2 ON ab2.id = bt.destination_id
                LEFT JOIN adms_users au ON au.id = bt.user_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Realiza uma transferência entre duas contas, garantindo atomicidade e validação de saldo.
     *
     * @param int $fromAccountId ID da conta de origem.
     * @param int $toAccountId ID da conta de destino.
     * @param float $amount Valor a ser transferido.
     * @return bool|int ID da transferência se realizada com sucesso, `false` caso contrário.
     */
    public function transfer(int $fromAccountId, int $toAccountId, float $amount, string $description): bool|int
    {
        try {
            // Log início repository
            \App\adms\Helpers\GenerateLog::generateLog('debug', 'Repository: Início do método transfer', [
                'from' => $fromAccountId, 'to' => $toAccountId, 'amount' => $amount
            ]);
            //echo "<pre>Repository: Início do método transfer</pre>";

            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Verificar saldo da conta de origem com bloqueio de linha
            $sql = 'SELECT balance FROM adms_bank_accounts WHERE id = :id FOR UPDATE';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $fromAccountId]);
            $origin = $stmt->fetch(PDO::FETCH_ASSOC);

            //echo "<pre>Repository: Saldo da conta origem: "; var_dump($origin); echo "</pre>";
            \App\adms\Helpers\GenerateLog::generateLog('debug', 'Repository: Saldo da conta origem', ['origin' => $origin]);

            if (!$origin || $origin['balance'] < $amount) {
                $conn->rollBack();
                //echo "<pre>Repository: Saldo insuficiente ou conta não encontrada</pre>";
                \App\adms\Helpers\GenerateLog::generateLog('debug', 'Repository: Saldo insuficiente ou conta não encontrada', ['origin' => $origin, 'amount' => $amount]);
                return false;
            }

            // Debitar da conta de origem
            $sql = 'UPDATE adms_bank_accounts SET balance = balance - :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':id', $fromAccountId, PDO::PARAM_INT);
            $stmt->execute();
            //echo "<pre>Repository: Débito realizado</pre>";

            // Creditar na conta de destino
            $sql = 'UPDATE adms_bank_accounts SET balance = balance + :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':id', $toAccountId, PDO::PARAM_INT);
            $stmt->execute();
            // echo "<pre>Repository: Crédito realizado</pre>";

            // Registrar a transferência
            $sql = 'INSERT INTO adms_bank_transfers (origin_id, destination_id, amount, description, user_id, created_at) 
                    VALUES (:origin_id, :destination_id, :amount, :description, :user_id, :created_at)';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':origin_id', $fromAccountId, PDO::PARAM_INT);
            $stmt->bindValue(':destination_id', $toAccountId, PDO::PARAM_INT);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->execute();
            $transferId = $conn->lastInsertId();
            // echo "<pre>Repository: Transferência registrada, ID: $transferId</pre>";
            \App\adms\Helpers\GenerateLog::generateLog('debug', 'Repository: Transferência registrada', ['transferId' => $transferId]);

            $conn->commit();
            // echo "<pre>Repository: Commit realizado</pre>";
            return $transferId;

        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            // echo "<pre>Repository: Erro na transferência: " . $e->getMessage() . "</pre>";
            \App\adms\Helpers\GenerateLog::generateLog('error', 'Repository: Erro na transferência', [
                'from_account' => $fromAccountId,
                'to_account' => $toAccountId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recupera uma transferência específica pelo ID
     *
     * @param int $id ID da transferência
     * @return array|null Dados da transferência ou null se não encontrada
     */
    public function getMovBetweenAccounts(int $id): ?array
    {
        $sql = 'SELECT bt.id as id, 
                bt.origin_id as origin_id,
                bt.destination_id as destination_id,
                ab1.bank_name as origin_name,
                ab2.bank_name as destination_name,
                au.name as user_name,
                bt.amount as amount,
                bt.description as description,
                bt.created_at,
                bt.updated_at
                FROM adms_bank_transfers bt
                LEFT JOIN adms_bank_accounts ab1 ON ab1.id = bt.origin_id
                LEFT JOIN adms_bank_accounts ab2 ON ab2.id = bt.destination_id
                LEFT JOIN adms_users au ON au.id = bt.user_id
                WHERE bt.id = :id
                LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Atualiza uma transferência entre contas
     *
     * @param array $data Dados da transferência
     * @return bool
     */
    public function updateMovBetweenAccounts(array $data): bool
    {
        echo "<pre>Repository: Dados da transferência: "; var_dump($data); echo "</pre>";
        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Buscar dados da transferência original
            $sql = 'SELECT origin_id, destination_id, amount FROM adms_bank_transfers WHERE id = :id FOR UPDATE';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->execute();
            $originalTransfer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$originalTransfer) {
                $conn->rollBack();
                return false;
            }

            // Desfazer saldo da transferência original
            $sql = 'UPDATE adms_bank_accounts SET balance = balance + :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $originalTransfer['amount'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $originalTransfer['origin_id'], PDO::PARAM_INT);
            $stmt->execute();

            $sql = 'UPDATE adms_bank_accounts SET balance = balance - :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $originalTransfer['amount'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $originalTransfer['destination_id'], PDO::PARAM_INT);
            $stmt->execute();

            // Aplicar nova transferência
            $sql = 'UPDATE adms_bank_accounts SET balance = balance - :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $data['value'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $data['source_account_id'], PDO::PARAM_INT);
            $stmt->execute();

            $sql = 'UPDATE adms_bank_accounts SET balance = balance + :amount WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $data['value'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $data['destination_account_id'], PDO::PARAM_INT);
            $stmt->execute();

            // Atualizar registro da transferência
            $sql = 'UPDATE adms_bank_transfers 
                    SET origin_id = :origin_id,
                        destination_id = :destination_id,
                        amount = :amount,
                        description = :description,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':origin_id', $data['source_account_id'], PDO::PARAM_INT);
            $stmt->bindValue(':destination_id', $data['destination_account_id'], PDO::PARAM_INT);
            $stmt->bindValue(':amount', $data['value'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->execute();

            $conn->commit();
            return true;

        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            GenerateLog::generateLog("error", "Erro ao atualizar transferência", [
                'id' => $data['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
