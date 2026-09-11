<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstGheColaboradoresRepository;
use App\adms\Models\Repository\SstGheTreinamentosRepository;

class SstSaveGheRelacionamentos
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }

        $gheId = (int) ($_POST['adms_sst_ghe_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'sst-view-ghe/' . $gheId;
        $secao = (string) ($_POST['secao'] ?? '');

        if ($gheId <= 0 || !CSRFHelper::validateCSRFToken('sst_ghe_relacionamentos', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        if ($secao === 'colaboradores') {
            $userIds = is_array($_POST['colaboradores'] ?? null) ? array_map('intval', $_POST['colaboradores']) : [];
            (new SstGheColaboradoresRepository())->syncColaboradoresForGhe($gheId, $userIds);
            $_SESSION['msg'] = 'Colaboradores do GHE atualizados. Execute sincronizar vínculos de treinamento se necessário.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $redirect . '#tab-colaboradores');
            exit;
        }

        if ($secao === 'treinamentos') {
            $treinamentoIds = is_array($_POST['treinamentos'] ?? null) ? array_map('intval', $_POST['treinamentos']) : [];
            $obrigatorioPost = is_array($_POST['treinamentos_obrigatorio'] ?? null) ? $_POST['treinamentos_obrigatorio'] : [];
            $validadePost = is_array($_POST['treinamentos_validade'] ?? null) ? $_POST['treinamentos_validade'] : [];
            $map = [];
            foreach ($treinamentoIds as $treinamentoId) {
                if ($treinamentoId > 0) {
                    $validade = trim((string) ($validadePost[$treinamentoId] ?? ''));
                    $map[$treinamentoId] = [
                        'obrigatorio' => !isset($obrigatorioPost[$treinamentoId . '_opcional']),
                        'validade_meses' => $validade !== '' ? (int) $validade : null,
                    ];
                }
            }
            (new SstGheTreinamentosRepository())->syncTreinamentosForGhe($gheId, $map);
            $_SESSION['msg'] = 'Treinamentos do GHE salvos. A matriz por cargo já considera esses vínculos.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $redirect . '#tab-treinamentos');
            exit;
        }

        $_SESSION['msg'] = 'Seção inválida.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $redirect);
        exit;
    }
}
