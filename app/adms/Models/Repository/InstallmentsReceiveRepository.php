<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável por parcelar Contas a Receber no Banco de dados.
 *
 * Esta classe fornece métodos para criar parcelas e deletar Conta original no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class InstallmentsReceiveRepository extends DbConnection
{

    /**
     * Recupera uma Conta a Receber específica pelo ID.
     *
     * @param int $id ID da Conta a Receber.
     * @return array|bool Dados da Conta a Receber ou `false` se não encontrada.
     */
    public function getReceiveInstallments(int $id): array|bool
    {


        $sql = 'SELECT ar.id AS id_receive, ar.num_doc, ar.description, ar.file, ar.paid, ar.original_value, ar.doc_date, ar.due_date, ar.expected_date, ar.created_at, ar.updated_at,
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

    public function getReceiveIds(int $id): array
    {

        $sql = 'SELECT *
                FROM adms_receive ar
                WHERE ar.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastra uma nova Conta a Receber.
     *
     * @param array $data Dados da Conta a Receber a ser cadastrada.
     * @return bool|int ID da Conta cadastrada ou `false` em caso de erro.
     */
    public function createReceive(array $dataForm, array $data, $nova_num_doc, $novo_vencimento, $novo_valor, $installment_number = null, $issue_date = null): bool|int
    {
        var_dump([
            'dataForm' => $dataForm,
            'data' => $data,
            'nova_num_doc' => $nova_num_doc,
            'novo_vencimento' => $novo_vencimento,
            'novo_valor' => $novo_valor,
        ]);
       
        $original = $data[0];
        $name = $this->getCustomerName($original['partner_id']);
        try {
            $sql = 'INSERT INTO adms_receive (
                description, num_doc, num_nota, file, paid, partner_id, card_code_cliente, cost_center_id, user_launch_id, frequency_id, account_id, busy, user_temp, original_value, doc_date, due_date, expected_date,  installment_number, issue_date, created_at, updated_at
            ) VALUES (
                :description, :num_doc, :num_nota, :file, :paid, :partner_id, :card_code_cliente, :cost_center_id, :user_launch_id, :frequency_id, :account_id, :busy, :user_temp,  :original_value, :doc_date, :due_date, :expected_date, :installment_number, :issue_date, :created_at, :updated_at
            )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':description', !empty($original['description']) ? $original['description'] : $name, PDO::PARAM_STR);
            $stmt->bindValue(':num_doc', $nova_num_doc, PDO::PARAM_STR);
            $stmt->bindValue(':num_nota', $original['num_nota'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':file', $original['file'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':paid', 0, PDO::PARAM_INT);
            $stmt->bindValue(':partner_id', $original['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':card_code_cliente', $original['card_code_cliente'] ?? null, PDO::PARAM_STR);
            
            $stmt->bindValue(':cost_center_id', $original['cost_center_id'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':user_launch_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':frequency_id', $dataForm['form']['frequency_id'] ?? $original['frequency_id'] ?? null, PDO::PARAM_INT);
           
            $stmt->bindValue(':account_id', $original['account_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':busy', 0, PDO::PARAM_INT);
            $stmt->bindValue(':user_temp', null, PDO::PARAM_NULL);
            
            $stmt->bindValue(':original_value', $novo_valor, PDO::PARAM_STR);

            $stmt->bindValue(':doc_date', $original['doc_date'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':due_date', $novo_vencimento, PDO::PARAM_STR);
            $stmt->bindValue(':expected_date', $original['expected_date'] ?? $novo_vencimento, PDO::PARAM_STR);
            
            $stmt->bindValue(':installment_number', $installment_number, PDO::PARAM_INT);
            $stmt->bindValue(':issue_date', $issue_date ?? date('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':updated_at', null, PDO::PARAM_NULL);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não cadastrada.", ['description' => $original['description'] ?? '', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
    * Deleta a Conta a Receber original pelo ID.
     *
     * @param int $id ID da Conta a Receber a ser deletada.
     * @return bool `true` se deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteReceive(int $id): bool
    {
        try {
            $sql = 'DELETE FROM adms_receive WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar conta.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getCustomerName(int $partner_id): string
    {

        $sql = "SELECT 	card_name FROM adms_customer WHERE id = :partner_id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':partner_id', $partner_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getCustomerNameAccount(int $id): string
    {

        $sql = "SELECT cus.card_name from adms_receive ar 
                INNER JOIN adms_customer cus on cus.id = ar.partner_id 
                WHERE ar.id = :id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function existsNumDocCliPartner($numDoc, $partnerId, $ignoreId = null) : bool
    {
       

        $sql = "SELECT COUNT(*) FROM adms_receive 
                WHERE num_doc = :num_doc 
                AND partner_id = :partner_id";


        if (!empty($ignoreId)) {
            $sql .= " AND id != :ignore_id"; // Ignorar o próprio ID
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $numDoc);
        $stmt->bindParam(':partner_id', $partnerId);

        if (!empty($ignoreId)) {
            $stmt->bindParam(':ignore_id', $ignoreId,  \PDO::PARAM_INT);
        }
  
        $stmt->execute();    
        return (int)$stmt->fetchColumn() > 0;
    }

}