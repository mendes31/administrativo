<?php

namespace App\adms\Controllers;

use App\adms\Models\Repository\PaymentsRepository;

class CheckBusy
{
    public function index(): void
    {
        // Filtra o ID da query string (GET)
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }

        $payRepo = new PaymentsRepository();
        $result = $payRepo->getBusy($id);

        header('Content-Type: application/json');

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Registro não encontrado']);
        }
    }
}
