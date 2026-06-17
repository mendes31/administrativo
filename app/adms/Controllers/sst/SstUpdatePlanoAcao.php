<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstPlanosAcaoRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdatePlanoAcao
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update();
            return;
        }

        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $repo = new SstPlanosAcaoRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Plano de ação não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $acidenteId = (int) $this->data['item']['adms_sst_acidente_id'];
        $this->data['acidente'] = (new SstAcidentesRepository())->getById($acidenteId);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Editar plano de ação - SST',
            'menu' => 'sst-list-acidentes',
            'buttonPermission' => ['SstUpdatePlanoAcao'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/planos_acao/form', $this->data))->loadView();
    }

    private function update(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_planos_acao_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $acidenteId = (int) ($_POST['adms_sst_acidente_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($id <= 0 || $acidenteId <= 0 || $titulo === '') {
            $_SESSION['msg'] = 'Dados inválidos.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-acidente/' . $acidenteId);
            exit;
        }

        $data = [
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? null,
            'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id']) ? (int) $_POST['responsavel_adms_user_id'] : null,
            'prazo' => $_POST['prazo'] ?? null,
            'data_conclusao' => $_POST['data_conclusao'] ?? null,
            'status' => $_POST['status'] ?? 'Pendente',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];

        $repo = new SstPlanosAcaoRepository();
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Plano de ação atualizado com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível atualizar o plano de ação.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-acidente/' . $acidenteId);
        exit;
    }
}
