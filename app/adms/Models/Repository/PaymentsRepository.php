<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
class PaymentsRepository extends DbConnection
{
    // Função para normalizar valores monetários do formato brasileiro para americano
    private function normalizarValor($valor)
    {
        $valor = trim($valor);
        if (strpos($valor, ',') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }
        return (float) $valor;
    }

    /**
     * Recupera todas as Contas a Pagar com paginação.
     *
     * @param int $page Número da página para recuperação de Contas a Pagar (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param array $filtros Filtros para a consulta.
     * @return array Lista de Contas a Pagar recuperadas do banco de dados.
     */
    public function getAllPayments(int $page = 1, int $limitResult = 100, array $filtros = []): array
    {
        $offset = max(0, ($page - 1) * $limitResult);

        $where = [];
        $params = [];
        $dataType = $filtros['data_type'] ?? 'due_date';

        if (!empty($filtros['num_doc'])) {
            $where[] = 'ap.num_doc LIKE :num_doc';
            $params[':num_doc'] = '%' . $filtros['num_doc'] . '%';
        }
        if (!empty($filtros['num_nota'])) {
            $where[] = 'ap.num_nota LIKE :num_nota';
            $params[':num_nota'] = '%' . $filtros['num_nota'] . '%';
        }
        if (!empty($filtros['card_code_fornecedor'])) {
            $where[] = 'ap.card_code_fornecedor LIKE :card_code_fornecedor';
            $params[':card_code_fornecedor'] = '%' . $filtros['card_code_fornecedor'] . '%';
        }
        if (!empty($filtros['fornecedor'])) {
            $where[] = 'sup.card_name LIKE :fornecedor';
            $params[':fornecedor'] = '%' . $filtros['fornecedor'] . '%';
        }
        if (!empty($filtros['vencimento_hoje'])) {
            $dataType = $filtros['data_type'] ?? 'due_date';
            $where[] = 'DATE(ap.' . $dataType . ') = CURDATE()';
        }
        if (!empty($filtros['data_inicial'])) {
            $where[] = 'DATE(ap.' . $dataType . ') >= :data_inicial';
            $params[':data_inicial'] = $filtros['data_inicial'];
        }
        if (!empty($filtros['data_final'])) {
            $where[] = 'DATE(ap.' . $dataType . ') <= :data_final';
            $params[':data_final'] = $filtros['data_final'];
        }
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'pendente') {
                $where[] = 'ap.paid = 0';
            } elseif ($filtros['status'] === 'pago') {
                $where[] = 'ap.paid = 1';
            } elseif ($filtros['status'] === 'vencidos') {
                $where[] = 'ap.paid = 0 AND ap.due_date < CURDATE()';
            }
        }
        if ($dataType === 'issue_date') {
            $where[] = 'ap.issue_date IS NOT NULL';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT ap.id AS id_pay, ap.num_doc, ap.num_nota, ap.card_code_fornecedor, ap.description, ap.busy, ap.user_temp, ap.file, ap.paid, ap.original_value,   ap.doc_date, ap.due_date, ap.expected_date, ap.created_at, ap.updated_at,
                    sup.card_name, 
                    au.name AS name_user,
                    au2.name AS name_user_temp, 
                    af.name AS name_freq, af.days, 
                    acc.name AS name_cc, 
                    aap.name AS name_aap,
                    ap.installment_number, ap.issue_date,
                    (SELECT COUNT(*) FROM adms_pay ap2 WHERE ap2.num_doc = ap.num_doc) AS total_installments
                FROM adms_pay ap 
                    LEFT JOIN adms_users au ON au.id = ap.user_launch_id
                    LEFT JOIN adms_users au2 ON au2.id = ap.user_temp
                    LEFT JOIN adms_frequency af on af.id = ap.frequency_id
                    LEFT JOIN adms_supplier sup on sup.id = ap.partner_id
                    LEFT JOIN adms_cost_center acc on acc.id = ap.cost_center_id
                    LEFT JOIN adms_accounts_plan aap on aap.id = ap.account_id
                ' . $whereSql . '
                ORDER BY due_date DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Adiciona o campo total_pago para cada conta
        foreach ($result as &$pay) {
            $sqlTotal = 'SELECT SUM(movement_value) as total_pago FROM adms_movements WHERE movement_id = :id_pay';
            $stmtTotal = $this->getConnection()->prepare($sqlTotal);
            $stmtTotal->bindValue(':id_pay', $pay['id_pay'], PDO::PARAM_INT);
            $stmtTotal->execute();
            $pay['total_pago'] = (float)($stmtTotal->fetch(PDO::FETCH_ASSOC)['total_pago'] ?? 0);

            // Buscar todos os movimentos (pagamentos) da conta
            $sqlMov = 'SELECT * FROM adms_movements WHERE movement_id = :id_pay';
            $stmtMov = $this->getConnection()->prepare($sqlMov);
            $stmtMov->bindValue(':id_pay', $pay['id_pay'], PDO::PARAM_INT);
            $stmtMov->execute();
            $pay['movements'] = $stmtMov->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        unset($pay);
        return $result;
    }

    // public function getPaymentsStatus(?int $id = null, int $limit = 100): array
    // {
    //     if ($id) {
    //         $sql = 'SELECT id AS id_pay, busy FROM adms_pay WHERE id = :id LIMIT 1';
    //         $stmt = $this->getConnection()->prepare($sql);
    //         $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    //     } else {
    //         $sql = 'SELECT id AS id_pay, busy FROM adms_pay ORDER BY updated_at DESC LIMIT :limit';
    //         $stmt = $this->getConnection()->prepare($sql);
    //         $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    //     }

    //     $stmt->execute();
    //     return $id ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
    // }

    public function getPaymentsStatus(?int $id = null, int $limit = 100): array
    {
        if ($id) {
            $sql = 'SELECT p.id AS id_pay, p.busy as busy, u.name AS name_user_temp
                FROM adms_pay p
                LEFT JOIN adms_users u ON u.id = p.user_temp
                WHERE p.id = :id
                LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT p.id AS id_pay, p.busy, u.name AS name_user_temp
                FROM adms_pay p
                LEFT JOIN adms_users u ON u.id = p.user_temp
                ORDER BY p.updated_at DESC
                LIMIT :limit';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $id ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function getBusy(int $id): array|null
    {
        $sql = 'SELECT id_pay, busy, user_temp FROM payments WHERE id_pay = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retorna array se encontrou, ou null se não encontrou
        return $result ?: null;
    }


    /**
     * Recupera a quantidade total de Contas a Pagar para paginação.
     *
     * @param array $filtros Filtros para a consulta.
     * @return int Quantidade total de Contas a Pagar no banco de dados.
     */
    // public function getAmountPayments(array $filtros = []): int
    // {
    //     $where = [];
    //     $params = [];
    //     if (!empty($filtros['num_doc'])) {
    //         $where[] = 'num_doc LIKE :num_doc';
    //         $params[':num_doc'] = '%' . $filtros['num_doc'] . '%';
    //     }
    //     if (!empty($filtros['num_nota'])) {
    //         $where[] = 'num_nota LIKE :num_nota';
    //         $params[':num_nota'] = '%' . $filtros['num_nota'] . '%';
    //     }
    //     if (!empty($filtros['card_code_fornecedor'])) {
    //         $where[] = 'card_code_fornecedor LIKE :card_code_fornecedor';
    //         $params[':card_code_fornecedor'] = '%' . $filtros['card_code_fornecedor'] . '%';
    //     }
    //     if (!empty($filtros['fornecedor'])) {
    //         $where[] = 'partner_id IN (SELECT id FROM adms_supplier WHERE card_name LIKE :fornecedor)';
    //         $params[':fornecedor'] = '%' . $filtros['fornecedor'] . '%';
    //     }
    //     if (!empty($filtros['vencimento_hoje'])) {
    //         $where[] = 'DATE(due_date) = CURDATE()';
    //     }
    //     if (!empty($filtros['data_inicial'])) {
    //         $where[] = 'DATE(due_date) >= :data_inicial';
    //         $params[':data_inicial'] = $filtros['data_inicial'];
    //     }
    //     if (!empty($filtros['data_final'])) {
    //         $where[] = 'DATE(due_date) <= :data_final';
    //         $params[':data_final'] = $filtros['data_final'];
    //     }
    //     if (!empty($filtros['status'])) {
    //         if ($filtros['status'] === 'pendente') {
    //             $where[] = 'paid = 0';
    //         } elseif ($filtros['status'] === 'pago') {
    //             $where[] = 'paid = 1';
    //         } elseif ($filtros['status'] === 'vencidos') {
    //             $where[] = 'paid = 0 AND due_date < CURDATE()';
    //         }
    //     }
    //     $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    //     $sql = 'SELECT COUNT(id) AS amount_records FROM adms_pay ' . $whereSql;
    //     $stmt = $this->getConnection()->prepare($sql);
    //     foreach ($params as $key => $value) {
    //         $stmt->bindValue($key, $value, PDO::PARAM_STR);
    //     }
    //     $stmt->execute();
    //     return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    // }
    public function getAmountPayments(array $filtros = []): int
    {
        $where = [];
        $params = [];
        $dataType = $filtros['data_type'] ?? 'due_date';  // ✅ Aqui!

        if (!empty($filtros['num_doc'])) {
            $where[] = 'ap.num_doc LIKE :num_doc';
            $params[':num_doc'] = '%' . $filtros['num_doc'] . '%';
        }
        if (!empty($filtros['num_nota'])) {
            $where[] = 'ap.num_nota LIKE :num_nota';
            $params[':num_nota'] = '%' . $filtros['num_nota'] . '%';
        }
        if (!empty($filtros['card_code_fornecedor'])) {
            $where[] = 'ap.card_code_fornecedor LIKE :card_code_fornecedor';
            $params[':card_code_fornecedor'] = '%' . $filtros['card_code_fornecedor'] . '%';
        }
        if (!empty($filtros['fornecedor'])) {
            $where[] = 'ap.partner_id IN (SELECT id FROM adms_supplier WHERE card_name LIKE :fornecedor)';
            $params[':fornecedor'] = '%' . $filtros['fornecedor'] . '%';
        }
        if (!empty($filtros['vencimento_hoje'])) {
            $dataType = $filtros['data_type'] ?? 'due_date';
            $where[] = 'DATE(ap.' . $dataType . ') = CURDATE()';
        }
        if (!empty($filtros['data_inicial'])) {
            $where[] = 'DATE(ap.' . $dataType . ') >= :data_inicial';
            $params[':data_inicial'] = $filtros['data_inicial'];
        }
        if (!empty($filtros['data_final'])) {
            $where[] = 'DATE(ap.' . $dataType . ') <= :data_final';
            $params[':data_final'] = $filtros['data_final'];
        }
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'pendente') {
                $where[] = 'ap.paid = 0';
            } elseif ($filtros['status'] === 'pago') {
                $where[] = 'ap.paid = 1';
            } elseif ($filtros['status'] === 'vencidos') {
                $where[] = 'ap.paid = 0 AND ap.due_date < CURDATE()';
            }
        }
        if ($dataType === 'issue_date') {
            $where[] = 'ap.issue_date IS NOT NULL';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT COUNT(id) AS amount_records FROM adms_pay ap ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }


    /**
     * Recupera uma Conta a Pagar específica pelo ID.
     *
     * @param int $id ID da Conta a Pagar.
     * @return array|bool Dados da Conta a Pagar ou `false` se não encontrada.
     */
    public function getPay(int $id): array|bool
    {

        $sql = 'SELECT 
                        ap.id AS id_pay, 
                        ap.num_doc, 
                        ap.num_nota, 
                        ap.card_code_fornecedor, 
                        ap.description, 
                        ap.file, 
                        ap.paid, 
                        ap.original_value, 
                        ap.doc_date, 
                        ap.due_date, 
                        ap.expected_date, 
                        ap.created_at, 
                        ap.updated_at,
                        sup.card_name, 
                        au.name AS name_user,
                        af.name AS name_freq,
                        af.days,
                        acc.name AS name_cc,
                        aap.name AS name_aap,
                        ap.installment_number, 
                        ap.issue_date,
                        (ap.original_value - COALESCE(SUM(am.movement_value), 0)) AS value
                FROM adms_pay ap 
                    LEFT JOIN adms_users au ON au.id = ap.user_launch_id
                    LEFT JOIN adms_frequency af on af.id = ap.frequency_id
                    LEFT JOIN adms_supplier sup on sup.id = ap.partner_id
                    LEFT JOIN adms_cost_center acc on acc.id = ap.cost_center_id
                    LEFT JOIN adms_accounts_plan aap on aap.id = ap.account_id
                    LEFT JOIN adms_movements am on am.movement_id = ap.id
                WHERE ap.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    /**
     * Cria uma nova conta a pagar
     * 
     * @param array $data Dados da conta
     * @return bool|int ID da conta criada ou false em caso de erro
     */
    public function createPay(array $data): bool|int
    {
        $valor = $this->normalizarValor($data['value']);

        // Garantir que todos os campos obrigatórios estejam preenchidos
        $partner_id = $data['partner_id'] ?? null;
        // Buscar o card_code_fornecedor se não vier preenchido
        $card_code_fornecedor = $data['card_code_fornecedor'] ?? null;
        if (!$card_code_fornecedor && $partner_id) {
            $sqlFornecedor = 'SELECT card_code FROM adms_supplier WHERE id = :id LIMIT 1';
            $stmtFornecedor = $this->getConnection()->prepare($sqlFornecedor);
            $stmtFornecedor->bindValue(':id', $partner_id, PDO::PARAM_INT);
            $stmtFornecedor->execute();
            $card_code_fornecedor = $stmtFornecedor->fetchColumn() ?: '';
        }
        $doc_date = $data['doc_date'] ?? date('Y-m-d');
        $installment_number = $data['installment_number'] ?? 1;
        $issue_date = $data['issue_date'] ?? null;

        $sql = "INSERT INTO adms_pay (
            num_doc,
            num_nota,
            card_code_fornecedor,
            description,
            original_value,
            doc_date,
            due_date,
            expected_date,
            partner_id,
            frequency_id,
            cost_center_id,
            account_id,
            installment_number,
            issue_date,
            user_launch_id,
            created_at
        ) VALUES (
            :num_doc,
            :num_nota,
            :card_code_fornecedor,
            :description,
            :original_value,
            :doc_date,
            :due_date,
            :expected_date,
            :partner_id,
            :frequency_id,
            :cost_center_id,
            :account_id,
            :installment_number,
            :issue_date,
            :user_launch_id,
            NOW()
        )";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':num_doc', $data['num_doc']);
        $stmt->bindValue(':num_nota', $data['num_nota'] ?? null);
        $stmt->bindValue(':card_code_fornecedor', $card_code_fornecedor);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':original_value', $valor); // Garante que original_value = value na criação
        $stmt->bindValue(':doc_date', $doc_date);
        $stmt->bindValue(':due_date', $data['due_date']);
        $stmt->bindValue(':expected_date', $data['expected_date'] ?? null);
        $stmt->bindValue(':partner_id', $partner_id);
        $stmt->bindValue(':frequency_id', $data['frequency_id'] ?? 1);
        $stmt->bindValue(':cost_center_id', $data['cost_center_id'] ?? 0);
        $stmt->bindValue(':account_id', $data['account_id'] ?? 0);
        $stmt->bindValue(':installment_number', $installment_number);
        $stmt->bindValue(':issue_date', $issue_date);
        $stmt->bindValue(':user_launch_id', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $paymentId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($paymentId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'num_doc' => $data['num_doc'],
                    'num_nota' => $data['num_nota'] ?? null,
                    'card_code_fornecedor' => $card_code_fornecedor,
                    'description' => $data['description'],
                    'original_value' => $valor,
                    'doc_date' => $doc_date,
                    'due_date' => $data['due_date'],
                    'expected_date' => $data['expected_date'] ?? null,
                    'partner_id' => $partner_id,
                    'frequency_id' => $data['frequency_id'] ?? 1,
                    'cost_center_id' => $data['cost_center_id'] ?? 0,
                    'account_id' => $data['account_id'] ?? 0,
                    'installment_number' => $installment_number,
                    'issue_date' => $issue_date,
                    'user_launch_id' => $_SESSION['user_id']
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_pay',
                    $paymentId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $paymentId;
        }

        return false;
    }

    public function validaPayment(string $card_code): array|bool
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

    public function importPayment(array $data): bool|int
    {
        // Logar dados de importação
        $logDir = __DIR__ . '/../../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/payments_debug.log';
        $mensagem = date('Y-m-d H:i:s') . " | IMPORT: " . print_r($data, true) . "\n";
        file_put_contents($logFile, $mensagem, FILE_APPEND);
        // Normalização dos campos para evitar duplicidade por formato/tipo
        $num_doc = trim((string)$data['num_doc']);
        $card_code_fornecedor = trim((string)$data['card_code_fornecedor']);
        $installment_number = (int)preg_replace('/[^0-9]/', '', $data['installment_number']);
        $sql = 'SELECT COUNT(*) FROM adms_pay WHERE TRIM(num_doc) = :num_doc AND TRIM(card_code_fornecedor) = :card_code_fornecedor AND installment_number = :installment_number';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $num_doc);
        $stmt->bindParam(':card_code_fornecedor', $card_code_fornecedor);
        $stmt->bindParam(':installment_number', $installment_number, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            return false; // Já existe, não insere
        }
        try {
            // 1. Verificar se o fornecedor existe pelo card_code_fornecedor
            $supplierRepo = new \App\adms\Models\Repository\SupplierRepository();
            $supplier = null;
            if (!empty($data['card_code_fornecedor'])) {
                $sql = 'SELECT id FROM adms_supplier WHERE card_code = :card_code LIMIT 1';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':card_code', $data['card_code_fornecedor'], PDO::PARAM_STR);
                $stmt->execute();
                $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            // 2. Se não existir, cadastrar fornecedor básico
            if (!$supplier && !empty($data['card_code_fornecedor'])) {
                $nomeFornecedor = $data['description'] ?? 'Fornecedor Importado';
                $fornecedor = [
                    'card_code' => $data['card_code_fornecedor'],
                    'card_name' => $nomeFornecedor,
                    'type_person' => '',
                    'doc' => '',
                    'phone' => '',
                    'email' => '',
                    'address' => '',
                    'description' => '',
                    'active' => 1,
                    'date_birth' => null
                ];
                $partner_id = $supplierRepo->createSupplier($fornecedor);
            } else {
                $partner_id = $supplier['id'] ?? null;
            }

            // 3. Inserir na adms_pay com de/para e valores padrão
            $sql = 'INSERT INTO adms_pay (
                description, num_doc, num_nota, file, paid, partner_id, card_code_fornecedor, cost_center_id, user_launch_id, frequency_id, account_id, busy, user_temp, original_value, doc_date, due_date, expected_date, installment_number, issue_date, created_at, updated_at
            ) VALUES (
                :description, :num_doc, :num_nota, :file, :paid, :partner_id, :card_code_fornecedor, :cost_center_id, :user_launch_id, :frequency_id, :account_id, :busy, :user_temp, :original_value, :doc_date, :due_date, :expected_date, :installment_number, :issue_date, :created_at, :updated_at
            )';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':num_doc', $data['num_doc'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':num_nota', null, PDO::PARAM_NULL);
            $stmt->bindValue(':file', null, PDO::PARAM_NULL);
            $stmt->bindValue(':paid', 0, PDO::PARAM_INT);
            $stmt->bindValue(':partner_id', $partner_id, PDO::PARAM_INT);
            $stmt->bindValue(':card_code_fornecedor', $data['card_code_fornecedor'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':cost_center_id', 0, PDO::PARAM_INT);
            $stmt->bindValue(':user_launch_id', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':frequency_id', 1, PDO::PARAM_INT);
            $stmt->bindValue(':account_id', 0, PDO::PARAM_INT);
            $stmt->bindValue(':busy', 0, PDO::PARAM_INT);
            $stmt->bindValue(':user_temp', null, PDO::PARAM_NULL);
            $original_value = isset($data['original_value']) ? $this->normalizarValor($data['original_value']) : '0.00';
            $stmt->bindParam(':original_value', $original_value, PDO::PARAM_STR);
            $stmt->bindValue(':doc_date', $data['doc_date'] ?? date("Y-m-d H:i:s"), PDO::PARAM_STR);
            $stmt->bindValue(':due_date', $data['due_date'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':expected_date', !empty($data['expected_date']) ? date('Y-m-d', strtotime($data['expected_date'])) : null, PDO::PARAM_STR);
            $stmt->bindValue(':installment_number', $data['installment_number'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':issue_date', !empty($data['issue_date']) ? date('Y-m-d', strtotime($data['issue_date'])) : null, PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':updated_at', null, PDO::PARAM_NULL);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Conta não cadastrada.", ['description' => $data['description'] ?? '', 'error' => $e->getMessage()]);
            return false;
        }
    }


    /**
     * Atualiza uma conta a pagar
     * 
     * @param array $data Dados da conta
     * @return bool
     */
    public function updatePay(array $data): bool
    {
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getPay($data['id']);
            
            // Atualiza campos replicados em todas as contas do mesmo num_doc e fornecedor
            $sqlReplicar = "UPDATE adms_pay SET 
                num_nota = :num_nota,
                issue_date = :issue_date,
                partner_id = :partner_id,
                card_code_fornecedor = :card_code_fornecedor,
                account_id = :account_id,
                cost_center_id = :cost_center_id,
                updated_at = NOW()
                WHERE num_doc = :num_doc AND card_code_fornecedor = :card_code_fornecedor";

            $stmtReplicar = $conn->prepare($sqlReplicar);
            $stmtReplicar->bindValue(':num_nota', $data['num_nota']);
            $stmtReplicar->bindValue(':issue_date', $data['issue_date']);
            $stmtReplicar->bindValue(':partner_id', $data['partner_id']);
            $stmtReplicar->bindValue(':card_code_fornecedor', $data['card_code_fornecedor']);
            $stmtReplicar->bindValue(':account_id', $data['account_id']);
            $stmtReplicar->bindValue(':cost_center_id', $data['cost_center_id']);
            $stmtReplicar->bindValue(':num_doc', $data['num_doc']);
            $stmtReplicar->bindValue(':card_code_fornecedor', $data['card_code_fornecedor']);
            $stmtReplicar->execute();

            // Atualiza os demais campos apenas na conta selecionada
            $sqlIndividual = "UPDATE adms_pay SET 
                description = :description,
                original_value = :original_value,                
                due_date = :due_date,
                expected_date = :expected_date,
                frequency_id = :frequency_id,
                installment_number = :installment_number,
                updated_at = NOW()
                WHERE id = :id";

            $stmtIndividual = $conn->prepare($sqlIndividual);
            $stmtIndividual->bindValue(':description', $data['description']);
            $stmtIndividual->bindValue(':original_value', $this->normalizarValor($data['original_value']));
            $stmtIndividual->bindValue(':due_date', $data['due_date']);
            $stmtIndividual->bindValue(':expected_date', $data['expected_date']);
            $stmtIndividual->bindValue(':frequency_id', $data['frequency_id']);
            $stmtIndividual->bindValue(':installment_number', $data['installment_number']);
            $stmtIndividual->bindValue(':id', $data['id']);
            $stmtIndividual->execute();

            $conn->commit();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'description' => $data['description'],
                    'original_value' => $this->normalizarValor($data['original_value']),
                    'due_date' => $data['due_date'],
                    'expected_date' => $data['expected_date'],
                    'frequency_id' => $data['frequency_id'],
                    'installment_number' => $data['installment_number'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_pay',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }

            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            GenerateLog::generateLog("error", "Erro ao editar conta em lote.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }


    /**
     * Deleta uma Conta a Pagar pelo ID.
     *
     * @param int $id ID da Conta a Pagar a ser deletada.
     * @return bool `true` se deletado com sucesso ou `false` em caso de erro.
     */
    public function deletePay(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getPay($id);
            
            $sql = 'DELETE FROM adms_pay WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            $result = $stmt->execute();

            // Registrar log de alteração se a exclusão foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                LogAlteracaoService::registrarAlteracao(
                    'adms_pay',
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

    // public function existsNumDocForPartner(string $num_doc, int $partner_id): bool
    // {
    //     $sql = "SELECT COUNT(*) FROM adms_pay WHERE num_doc = :num_doc AND partner_id = :partner_id";


    //     $stmt = $this->getConnection()->prepare($sql);
    //     $stmt->bindParam(':num_doc', $num_doc);
    //     $stmt->bindParam(':partner_id', $partner_id);

    //     $stmt->execute();
    //     return $stmt->fetchColumn() > 0;
    // }

    public function existsNumDocForPartner($numDoc, $partnerId, $ignoreId = null)
    {


        $sql = "SELECT COUNT(*) FROM adms_pay WHERE num_doc = :num_doc AND partner_id = :partner_id";


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

    public function getInstallments(int $id): array|bool
    {

        $sql = 'SELECT 
                ap.id AS id_pay, ap.value, 
                af.name AS name_freq, af.days
                FROM adms_pay ap                     
                    LEFT JOIN adms_frequency af on af.id = ap.frequency_id
                WHERE ap.id = :id LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se já existe uma conta a pagar com os mesmos num_doc, card_code_fornecedor e due_date.
     */
    public function existePayment($num_doc, $card_code_fornecedor, $due_date): bool
    {
        $sql = 'SELECT COUNT(*) FROM adms_pay WHERE num_doc = :num_doc AND card_code_fornecedor = :card_code_fornecedor AND due_date = :due_date';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $num_doc);
        $stmt->bindParam(':card_code_fornecedor', $card_code_fornecedor);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Busca todas as parcelas de um documento para um fornecedor.
     */
    public function buscarParcelasDocumento($num_doc, $card_code_fornecedor)
    {
        $sql = "SELECT * FROM adms_pay WHERE num_doc = :num_doc AND card_code_fornecedor = :card_code_fornecedor ORDER BY installment_number";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $num_doc);
        $stmt->bindParam(':card_code_fornecedor', $card_code_fornecedor);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Deleta uma parcela específica de um documento.
     */
    public function deletarParcela($num_doc, $card_code_fornecedor, $installment_number)
    {
        $sql = "DELETE FROM adms_pay WHERE num_doc = :num_doc AND card_code_fornecedor = :card_code_fornecedor AND installment_number = :installment_number";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':num_doc', $num_doc);
        $stmt->bindParam(':card_code_fornecedor', $card_code_fornecedor);
        $stmt->bindParam(':installment_number', $installment_number);
        return $stmt->execute();
    }

    /**
     * Atualiza uma parcela específica de um documento.
     */
    public function atualizarPayment($dados)
    {
        // Logar dados de atualização
        $logDir = __DIR__ . '/../../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/payments_debug.log';
        $mensagem = date('Y-m-d H:i:s') . " | UPDATE: " . print_r($dados, true) . "\n";
        file_put_contents($logFile, $mensagem, FILE_APPEND);
        $sql = "UPDATE adms_pay SET 
                    due_date = :due_date,
                    expected_date = :expected_date,
                    value = :value,
                    original_value = :value,
                    issue_date = :issue_date,
                    updated_at = NOW()
                WHERE num_doc = :num_doc AND card_code_fornecedor = :card_code_fornecedor AND installment_number = :installment_number";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':due_date', $dados['due_date']);
        $stmt->bindParam(':expected_date', $dados['expected_date']);
        $stmt->bindParam(':value', $dados['value']);
        $stmt->bindParam(':issue_date', $dados['issue_date']);
        $stmt->bindParam(':num_doc', $dados['num_doc']);
        $stmt->bindParam(':card_code_fornecedor', $dados['card_code_fornecedor']);
        $stmt->bindParam(':installment_number', $dados['installment_number']);
        return $stmt->execute();
    }

    /**
     * Retorna a próxima data de vencimento (due_date) após a data atual.
     */
    public function getProximaDataVencimento($dataType = 'due_date')
    {
        $campo = in_array($dataType, ['due_date', 'issue_date']) ? $dataType : 'due_date';
        $sql = "SELECT $campo FROM adms_pay WHERE $campo > CURDATE() ORDER BY $campo ASC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? date('Y-m-d', strtotime($result[$campo])) : null;
    }

    /**
     * Retorna o total a pagar de todos os registros filtrados.
     *
     * @param array $filtros Filtros para a consulta.
     * @return float Total a pagar.
     */
    public function getTotalToPay(array $filtros = []): float
    {
        $where = [];
        $params = [];
        $dataType = $filtros['data_type'] ?? 'due_date'; // Adicionado para usar o campo correto
        if (!empty($filtros['num_doc'])) {
            $where[] = 'num_doc LIKE :num_doc';
            $params[':num_doc'] = '%' . $filtros['num_doc'] . '%';
        }
        if (!empty($filtros['num_nota'])) {
            $where[] = 'num_nota LIKE :num_nota';
            $params[':num_nota'] = '%' . $filtros['num_nota'] . '%';
        }
        if (!empty($filtros['card_code_fornecedor'])) {
            $where[] = 'card_code_fornecedor LIKE :card_code_fornecedor';
            $params[':card_code_fornecedor'] = '%' . $filtros['card_code_fornecedor'] . '%';
        }
        if (!empty($filtros['fornecedor'])) {
            $where[] = 'partner_id IN (SELECT id FROM adms_supplier WHERE card_name LIKE :fornecedor)';
            $params[':fornecedor'] = '%' . $filtros['fornecedor'] . '%';
        }

        if (!empty($filtros['vencimento_hoje'])) {
            $dataType = $filtros['data_type'] ?? 'due_date';
            $where[] = 'DATE(ap.' . $dataType . ') = CURDATE()';
        }
        if (!empty($filtros['data_inicial'])) {
            $where[] = 'DATE(ap.' . $dataType . ') >= :data_inicial';
            $params[':data_inicial'] = $filtros['data_inicial'];
        }
        if (!empty($filtros['data_final'])) {
            $where[] = 'DATE(ap.' . $dataType . ') <= :data_final';
            $params[':data_final'] = $filtros['data_final'];
        }
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'pendente') {
                $where[] = 'paid = 0';
            } elseif ($filtros['status'] === 'pago') {
                $where[] = 'paid = 1';
            } elseif ($filtros['status'] === 'vencidos') {
                $where[] = 'paid = 0 AND due_date < CURDATE()';
            }
        }
        if ($dataType === 'issue_date') {
            $where[] = 'ap.issue_date IS NOT NULL';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        // Soma apenas o saldo a pagar (considerando pagamentos parciais e descontos)
        $sql = 'SELECT ap.id, ap.original_value, (
                    SELECT COALESCE(SUM(movement_value),0) FROM adms_movements WHERE movement_id = ap.id
                ) as total_pago,
                (
                    SELECT COALESCE(SUM(discount_value),0) FROM adms_movements WHERE movement_id = ap.id
                ) as total_desconto
                FROM adms_pay ap ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $saldo = $row['original_value'] - $row['total_pago'] - $row['total_desconto'];
            if ($saldo < 0) $saldo = 0;
            $total += $saldo;
        }
        return (float) $total;
    }

    /**
     * Atualiza o status de bloqueio de uma conta
     * 
     * @param int $id ID da conta
     * @param int $userId ID do usuário que está bloqueando
     * @return bool
     */
    public function updateBusy(int $id, int $userId): bool
    {
        $sql = "UPDATE adms_pay SET busy = 1, user_temp = :user_id, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Limpa o status de bloqueio de uma conta
     * 
     * @param int $id ID da conta
     * @return bool
     */
    public function clearBusy(int $id): bool
    {
        $sql = "UPDATE adms_pay SET busy = 0, user_temp = NULL, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obtém as contas bloqueadas por um usuário
     * 
     * @param int $userId ID do usuário
     * @return array
     */
    public function getUserTemp(int $userId): array
    {
        $sql = "SELECT id FROM adms_pay WHERE user_temp = :user_id AND busy = 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Limpa todas as contas bloqueadas por um usuário
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    public function clearUser(int $userId): bool
    {
        $sql = "UPDATE adms_pay SET busy = 0, user_temp = NULL, updated_at = NOW() WHERE user_temp = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Cria um movimento financeiro
     * 
     * @param array $dataBD Dados atuais da conta
     * @param array $data Dados do formulário
     * @return bool
     */
    public function createMovement(array $dataBD, array $data): bool
    {
        $sql = "INSERT INTO adms_movements (
        type,
         movement, description,
            movement_id, 
            movement_value, 
            created_at, 
            bank_id, 
            method_id, 
            user_id,
            discount_value,
            fine_value,
            interest_value
        ) VALUES (
            :type,
            :movement,
            :description,
            :movement_id,
            :movement_value,
            NOW(),
            :bank_id,
            :method_id,
            :user_id,
            :discount_value,
            :fine_value,
            :interest_value
        )";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':type', 'Saída', PDO::PARAM_STR);
        $stmt->bindValue(':movement', 'Conta à Pagar', PDO::PARAM_STR);
        $stmt->bindParam(':description',  $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':movement_id', $data['id_pay'], PDO::PARAM_INT);
        $stmt->bindValue(':movement_value', $this->normalizarValor($data['subtotal'] ?? $data['value']), PDO::PARAM_STR);
        $stmt->bindValue(':bank_id', $data['bank_id'], PDO::PARAM_INT);
        $stmt->bindValue(':method_id', $data['pay_method_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':discount_value', $this->normalizarValor($data['discount_value'] ?? 0), PDO::PARAM_STR);
        $stmt->bindValue(':fine_value', $this->normalizarValor($data['fine_value'] ?? 0), PDO::PARAM_STR);
        $stmt->bindValue(':interest_value', $this->normalizarValor($data['interest'] ?? 0), PDO::PARAM_STR);
        $result = $stmt->execute();
        $lastId = $this->getConnection()->lastInsertId();
        // Log de alteração
        \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
            'adms_movements',
            $lastId,
            $_SESSION['user_id'] ?? 0,
            'INSERT',
            [],
            [
                'type' => 'Saída',
                'movement' => 'Conta à Pagar',
                'description' => $data['description'],
                'movement_id' => $data['id_pay'],
                'movement_value' => $this->normalizarValor($data['subtotal'] ?? $data['value']),
                'bank_id' => $data['bank_id'],
                'method_id' => $data['pay_method_id'],
                'user_id' => $_SESSION['user_id'],
                'discount_value' => $this->normalizarValor($data['discount_value'] ?? 0),
                'fine_value' => $this->normalizarValor($data['fine_value'] ?? 0),
                'interest_value' => $this->normalizarValor($data['interest'] ?? 0),
                'created_at' => date("Y-m-d H:i:s")
            ]
        );
        // Após inserir, atualizar o campo paid em adms_pay
        if ($result) {
            $id_pay = $data['id_pay'];
            // Soma movement_value e discount_value
            $sqlSoma = 'SELECT COALESCE(SUM(movement_value),0) as total_pago, COALESCE(SUM(discount_value),0) as total_desconto FROM adms_movements WHERE movement_id = :id_pay';
            $stmtSoma = $this->getConnection()->prepare($sqlSoma);
            $stmtSoma->bindValue(':id_pay', $id_pay, PDO::PARAM_INT);
            $stmtSoma->execute();
            $somas = $stmtSoma->fetch(PDO::FETCH_ASSOC);
            $totalPago = (float)($somas['total_pago'] ?? 0);
            $totalDesconto = (float)($somas['total_desconto'] ?? 0);
            // Buscar o valor original
            $sqlOriginal = 'SELECT original_value FROM adms_pay WHERE id = :id_pay';
            $stmtOriginal = $this->getConnection()->prepare($sqlOriginal);
            $stmtOriginal->bindValue(':id_pay', $id_pay, PDO::PARAM_INT);
            $stmtOriginal->execute();
            $originalValue = (float)($stmtOriginal->fetchColumn() ?? 0);
            $paid = ($totalPago + $totalDesconto) >= $originalValue ? 1 : 0;
            $sqlUpdatePaid = 'UPDATE adms_pay SET paid = :paid WHERE id = :id_pay';
            $stmtUpdatePaid = $this->getConnection()->prepare($sqlUpdatePaid);
            $stmtUpdatePaid->bindValue(':paid', $paid, PDO::PARAM_INT);
            $stmtUpdatePaid->bindValue(':id_pay', $id_pay, PDO::PARAM_INT);
            $stmtUpdatePaid->execute();
        }
        return $result;
    }

    // /**
    //  * Cria um valor parcial de pagamento
    //  * 
    //  * @param array $dataBD Dados atuais da conta
    //  * @param array $data Dados do formulário
    //  * @return bool
    //  */
    // public function createPartialValue(array $dataBD, array $data): bool
    // {
    //     $sql = "INSERT INTO adms_partial_value (
    //         account_id,
    //         partial_value,
    //         type,
    //         user_id,
    //         created_at
    //     ) VALUES (
    //         :account_id,
    //         :partial_value,
    //         :type,
    //         :user_id,
    //         NOW()
    //     )";

    //     $stmt = $this->getConnection()->prepare($sql);
    //     $stmt->bindValue(':account_id', $data['id_pay'], PDO::PARAM_INT);
    //     $stmt->bindValue(':partial_value', $this->normalizarValor($data['value']), PDO::PARAM_STR);
    //     $stmt->bindValue(':type', 'P', PDO::PARAM_STR);
    //     $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

    //     return $stmt->execute();
    // }

    // /**
    //  * Atualiza o valor residual da conta
    //  * 
    //  * @param array $dataBD Dados atuais da conta
    //  * @param array $data Dados do formulário
    //  * @return bool
    //  */
    // public function updatePayResidue(array $dataBD, array $data): bool
    // {
    //     $valorAtual = (float)$dataBD['value'];
    //     $valorPago = $this->normalizarValor($data['value']);
    //     $novoValor = $valorAtual - $valorPago;

    //     $sql = "UPDATE adms_pay SET 
    //         value = :value,
    //         updated_at = NOW()
    //         WHERE id = :id";

    //     $stmt = $this->getConnection()->prepare($sql);
    //     $stmt->bindValue(':value', $novoValor, PDO::PARAM_STR);
    //     $stmt->bindValue(':id', $data['id_pay'], PDO::PARAM_INT);

    //     return $stmt->execute();
    // }
}
