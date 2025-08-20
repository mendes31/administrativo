<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável por pagar/baixar Contas a Pagar no Banco de dados.
 *
 * Esta classe fornece métodos para criar parcelas e deletar Conta original no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class ReceiveRepository extends DbConnection
{

    /**
     * Recupera uma Conta a Pagar específica pelo ID.
     *
     * @param int $id ID da Conta a Pagar.
     * @return array|bool Dados da Conta a Pagar ou `false` se não encontrada.
     */
    public function getReceive(int $id): array|bool
    {


        $sql = 'SELECT 
                        ar.id AS id_receive, ar.num_doc, ar.num_nota, ar.issue_date, ar.description, ar.file, ar.paid, ar.original_value, ar.doc_date, 
                        ar.due_date, ar.expected_date, ar.created_at, ar.updated_at,
                        cus.card_name, 
                        au.name AS name_user,
                        af.name AS name_freq, af.id as id_freq, af.days,
                        acc.name AS name_cc, acc.id as id_cc,
                        aap.name AS name_aap, aap.id as id_aap
                FROM adms_receive ar 
                    LEFT JOIN adms_users au ON au.id = ar.user_launch_id
                    LEFT JOIN adms_frequency af on af.id = ar.frequency_id
                    LEFT JOIN adms_customer cus on cus.id = ar.partner_id
                    LEFT JOIN adms_cost_center acc on acc.id = ar.cost_center_id
                    LEFT JOIN adms_accounts_plan aap on aap.id = ar.account_id
                WHERE ar.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPayIds(int $id): array
    {

        $sql = 'SELECT *
                FROM adms_receive ap
                WHERE ap.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a soma dos movement_value dos movimentos relacionados à conta.
     */
    private function getTotalPago($id_pay): float
    {
        $sql = 'SELECT SUM(movement_value) as total_pago FROM adms_movements WHERE movement_id = :id_pay';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id_pay', $id_pay, PDO::PARAM_INT);
        $stmt->execute();
        return (float)($stmt->fetch(PDO::FETCH_ASSOC)['total_pago'] ?? 0);
    }

    /**
     * Cadastra uma nova Conta a Pagar.
     *
     * @param array $data Dados da Conta a Pagar a ser cadastrada.
     * @return bool|int ID da Conta cadastrada ou `false` em caso de erro.
     */
    public function createPay(array $dataForm, array $data,  $nova_num_doc, $novo_vencimento, $novo_valor): bool|int
    {
        $name = $this->getSupplierName($data['0']['partner_id']);


        try {
            $id_pay = $data['id_pay'] ?? null;
            $totalPago = $id_pay ? $this->getTotalPago($id_pay) : 0;
            $originalValue = isset($data['original_value']) ? (float)$data['original_value'] : 0;
            $value = $originalValue - $totalPago;
            if ($value < 0) $value = 0;

            $sql = 'INSERT INTO adms_receive (description, num_doc, partner_id, cost_center_id, 
                    user_launch_id, frequency_id, account_id, original_value, 
                    doc_date, due_date, expected_date, created_at)
                    VALUES (:description, :num_doc, :partner_id, :cost_center_id, 
                    :user_launch_id, :frequency_id, :account_id, :original_value, 
                    :doc_date, :due_date, :expected_date, :created_at)';

            $stmt = $this->getConnection()->prepare($sql);

            $description = !empty($data['description']) ? $data['description'] : $name;
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);

            $stmt->bindValue(':num_doc',  $nova_num_doc, PDO::PARAM_STR);

            $stmt->bindValue(':partner_id', $data['0']['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':cost_center_id', $data['0']['cost_center_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':user_launch_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':frequency_id', $dataForm['form']['frequency_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':account_id', $data['0']['account_id'] ?? null, PDO::PARAM_INT);

            $stmt->bindParam(':original_value', $originalValue, PDO::PARAM_STR);

            $stmt->bindValue(':doc_date', date("Y-m-d H:i:s"));
            $stmt->bindValue(':due_date', $novo_vencimento, PDO::PARAM_STR);
            $stmt->bindValue(':expected_date', $novo_vencimento, PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            $stmt->execute();

            $receiveId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($receiveId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'description' => $description,
                    'num_doc' => $nova_num_doc,
                    'partner_id' => $data['0']['partner_id'] ?? null,
                    'cost_center_id' => $data['0']['cost_center_id'] ?? null,
                    'user_launch_id' => $_SESSION['user_id'],
                    'frequency_id' => $dataForm['form']['frequency_id'] ?? null,
                    'account_id' => $data['0']['account_id'] ?? null,
                    'original_value' => $originalValue,
                    'doc_date' => date("Y-m-d H:i:s"),
                    'due_date' => $novo_vencimento,
                    'expected_date' => $novo_vencimento
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_receive',
                    $receiveId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $receiveId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não cadastrada.", ['description' => $data['0']['description'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar os dados de uma Conta existente.
     *
     * Este método atualiza as informações de um Conta existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Conta, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePay(array $dataForm, array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getReceive($data['id_pay']);
            
            $id_pay = $data['id_pay'];
            $totalPago = $this->getTotalPago($id_pay);
            $originalValue = isset($data['original_value']) ? (float)$data['original_value'] : 0;
            $value = $originalValue - $totalPago;

            $sql = 'UPDATE adms_receive 
                    SET paid = :paid,
                        updated_at = :updated_at
                    WHERE id = :id_pay';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':paid', $value == 0 ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id_pay', $data['id_pay'], PDO::PARAM_INT);

            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'paid' => $value == 0 ? 1 : 0,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_receive',
                    $data['id_pay'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não baixada.", ['id' => $data['id_pay'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateBusy(int $idPay, int $userId): bool

    {
        // var_dump($idPay);
        // var_dump($userId);
        // exit;


        try {
            $sql = 'UPDATE adms_receive SET busy = 1, user_temp = :user_id, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // var_dump($sql);

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $idPay, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Conta não baixada.", ['id' => $idPay, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function clearBusy(int $idPay): array|bool
    {
    
        try {
            // $sql = 'UPDATE adms_receive SET busy = :busy, user_temp = :user_id, updated_at = :updated_at WHERE id = :id';

            $sql = 'UPDATE adms_receive SET busy = :busy, user_temp = :user_id, updated_at = :updated_at WHERE id = :id';
    
            $stmt = $this->getConnection()->prepare($sql);
    
            $busy = 0;
            $userId = null;
            $updatedAt = date("Y-m-d H:i:s");
    
            $stmt->bindParam(':busy', $busy, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_NULL);
            $stmt->bindValue(':updated_at', $updatedAt);
            $stmt->bindValue(':id', $idPay, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não baixada.", ['id' => $idPay, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function clearUser(int $id): bool
    {
        try {
            $sql = 'UPDATE adms_receive SET busy = :busy, user_temp = :user_id, updated_at = :updated_at WHERE user_temp = :id';
    
            $stmt = $this->getConnection()->prepare($sql);
    
            $busy = 0;
            $userId = null;
            $updatedAt = date("Y-m-d H:i:s");
    
            $stmt->bindParam(':busy', $busy, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_NULL);
            $stmt->bindValue(':updated_at', $updatedAt);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não baixada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function getUserTemp(int $id): array|bool
    {
        try {
            $sql = 'SELECT user_temp FROM adms_receive WHERE id = :id';
    
            $stmt = $this->getConnection()->prepare($sql);
    
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Teste.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function clearUserTemp(int $userId): bool
{
    try {
        $sql = 'UPDATE adms_receive SET busy = 0, user_temp = NULL WHERE user_temp = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);

        return $stmt->execute();
    } catch (Exception $e) {
        GenerateLog::generateLog("error", "Erro ao liberar status busy.", ['user_id' => $userId, 'error' => $e->getMessage()]);
        return false;
    }
}


    /**
     * Atualizar os dados de uma Conta existente.
     *
     * Este método atualiza as informações de um Conta existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do Conta, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePayResidue(array $dataBD, array $data): bool

    {

        try {
            $id_pay = $data['id_pay'];
            $totalPago = $this->getTotalPago($id_pay);
            $originalValue = isset($dataBD['original_value']) ? (float)$dataBD['original_value'] : 0;
            $value = $originalValue - $totalPago;
            if ($value < 0) $value = 0;

            $sql = 'UPDATE adms_receive 
                    SET num_doc = :num_doc,
                        updated_at = :updated_at
                    WHERE id = :id_pay';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id_pay';

            // var_dump($sql);       
            // var_dump($data);

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // 'description' => "Doc: ". "{$this->data['form']['num_doc']} " ." - " . "{$this->dataBD['card_name']}" . " (Parcelamento)",

            $num_doc = "(Resíduo) " . $data['num_doc'];
            $stmt->bindValue(':num_doc', $num_doc, PDO::PARAM_STR);

            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id_pay', $data['id_pay'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Conta não baixada.", ['id' => $data['id_pay'], 'error' => $e->getMessage()]);

            return false;
        }
    }



    /**
     * Cadastra um pagamento parcial.
     *
     * @param array $data Dados de um pagamento parcial.
     * @return bool|int ID da Conta cadastrada ou `false` em caso de erro.
     */
    public function createPartialValue(array $dataForm, array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO adms_partial_value (account_id, type, partial_value, user_id, created_at)
                    VALUES (:account_id, :type, :partial_value, :user_id, :created_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':account_id', $data['id_pay'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':type', 'Receber', PDO::PARAM_STR);
            $partial_value = isset($data['value']) ? str_replace(',', '.', $data['value']) : '0.00';
            $partial_value = number_format((float) $partial_value, 2, '.', '');
            $stmt->bindParam(':partial_value', $partial_value, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->execute();
            $lastId = $this->getConnection()->lastInsertId();
            // Log de alteração
            \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                'adms_partial_value',
                $lastId,
                $_SESSION['user_id'] ?? 0,
                'INSERT',
                [],
                [
                    'account_id' => $data['id_pay'] ?? null,
                    'type' => 'Receber',
                    'partial_value' => $partial_value,
                    'user_id' => $_SESSION['user_id'] ?? 0,
                    'created_at' => date("Y-m-d H:i:s")
                ]
            );
            return $lastId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não cadastrada.", ['account_id' => $data['account_id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Cadastra um pagamento parcial.
     *
     * @param array $data Dados de um pagamento parcial.
     * @return bool|int ID da Conta cadastrada ou `false` em caso de erro.
     */
    public function createMovement(array $dataForm, array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO adms_movements (type, movement, description, movement_value, user_id, bank_id, method_id, movement_id, created_at)
                    VALUES (:type, :movement,  :description, :movement_value, :user_id,:bank_id, :method_id, :movement_id, :created_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':type', 'Entrada', PDO::PARAM_STR);
            $stmt->bindValue(':movement', 'Conta à Receber', PDO::PARAM_STR);
            $stmt->bindParam(':description',  $data['description'], PDO::PARAM_STR);
            $movement_value = isset($data['subtotal']) ? str_replace(',', '.', $data['subtotal']) : '0.00';
            $movement_value = number_format((float) $movement_value, 2, '.', '');
            $stmt->bindParam(':movement_value', $movement_value, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':bank_id', $data['bank_id'], PDO::PARAM_INT);
            $stmt->bindValue(':method_id', $data['pay_method_id'], PDO::PARAM_INT);
            $stmt->bindValue(':movement_id', $data['id_pay'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->execute();
            $lastId = $this->getConnection()->lastInsertId();
            // Log de alteração
            \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                'adms_movements',
                $lastId,
                $_SESSION['user_id'] ?? 0,
                'INSERT',
                [],
                [
                    'type' => 'Entrada',
                    'movement' => 'Conta à Receber',
                    'description' => $data['description'],
                    'movement_value' => $movement_value,
                    'user_id' => $_SESSION['user_id'] ?? 0,
                    'bank_id' => $data['bank_id'],
                    'method_id' => $data['pay_method_id'],
                    'movement_id' => $data['id_pay'] ?? null,
                    'created_at' => date("Y-m-d H:i:s")
                ]
            );
            // Atualizar amount_paid da conta relacionada
            if (!empty($data['id_pay'])) {
                $this->updatePay([], ['id_pay' => $data['id_pay']]);
            }
            return $lastId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não cadastrada.", ['movement_id' => $data['movement_id'], 'error' => $e->getMessage()]);
            return false;
        }
    }



    /**
     * Deleta a Conta a Pagar original pelo ID.
     *
     * @param int $id ID da Conta a Pagar a ser deletada.
     * @return bool `true` se deletado com sucesso ou `false` em caso de erro.
     */
    public function deletePay(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getReceive($id);
            
            $sql = 'DELETE FROM adms_receive WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            $result = $stmt->execute();

            // Registrar log de alteração se a exclusão foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_receive',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $oldData,
                    []
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar conta.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getSupplierName(int $partner_id): string
    {

        $sql = "SELECT 	card_name FROM adms_supplier WHERE id = :partner_id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':partner_id', $partner_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getSupplierNameAccount(int $id): string
    {

        $sql = "SELECT as2.card_name from adms_receive ap 
                INNER JOIN adms_supplier as2 on as2.id = ap.partner_id 
                WHERE ap.id = :id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function existsNumDocForPartner($numDoc, $partnerId, $ignoreId = null)
    {


        $sql = "SELECT COUNT(*) FROM adms_receive WHERE num_doc = :num_doc AND partner_id = :partner_id";


        if ($ignoreId) {
            $sql .= " AND id != :ignore_id"; // Ignorar o próprio ID
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $numDoc);
        $stmt->bindParam(':partner_id', $partnerId);

        if ($ignoreId) {
            $stmt->bindParam(':ignore_id', $ignoreId);
        }

        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
