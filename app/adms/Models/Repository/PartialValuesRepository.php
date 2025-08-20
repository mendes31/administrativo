<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular Contas a Pagar no Banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar Contas a Pagar no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class PartialValuesRepository extends DbConnection
{

    /**
     * Recupera todas os pagamentos realizados em uma conta.
     *
     * @return array Lista de pagamentos realizados em uma conta espedifica pelo ID.
     */
    public function getPartialValue(int $id): array
    {

        $sql = 'SELECT * FROM adms_partial_value 
                        WHERE account_id = :id AND type = "Pagar"
                        ORDER BY id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }

    public function getMovementValues(int $id): array
    {

        $sql = 'SELECT  m.id as "id",
                        m.created_at, 
                        m.movement_id as "id_mov",
                        m.movement_value,
                        m.type, 	
                        m.bank_id as "id_bank_pgto", 
                        ab.bank_name as "name_bank", 
                        m.method_id as "id_method",
                        apm.name as "name_method",
                        m.user_id as "id_user_pgto",
                        au.name as "name_user_pegto"
                    FROM adms_pay ap 
                    LEFT JOIN adms_movements m ON m.movement_id = ap.id
                    LEFT JOIN adms_users au ON au.id = m.user_id
                    LEFT JOIN adms_supplier sup ON sup.id = ap.partner_id
                    LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                    LEFT JOIN adms_payment_method apm ON apm.id = m.method_id 
                    WHERE m.movement_id = :id
                    ORDER BY created_at desc';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * Busca um movimento realizado pelo ID do movimento (m.id).
     */
    public function getMovementById(int $id): ?array
    {
        $sql = 'SELECT  m.id as "id",
                        m.created_at,
                        m.movement_id as "id_mov",
                        m.movement_value,
                        m.type,
                        m.movement as "movement",
                        m.bank_id as "id_bank_pgto",
                        ab.bank_name as "name_bank",
                        m.method_id as "id_method",
                        apm.name as "name_method",
                        m.user_id as "id_user_pgto",
                        au.name as "name_user_pegto"
                FROM adms_pay ap
                LEFT JOIN adms_movements m ON m.movement_id = ap.id
                LEFT JOIN adms_users au ON au.id = m.user_id
                LEFT JOIN adms_supplier sup ON sup.id = ap.partner_id
                LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                LEFT JOIN adms_payment_method apm ON apm.id = m.method_id
                WHERE m.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca um movimento de recebimento realizado pelo ID do movimento (m.id).
     */
    public function getMovementReceiveById(int $id): ?array
    {
        
        $sql = 'SELECT  m.id as "id",
                        m.created_at,
                        m.movement_id as "id_mov",
                        m.movement_value,
                        m.type,
                        m.movement as "movement",
                        m.bank_id as "id_bank_pgto",
                        ab.bank_name as "name_bank",
                        m.method_id as "id_method",
                        apm.name as "name_method",
                        m.user_id as "id_user_pgto",
                        au.name as "name_user_pegto"
                FROM adms_receive ar
                LEFT JOIN adms_movements m ON m.movement_id = ar.id
                LEFT JOIN adms_users au ON au.id = m.user_id
                LEFT JOIN adms_customer cus ON cus.id = ar.partner_id
                LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                LEFT JOIN adms_payment_method apm ON apm.id = m.method_id
                WHERE m.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
