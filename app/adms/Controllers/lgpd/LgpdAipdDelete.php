<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller responsável pela exclusão de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdDelete
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Método não permitido!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        // Validar CSRF token
        if (!CSRFHelper::validateCSRFToken($_POST['csrf_token'] ?? '', 'form_delete_aipd')) {
            $_SESSION['error'] = "Token de segurança inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $id = $_POST['id'] ?? null;
        
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $result = $repo->delete($id);

        if ($result) {
            $_SESSION['success'] = "AIPD excluída com sucesso!";
        } else {
            $_SESSION['error'] = "AIPD não excluída!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
        exit;
    }
}
