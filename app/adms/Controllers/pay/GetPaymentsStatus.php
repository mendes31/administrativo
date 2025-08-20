<?php

namespace App\adms\Controllers\pay;

use App\adms\Models\Repository\PaymentsRepository;

class GetPaymentsStatus
// {
//     public function index(): void
//     {
//         header('Content-Type: application/json');

//         $repo = new PaymentsRepository();
//         $dados = $repo->getPaymentsStatus(); // Esse é o novo método leve que criamos

//         echo json_encode($dados);
//     }
// }
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $repo = new PaymentsRepository();
            $dados = $repo->getPaymentsStatus();

            // Verifica se o retorno é um array
            if (!is_array($dados)) {
                throw new \Exception("Erro 003: Retorno inesperado do servidor.");
            }

            echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            // Retorna erro em formato JSON
            http_response_code(500); // Erro interno (opcional)
            echo json_encode([
                "erro" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
