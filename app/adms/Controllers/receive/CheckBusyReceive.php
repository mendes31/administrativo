<?php

namespace App\adms\Controllers\receive;

use App\adms\Models\Repository\ReceiptsRepository;

class CheckBusyReceive
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

        $receiveRepo = new ReceiptsRepository();
        $result = $receiveRepo->getBusy($id);

        header('Content-Type: application/json');

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Registro não encontrado']);
        }
    }
}
