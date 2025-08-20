<?php

namespace App\adms\Controllers\receive;

use App\adms\Models\Repository\ReceiptsRepository;

class ClearBusyReceive
{
    public function index(int $id): void
    {

        
        if (!(int) $id) {
            $_SESSION['error'] = "ID inválido para limpar bloqueio!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        $receiveRepo = new ReceiptsRepository();
        $receiveRepo->clearBusy((int) $id);

        $_SESSION['success'] = "Bloqueio liberado com sucesso.";
        header("Location: {$_ENV['URL_ADM']}list-receipts"); // ou volte pra listagem
    }
}
