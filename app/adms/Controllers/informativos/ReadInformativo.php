<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;

class ReadInformativo
{
    public function index(string $id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Aceitar chamadas mesmo sem cabeçalho X-Requested-With (alguns ambientes não o encaminham)

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        $informativoId = (int)($id ?? ($_POST['informativo_id'] ?? ($_GET['informativo_id'] ?? 0)));
        if ($informativoId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        try {
            $repo = new InformativosRepository();
            $repo->upsertRead($informativoId, (int)$_SESSION['user_id']);
            $read = $repo->getReadByUser($informativoId, (int)$_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'read' => (bool)($read['read_at'] ?? false),
                'acknowledged' => (bool)($read['acknowledged'] ?? false),
                'ack_at' => $read['ack_at'] ?? null,
            ]);
        } catch (\Throwable $t) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar leitura']);
        }
    }
}


