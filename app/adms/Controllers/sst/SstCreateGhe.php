<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstGheRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateGhe
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $this->data['item'] = ['status' => 'Ativo'];
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $pageElements = [
            'title_head' => 'Novo GHE SST',
            'menu' => 'sst-list-ghe',
            'buttonPermission' => ['SstCreateGhe'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ghe/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_ghe_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-ghe');
            exit;
        }
        $nome = trim((string) ($_POST['nome'] ?? ''));
        if ($nome === '') {
            $_SESSION['msg'] = 'Informe o nome do GHE.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-ghe');
            exit;
        }
        $newId = (new SstGheRepository())->create([
            'codigo' => trim((string) ($_POST['codigo'] ?? '')) ?: null,
            'nome' => $nome,
            'descricao' => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'ambiente_local' => trim((string) ($_POST['ambiente_local'] ?? '')) ?: null,
            'adms_department_id' => (int) ($_POST['adms_department_id'] ?? 0) ?: null,
            'status' => ($_POST['status'] ?? 'Ativo') === 'Inativo' ? 'Inativo' : 'Ativo',
        ]);
        if ($newId) {
            $_SESSION['msg'] = 'GHE cadastrado. Vincule colaboradores e treinamentos.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-ghe/' . $newId);
            exit;
        }
        $_SESSION['msg'] = 'Erro ao salvar GHE.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-ghe');
        exit;
    }
}
