<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstGheRepository;

class SstDeleteGhe
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !CSRFHelper::validateCSRFToken('form_delete_sst_ghe', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }
        $ok = (new SstGheRepository())->delete($id);
        $_SESSION['msg'] = $ok ? 'GHE excluído.' : 'Não foi possível excluir.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
        exit;
    }
}
