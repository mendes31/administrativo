<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstCipaRepository;

class SstDeleteCipaMandato
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_cipa', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        (new SstCipaRepository())->deleteMandato($id);
        $_SESSION['msg'] = 'Mandato excluído.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
        exit;
    }
}
