<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável por parcelar Contas a Pagar no Banco de dados.
 *
 * Esta classe fornece métodos para criar parcelas e deletar Conta original no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class InstallmentsRepository extends DbConnection
{

    /**
     * Recupera uma Conta a Pagar específica pelo ID.
     *
     * @param int $id ID da Conta a Pagar.
     * @return array|bool Dados da Conta a Pagar ou `false` se não encontrada.
     */
    public function getInstallments(int $id): array|bool
    {


        $sql = 'SELECT ap.id AS id_pay, ap.num_doc, ap.description, ap.file, ap.paid, ap.original_value, ap.doc_date, ap.due_date, ap.expected_date, ap.created_at, ap.updated_at,
                        sup.card_name, 
                        au.name AS name_user,
                        af.name AS name_freq, af.id as id_freq, af.days,
                        acc.name AS name_cc, acc.id as id_cc,
                        aap.name AS name_aap, aap.id as id_aap
                FROM adms_pay ap 
                    LEFT JOIN adms_users au ON au.id = ap.user_launch_id
                    LEFT JOIN adms_frequency af on af.id = ap.frequency_id
                    LEFT JOIN adms_supplier sup on sup.id = ap.partner_id
                    LEFT JOIN adms_cost_center acc on acc.id = ap.cost_center_id
                    LEFT JOIN adms_accounts_plan aap on aap.id = ap.account_id
                WHERE ap.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPayIds(int $id): array
    {

        $sql = 'SELECT *
                FROM adms_pay ap
                WHERE ap.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastra uma nova Conta a Pagar.
     *
     * @param array $data Dados da Conta a Pagar a ser cadastrada.
     * @return bool|int ID da Conta cadastrada ou `false` em caso de erro.
     */
    public function createPay(array $dataForm, array $data, $nova_num_doc, $novo_vencimento, $novo_valor, $installment_number = null, $issue_date = null): bool|int
    {
        $original = $data[0];
        var_dump($original);
        $name = $this->getSupplierName($original['partner_id']);
        try {
            $sql = 'INSERT INTO adms_pay (
                description, num_doc, num_nota, file, paid, partner_id, card_code_fornecedor, cost_center_id, user_launch_id, frequency_id, account_id, busy, user_temp,  original_value, doc_date, due_date, expected_date, installment_number, issue_date, created_at, updated_at
            ) VALUES (
                :description, :num_doc, :num_nota, :file, :paid, :partner_id, :card_code_fornecedor, :cost_center_id, :user_launch_id, :frequency_id, :account_id, :busy, :user_temp, :original_value, :doc_date, :due_date, :expected_date, :installment_number, :issue_date, :created_at, :updated_at
            )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':description', !empty($original['description']) ? $original['description'] : $name, PDO::PARAM_STR);
            $stmt->bindValue(':num_doc', $nova_num_doc, PDO::PARAM_STR);
            $stmt->bindValue(':num_nota', $original['num_nota'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':file', $original['file'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':paid', 0, PDO::PARAM_INT);
            $stmt->bindValue(':partner_id', $original['partner_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':card_code_fornecedor', $original['card_code_fornecedor'] ?? null, PDO::PARAM_STR);
            
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
            //$stmt->bindValue(':issue_date', $issue_date ?? date('Y-m-d'), PDO::PARAM_STR);
            if (!empty($original['issue_date'])) {
                $stmt->bindValue(':issue_date', $original['issue_date'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':issue_date', null, PDO::PARAM_NULL);
            }
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
     * Deleta a Conta a Pagar original pelo ID.
     *
     * @param int $id ID da Conta a Pagar a ser deletada.
     * @return bool `true` se deletado com sucesso ou `false` em caso de erro.
     */
    public function deletePay(int $id): bool
    {
        try {
            $sql = 'DELETE FROM adms_pay WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
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

        $sql = "SELECT as2.card_name from adms_pay ap 
                INNER JOIN adms_supplier as2 on as2.id = ap.partner_id 
                WHERE ap.id = :id LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function existsNumDocForPartner($numDoc, $partnerId, $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM adms_pay 
                WHERE num_doc = :num_doc 
                  AND partner_id = :partner_id";
    
        if (!empty($ignoreId)) {
            $sql .= " AND id != :ignore_id"; // Ignorar o próprio ID na edição
        }
    
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $numDoc);
        $stmt->bindParam(':partner_id', $partnerId, \PDO::PARAM_INT);
    
        if (!empty($ignoreId)) {
            $stmt->bindParam(':ignore_id', $ignoreId, \PDO::PARAM_INT);
        }
    
        $stmt->execute();
    
        return (int)$stmt->fetchColumn() > 0;
    }
    

}
