<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;

class SstManageEquipamentoChecklistItem
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }
        $tipoId = (int) ($_POST['adms_sst_equipamento_tipo_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'sst-view-equipamento-tipo/' . $tipoId;
        if (!$tipoId || !CSRFHelper::validateCSRFToken('sst_equipamento_checklist', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $redirect);
            exit;
        }
        $repo = new SstEquipamentoTiposRepository();
        $action = $_POST['action'] ?? 'add';
        if ($action === 'delete') {
            $repo->deleteChecklistItem((int) ($_POST['item_id'] ?? 0));
            $_SESSION['msg'] = 'Item removido.';
        } elseif ($action === 'update') {
            $repo->updateChecklistItem((int) ($_POST['item_id'] ?? 0), [
                'descricao' => $_POST['descricao'] ?? '',
                'ordem' => (int) ($_POST['ordem'] ?? 0),
                'obrigatorio' => !empty($_POST['obrigatorio']),
                'ativo' => !empty($_POST['ativo']),
            ]);
            $_SESSION['msg'] = 'Item atualizado.';
        } else {
            if (trim((string) ($_POST['descricao'] ?? '')) === '') {
                $_SESSION['msg'] = 'Descrição obrigatória.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $redirect);
                exit;
            }
            $repo->addChecklistItem($tipoId, [
                'descricao' => $_POST['descricao'] ?? '',
                'ordem' => (int) ($_POST['ordem'] ?? 0),
                'obrigatorio' => !empty($_POST['obrigatorio']),
            ]);
            $_SESSION['msg'] = 'Item adicionado.';
        }
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect);
        exit;
    }
}
