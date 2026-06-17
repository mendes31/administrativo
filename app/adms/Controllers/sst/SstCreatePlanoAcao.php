<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstPlanosAcaoRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreatePlanoAcao
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $acidenteId = (int) ($_GET['adms_sst_acidente_id'] ?? 0);
        if ($acidenteId <= 0) {
            $_SESSION['msg'] = 'Acidente não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $acidente = (new SstAcidentesRepository())->getById($acidenteId);
        if (!$acidente) {
            $_SESSION['msg'] = 'Acidente não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $this->data['acidente'] = $acidente;
        $this->data['item'] = ['adms_sst_acidente_id' => $acidenteId, 'status' => 'Pendente'];
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Novo plano de ação - SST',
            'menu' => 'sst-list-acidentes',
            'buttonPermission' => ['SstCreatePlanoAcao'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/planos_acao/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_planos_acao_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $acidenteId = (int) ($_POST['adms_sst_acidente_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($acidenteId <= 0 || $titulo === '') {
            $_SESSION['msg'] = 'Preencha os campos obrigatórios.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-plano-acao?adms_sst_acidente_id=' . $acidenteId);
            exit;
        }

        $data = [
            'adms_sst_acidente_id' => $acidenteId,
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? null,
            'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id']) ? (int) $_POST['responsavel_adms_user_id'] : null,
            'prazo' => $_POST['prazo'] ?? null,
            'data_conclusao' => $_POST['data_conclusao'] ?? null,
            'status' => $_POST['status'] ?? 'Pendente',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];

        $repo = new SstPlanosAcaoRepository();
        $newId = $repo->create($data);
        if ($newId) {
            $_SESSION['msg'] = 'Plano de ação cadastrado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-acidente/' . $acidenteId);
            exit;
        }

        $_SESSION['msg'] = 'Não foi possível salvar o plano de ação.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-plano-acao?adms_sst_acidente_id=' . $acidenteId);
        exit;
    }
}
