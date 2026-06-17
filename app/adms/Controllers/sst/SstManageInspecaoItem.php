<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstInspecoesRepository;

class SstManageInspecaoItem
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        if (!CSRFHelper::validateCSRFToken('sst_inspecao_itens', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }

        $inspecaoId = (int) ($_POST['adms_sst_inspecao_id'] ?? 0);
        $action = $_POST['action'] ?? 'add';
        $repo = new SstInspecoesRepository();
        $redirect = $_ENV['URL_ADM'] . 'sst-view-inspecao/' . $inspecaoId;

        if ($action === 'delete') {
            $repo->deleteItem((int) ($_POST['item_id'] ?? 0));
            $_SESSION['msg'] = 'Item removido.';
        } elseif ($action === 'update') {
            $repo->updateItem((int) ($_POST['item_id'] ?? 0), [
                'descricao' => $_POST['descricao'] ?? '',
                'classificacao' => $_POST['classificacao'] ?? 'Observação',
                'acao_corretiva' => $_POST['acao_corretiva'] ?? null,
                'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id']) ? (int) $_POST['responsavel_adms_user_id'] : null,
                'prazo' => $_POST['prazo'] ?? null,
                'status' => $_POST['status'] ?? 'Pendente',
            ]);
            $_SESSION['msg'] = 'Item atualizado.';
        } else {
            if (trim((string) ($_POST['descricao'] ?? '')) === '') {
                $_SESSION['msg'] = 'Descrição obrigatória.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $redirect);
                exit;
            }
            $repo->addItem($inspecaoId, [
                'descricao' => $_POST['descricao'] ?? '',
                'classificacao' => $_POST['classificacao'] ?? 'Observação',
                'acao_corretiva' => $_POST['acao_corretiva'] ?? null,
                'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id']) ? (int) $_POST['responsavel_adms_user_id'] : null,
                'prazo' => $_POST['prazo'] ?? null,
                'status' => $_POST['status'] ?? 'Pendente',
            ]);
            $_SESSION['msg'] = 'Item adicionado.';
        }
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect);
        exit;
    }
}
