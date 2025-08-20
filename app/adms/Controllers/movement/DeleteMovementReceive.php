<?php

namespace App\adms\Controllers\movement;

use App\adms\Models\Repository\FinancialMovementsRepository;
use App\adms\Models\Repository\ReceiptsRepository;

class DeleteMovementReceive
{
    public function index($id_mov)
    {
        $repo = new FinancialMovementsRepository();
        $movement = $repo->getMovementReceiveById($id_mov);
        if (!$movement) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Movimento não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-receipts');
            exit;
        }

        $delete = $repo->deleteMovementReceive($id_mov);
        if ($delete) {
            $receiveRepo = new ReceiptsRepository();
            $id_receive = $movement['movement_id'];
            $sqlSoma = 'SELECT COALESCE(SUM(movement_value),0) as total_recebido, COALESCE(SUM(discount_value),0) as total_desconto FROM adms_movements WHERE movement_id = :id_receive';
            $stmtSoma = $receiveRepo->getConnection()->prepare($sqlSoma);
            $stmtSoma->bindValue(':id_receive', $id_receive, \PDO::PARAM_INT);
            $stmtSoma->execute();
            $somas = $stmtSoma->fetch(\PDO::FETCH_ASSOC);
            $totalRecebido = (float)($somas['total_recebido'] ?? 0);
            $totalDesconto = (float)($somas['total_desconto'] ?? 0);
            $sqlOriginal = 'SELECT original_value FROM adms_receive WHERE id = :id_receive';
            $stmtOriginal = $receiveRepo->getConnection()->prepare($sqlOriginal);
            $stmtOriginal->bindValue(':id_receive', $id_receive, \PDO::PARAM_INT);
            $stmtOriginal->execute();
            $originalValue = (float)($stmtOriginal->fetchColumn() ?? 0);
            $paid = ($totalRecebido + $totalDesconto) >= $originalValue ? 1 : 0;
            $sqlUpdatePaid = 'UPDATE adms_receive SET paid = :paid WHERE id = :id_receive';
            $stmtUpdatePaid = $receiveRepo->getConnection()->prepare($sqlUpdatePaid);
            $stmtUpdatePaid->bindValue(':paid', $paid, \PDO::PARAM_INT);
            $stmtUpdatePaid->bindValue(':id_receive', $id_receive, \PDO::PARAM_INT);
            $stmtUpdatePaid->execute();
            $_SESSION['msg'] = '<div class="alert alert-success">Movimento excluído com sucesso!</div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao excluir movimento!</div>';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'view-receive/' . $movement['movement_id']);
        exit;
    }
} 