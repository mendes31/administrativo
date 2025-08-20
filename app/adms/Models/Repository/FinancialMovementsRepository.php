<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class FinancialMovementsRepository extends DbConnection
{
    /**
     * Lista movimentos financeiros com filtros.
     * @param array $filtros
     * @return array
     */
    public function getMovements(array $filtros = []): array
    {
        $where = [];
        $params = [];
        $dataType = $filtros['data_type'] ?? 'created_at';
        if (!empty($filtros['data_inicial'])) {
            $where[] = 'DATE(' . ($dataType === 'issue_date' ? 'ap.issue_date' : 'm.created_at') . ') >= :data_inicial';
            $params[':data_inicial'] = $filtros['data_inicial'];
        }
        if (!empty($filtros['data_final'])) {
            $where[] = 'DATE(' . ($dataType === 'issue_date' ? 'ap.issue_date' : 'm.created_at') . ') <= :data_final';
            $params[':data_final'] = $filtros['data_final'];
        }
        if (!empty($filtros['bank_id'])) {
            $where[] = 'm.bank_id = :bank_id';
            $params[':bank_id'] = $filtros['bank_id'];
        }
        if (!empty($filtros['method_id'])) {
            $where[] = 'm.method_id = :method_id';
            $params[':method_id'] = $filtros['method_id'];
        }
        if (!empty($filtros['type'])) {
            $where[] = 'm.type = :type';
            $params[':type'] = $filtros['type'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT m.*, ap.issue_date, ab.bank_name, apm.name as method_name, au.name as user_name
                FROM adms_movements m
                LEFT JOIN adms_pay ap ON ap.id = m.movement_id
                LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                LEFT JOIN adms_payment_method apm ON apm.id = m.method_id
                LEFT JOIN adms_users au ON au.id = m.user_id
                ' . $whereSql . '
                ORDER BY m.created_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o fluxo de caixa agrupado por dia para o mês/ano e tipo (efetivo/previsto).
     */
    public function getCashFlow($mes, $ano, $tipo = 'efetivo') {
        if ($tipo === 'efetivo') {
            $sql = "SELECT 
                        DAY(m.created_at) as dia,
                        SUM(CASE WHEN m.type = 'Entrada' THEN m.movement_value ELSE 0 END) as receita,
                        SUM(CASE WHEN m.type = 'Saída' THEN m.movement_value ELSE 0 END) as despesa
                    FROM adms_movements m
                    WHERE MONTH(m.created_at) = :mes AND YEAR(m.created_at) = :ano
                    GROUP BY dia
                    ORDER BY dia ASC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
            $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else { // previsto
            // Despesas (adms_pay)
            $sqlPay = "SELECT DAY(p.due_date) as dia, 0 as receita, SUM(p.original_value) as despesa
                       FROM adms_pay p
                       WHERE MONTH(p.due_date) = :mes AND YEAR(p.due_date) = :ano
                       GROUP BY dia";
            $stmtPay = $this->getConnection()->prepare($sqlPay);
            $stmtPay->bindValue(':mes', $mes, PDO::PARAM_INT);
            $stmtPay->bindValue(':ano', $ano, PDO::PARAM_INT);
            $stmtPay->execute();
            $despesas = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
            // Receitas (adms_receive)
            $sqlRec = "SELECT DAY(r.due_date) as dia, SUM(r.original_value) as receita, 0 as despesa
                       FROM adms_receive r
                       WHERE MONTH(r.due_date) = :mes AND YEAR(r.due_date) = :ano
                       GROUP BY dia";
            $stmtRec = $this->getConnection()->prepare($sqlRec);
            $stmtRec->bindValue(':mes', $mes, PDO::PARAM_INT);
            $stmtRec->bindValue(':ano', $ano, PDO::PARAM_INT);
            $stmtRec->execute();
            $receitas = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
            // Unir receitas e despesas por dia
            $dias = [];
            foreach ($despesas as $d) {
                $dias[$d['dia']] = ['dia' => $d['dia'], 'receita' => 0, 'despesa' => (float)$d['despesa']];
            }
            foreach ($receitas as $r) {
                if (isset($dias[$r['dia']])) {
                    $dias[$r['dia']]['receita'] = (float)$r['receita'];
                } else {
                    $dias[$r['dia']] = ['dia' => $r['dia'], 'receita' => (float)$r['receita'], 'despesa' => 0];
                }
            }
            ksort($dias);
            return array_values($dias);
        }
    }

    /**
     * Retorna o saldo acumulado até o último dia do mês/ano anterior.
     */
    public function getSaldoFinal($mes, $ano, $tipo = 'efetivo') {
        // Calcular mês/ano anterior
        $mes = (int)$mes;
        $ano = (int)$ano;
        if ($mes == 1) {
            $mesAnterior = 12;
            $anoAnterior = $ano - 1;
        } else {
            $mesAnterior = $mes - 1;
            $anoAnterior = $ano;
        }
        if ($tipo === 'efetivo') {
            $sql = "SELECT 
                        SUM(CASE WHEN m.type = 'Entrada' THEN m.movement_value ELSE 0 END) -
                        SUM(CASE WHEN m.type = 'Saída' THEN m.movement_value ELSE 0 END) as saldo
                    FROM adms_movements m
                    WHERE (YEAR(m.created_at) < :ano OR (YEAR(m.created_at) = :ano AND MONTH(m.created_at) <= :mes))";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':ano', $anoAnterior, PDO::PARAM_INT);
            $stmt->bindValue(':mes', $mesAnterior, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($row['saldo'] ?? 0);
        } else { // previsto
            // Despesas previstas
            $sqlPay = "SELECT SUM(p.original_value) as total_despesa
                       FROM adms_pay p
                       WHERE (YEAR(p.due_date) < :ano OR (YEAR(p.due_date) = :ano AND MONTH(p.due_date) <= :mes))";
            $stmtPay = $this->getConnection()->prepare($sqlPay);
            $stmtPay->bindValue(':ano', $anoAnterior, PDO::PARAM_INT);
            $stmtPay->bindValue(':mes', $mesAnterior, PDO::PARAM_INT);
            $stmtPay->execute();
            $totalDespesa = (float)($stmtPay->fetchColumn() ?: 0);
            // Receitas previstas
            $sqlRec = "SELECT SUM(r.original_value) as total_receita
                       FROM adms_receive r
                       WHERE (YEAR(r.due_date) < :ano OR (YEAR(r.due_date) = :ano AND MONTH(r.due_date) <= :mes))";
            $stmtRec = $this->getConnection()->prepare($sqlRec);
            $stmtRec->bindValue(':ano', $anoAnterior, PDO::PARAM_INT);
            $stmtRec->bindValue(':mes', $mesAnterior, PDO::PARAM_INT);
            $stmtRec->execute();
            $totalReceita = (float)($stmtRec->fetchColumn() ?: 0);
            return $totalReceita - $totalDespesa;
        }
    }

    /**
     * Busca um movimento financeiro pelo ID.
     */
    public function getMovementById($id_mov)
    {
        $sql = 'SELECT m.*, ap.original_value, ab.bank_name, apm.name as method_name
                FROM adms_movements m
                LEFT JOIN adms_pay ap ON ap.id = m.movement_id
                LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                LEFT JOIN adms_payment_method apm ON apm.id = m.method_id
                WHERE m.id = :id_mov LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id_mov', $id_mov, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um movimento financeiro de recebimento pelo ID.
     */
    public function getMovementReceiveById($id_mov)
    {
        $sql = 'SELECT m.*, ar.original_value, ab.bank_name, apm.name as method_name
                FROM adms_movements m
                LEFT JOIN adms_receive ar ON ar.id = m.movement_id
                LEFT JOIN adms_bank_accounts ab ON ab.id = m.bank_id
                LEFT JOIN adms_payment_method apm ON apm.id = m.method_id
                WHERE m.id = :id_mov LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id_mov', $id_mov, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza um movimento financeiro pelo ID.
     * Somente campos permitidos: valor, forma pgto, banco, usuário e updated_at.
     */
    public function updateMovement($id_mov, $dados)
    {
        // Recuperar dados antigos antes da atualização
        $oldData = $this->getMovementById($id_mov);
        
        $sql = 'UPDATE adms_movements SET method_id = :method, bank_id = :banco, user_id = :user, updated_at = NOW() WHERE id = :id_mov';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':method', $dados['method_id']);
        $stmt->bindValue(':banco', $dados['bank_id']);
        $stmt->bindValue(':user', $dados['user_id']);
        $stmt->bindValue(':id_mov', $id_mov, PDO::PARAM_INT);
        
        $result = $stmt->execute();

        // Registrar log de alteração se a atualização foi bem-sucedida
        if ($result && $oldData) {
            $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
            $newData = array_merge($oldData, [
                'method_id' => $dados['method_id'],
                'bank_id' => $dados['bank_id'],
                'user_id' => $dados['user_id'],
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            
            LogAlteracaoService::registrarAlteracao(
                'adms_movements',
                $id_mov,
                $usuarioId,
                'UPDATE',
                $oldData,
                $newData
            );
        }

        return $result;
    }

    /**
     * Exclui um movimento financeiro pelo ID.
     */
    public function deleteMovementReceive($id_mov)
    {
        // Recuperar dados antes da exclusão
        $oldData = $this->getMovementReceiveById($id_mov);
        
        $sql = 'DELETE FROM adms_movements WHERE id = :id_mov';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id_mov', $id_mov, PDO::PARAM_INT);
        
        $result = $stmt->execute();

        // Registrar log de alteração se a exclusão foi bem-sucedida
        if ($result && $oldData) {
            $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
            LogAlteracaoService::registrarAlteracao(
                'adms_movements',
                $id_mov,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $result;
    }
} 