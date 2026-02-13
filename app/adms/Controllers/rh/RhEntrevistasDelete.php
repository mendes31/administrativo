<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhEntrevistasRepository;

class RhEntrevistasDelete
{
    public function index(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $repo = new RhEntrevistasRepository();
        $entrevista = $repo->getById($id);
        if (!$entrevista) {
            echo json_encode(['success' => false, 'message' => 'Entrevista não encontrada.']);
            exit;
        }

        if ($repo->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Entrevista excluída com sucesso.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Erro ao excluir entrevista.']);
    }
}
