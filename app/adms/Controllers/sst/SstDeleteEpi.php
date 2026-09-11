<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEpiImagemHelper;
use App\adms\Models\Repository\SstEpisRepository;

class SstDeleteEpi
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = 'Método inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_epis', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $repo = new SstEpisRepository();
        $item = $repo->getById($id);
        if ($repo->delete($id)) {
            SstEpiImagemHelper::deleteStored(is_array($item) ? ($item['imagem'] ?? null) : null);
            $_SESSION['msg'] = 'Registro excluído com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível excluir o registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
        exit;
    }
}