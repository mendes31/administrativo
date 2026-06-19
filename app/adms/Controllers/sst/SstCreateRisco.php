<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoCatalogHelper;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateRisco
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstRiscosRepository();
        $this->data['item'] = ['codigo' => $repo->getProximoCodigo()];
        $this->data['entity'] = [
            'table' => 'adms_sst_riscos',
            'singular' => 'Risco',
            'plural' => 'Riscos',
            'prefix' => 'Risco',
            'url' => 'risco',
            'menu' => 'sst-list-riscos',
            'icon' => 'fa-exclamation-triangle',
            'type' => 'catalog',
        ];
        $pageElements = [
            'title_head' => 'Create Risco - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => ['SstCreateRisco'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_riscos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Sessão expirada ou formulário já enviado. Abra o cadastro novamente e tente de novo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco');
            exit;
        }
        $repo = new SstRiscosRepository();
        $data = SstRiscoCatalogHelper::parseFormData($_POST);
        if (empty($data['codigo'])) {
            $data['codigo'] = $repo->getProximoCodigo();
        }
        $error = SstRiscoCatalogHelper::validate($data, $repo);
        if ($error !== null) {
            $_SESSION['msg'] = $error;
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco');
            exit;
        }

        try {
            $newId = $repo->create($data);
        } catch (\PDOException) {
            $_SESSION['msg'] = 'Erro ao salvar. Execute as migrations SST (riscos) e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco');
            exit;
        }

        if ($newId) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-risco/' . $newId);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco');
        }
        exit;
    }
}
