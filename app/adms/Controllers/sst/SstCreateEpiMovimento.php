<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Services\SstEpiEstoqueService;
use App\adms\Views\Services\LoadViewService;

class SstCreateEpiMovimento
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        $this->loadForm();
        (new LoadViewService('adms/Views/sst/epi_estoque/movimento_form', $this->data))->loadView();
    }

    private function loadForm(): void
    {
        $epiId = (int) ($_GET['adms_sst_epi_id'] ?? 0);
        $tipoDefault = in_array($_GET['tipo'] ?? '', ['Entrada', 'Saída', 'Ajuste'], true) ? $_GET['tipo'] : 'Entrada';
        $movRepo = new SstEpiMovimentosRepository();
        $saldo = $epiId > 0 && $movRepo->hasTable() ? $movRepo->getSaldoCalculado($epiId) : null;
        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $this->data['item'] = [
            'adms_sst_epi_id' => $epiId ?: '',
            'tipo_movimento' => $tipoDefault,
            'data_movimento' => date('Y-m-d'),
            'quantidade' => 1,
        ];
        $this->data['saldo_atual'] = $saldo;
        $pageElements = [
            'title_head' => 'Movimentação de estoque EPI',
            'menu' => 'sst-list-epi-movimentos',
            'buttonPermission' => ['SstCreateEpiMovimento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_epi_movimento_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Sessão expirada. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-movimento');
            exit;
        }

        $tipo = (string) ($_POST['tipo_movimento'] ?? '');
        $payload = [
            'adms_sst_epi_id' => (int) ($_POST['adms_sst_epi_id'] ?? 0),
            'tipo_movimento' => $tipo,
            'quantidade' => (int) ($_POST['quantidade'] ?? 1),
            'data_movimento' => trim((string) ($_POST['data_movimento'] ?? '')),
            'documento_ref' => trim((string) ($_POST['documento_ref'] ?? '')),
            'observacoes' => trim((string) ($_POST['observacoes'] ?? '')),
        ];
        if ($tipo === 'Ajuste') {
            $payload['saldo_novo'] = (int) ($_POST['saldo_novo'] ?? -1);
        }

        $result = (new SstEpiEstoqueService())->registrarMovimento($payload);
        if (!$result['ok']) {
            $_SESSION['msg'] = $result['error'] ?? 'Erro ao registrar movimentação.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-movimento');
            exit;
        }

        $_SESSION['msg'] = 'Movimentação registrada com sucesso.';
        $_SESSION['msg_type'] = 'success';
        $epiId = (int) $payload['adms_sst_epi_id'];
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-movimentos?adms_sst_epi_id=' . $epiId);
        exit;
    }
}
