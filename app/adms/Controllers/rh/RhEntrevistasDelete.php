<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Services\RhPermissionService;

class RhEntrevistasDelete
{
    public function index(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_delete_rh_entrevista', $csrfToken)) {
            echo json_encode([
                'success' => false,
                'message' => 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.',
            ]);
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
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

        if (!RhPermissionService::canManageEntrevista($entrevista)) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir esta entrevista.']);
            exit;
        }

        if ($repo->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Entrevista excluída com sucesso.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Erro ao excluir entrevista.']);
    }
}
