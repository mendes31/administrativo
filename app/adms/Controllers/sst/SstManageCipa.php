<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstCipaRepository;

class SstManageCipa
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        if (!CSRFHelper::validateCSRFToken('sst_cipa_manage', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }

        $mandatoId = (int) ($_POST['adms_sst_cipa_mandato_id'] ?? 0);
        $entity = $_POST['entity'] ?? 'membro';
        $action = $_POST['action'] ?? 'add';
        $repo = new SstCipaRepository();
        $redirect = $_ENV['URL_ADM'] . 'sst-view-cipa-mandato/' . $mandatoId;

        if ($entity === 'reuniao') {
            if ($action === 'delete') {
                $repo->deleteReuniao((int) ($_POST['item_id'] ?? 0));
                $_SESSION['msg'] = 'Reunião removida.';
            } else {
                $repo->addReuniao($mandatoId, [
                    'data_reuniao' => $_POST['data_reuniao'] ?? date('Y-m-d'),
                    'tipo' => $_POST['tipo'] ?? 'Ordinária',
                    'pauta' => $_POST['pauta'] ?? null,
                    'ata' => $_POST['ata'] ?? null,
                    'participantes' => $_POST['participantes'] ?? null,
                ]);
                $_SESSION['msg'] = 'Reunião registrada.';
            }
        } else {
            if ($action === 'delete') {
                $repo->deleteMembro((int) ($_POST['item_id'] ?? 0));
                $_SESSION['msg'] = 'Membro removido.';
            } else {
                $userId = (int) ($_POST['adms_user_id'] ?? 0);
                if (!$userId) {
                    $_SESSION['msg'] = 'Selecione o colaborador.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $redirect);
                    exit;
                }
                $repo->addMembro($mandatoId, [
                    'adms_user_id' => $userId,
                    'cargo' => $_POST['cargo'] ?? 'Titular',
                    'ativo' => 1,
                ]);
                $_SESSION['msg'] = 'Membro adicionado.';
            }
        }
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect);
        exit;
    }
}
