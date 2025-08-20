<?php

namespace App\adms\Controllers\movement;

use App\adms\Models\Repository\FinancialMovementsRepository;
use App\adms\Models\Repository\PaymentsRepository;

class DeleteMovement
{
    public function index($id_mov)
    {
        $repo = new FinancialMovementsRepository();
        $movement = $repo->getMovementById($id_mov);
        if (!$movement) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Movimento não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-payments');
            exit;
        }

        $delete = $repo->deleteMovement($id_mov);
        if ($delete) {
            $payRepo = new PaymentsRepository();
            $id_pay = $movement['movement_id'];
            $sqlSoma = 'SELECT COALESCE(SUM(movement_value),0) as total_pago, COALESCE(SUM(discount_value),0) as total_desconto FROM adms_movements WHERE movement_id = :id_pay';
            $stmtSoma = $payRepo->getConnection()->prepare($sqlSoma);
            $stmtSoma->bindValue(':id_pay', $id_pay, \PDO::PARAM_INT);
            $stmtSoma->execute();
            $somas = $stmtSoma->fetch(\PDO::FETCH_ASSOC);
            $totalPago = (float)($somas['total_pago'] ?? 0);
            $totalDesconto = (float)($somas['total_desconto'] ?? 0);
            $sqlOriginal = 'SELECT original_value FROM adms_pay WHERE id = :id_pay';
            $stmtOriginal = $payRepo->getConnection()->prepare($sqlOriginal);
            $stmtOriginal->bindValue(':id_pay', $id_pay, \PDO::PARAM_INT);
            $stmtOriginal->execute();
            $originalValue = (float)($stmtOriginal->fetchColumn() ?? 0);
            $paid = ($totalPago + $totalDesconto) >= $originalValue ? 1 : 0;
            $sqlUpdatePaid = 'UPDATE adms_pay SET paid = :paid WHERE id = :id_pay';
            $stmtUpdatePaid = $payRepo->getConnection()->prepare($sqlUpdatePaid);
            $stmtUpdatePaid->bindValue(':paid', $paid, \PDO::PARAM_INT);
            $stmtUpdatePaid->bindValue(':id_pay', $id_pay, \PDO::PARAM_INT);
            $stmtUpdatePaid->execute();
            $_SESSION['msg'] = '<div class="alert alert-success">Movimento excluído com sucesso!</div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao excluir movimento!</div>';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'view-pay/' . $movement['movement_id']);
        exit;
    }
} 