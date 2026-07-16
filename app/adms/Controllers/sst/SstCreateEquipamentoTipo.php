<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateEquipamentoTipo
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $pageElements = [
            'title_head' => 'Novo tipo de equipamento - SST',
            'menu' => 'sst-list-equipamento-tipos',
            'buttonPermission' => ['SstCreateEquipamentoTipo'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/tipo_form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_tipo_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }

        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($_POST['prefixo'] ?? ''));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo((string) ($_POST['prefixo'] ?? ''))) {
            $_SESSION['msg'] = 'Prefixo inválido. Informe exatamente 3 caracteres (A–Z / 0–9).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento-tipo');
            exit;
        }

        $repo = new SstEquipamentoTiposRepository();
        if ($repo->prefixoExists($prefixo)) {
            $_SESSION['msg'] = 'Já existe um tipo com o prefixo ' . $prefixo . '.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento-tipo');
            exit;
        }

        $id = $repo->create([
            'nome' => $_POST['nome'] ?? '',
            'codigo' => $_POST['codigo'] ?? '',
            'prefixo' => $prefixo,
            'controla_recarga' => !empty($_POST['controla_recarga']),
            'validade_recarga_meses' => (int) ($_POST['validade_recarga_meses'] ?? 12),
            'descricao' => $_POST['descricao'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
        ]);
        if ($id) {
            $_SESSION['msg'] = 'Tipo cadastrado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-tipo/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar (verifique código ou prefixo duplicado).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento-tipo');
        }
        exit;
    }
}
