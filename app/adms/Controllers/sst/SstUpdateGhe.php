<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstGheRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateGhe
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) ($id ?? $_POST['id'] ?? 0));
            return;
        }
        $item = (new SstGheRepository())->getById((int) $id);
        if (!$item) {
            $_SESSION['msg'] = 'GHE não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $pageElements = [
            'title_head' => 'Editar GHE SST',
            'menu' => 'sst-list-ghe',
            'buttonPermission' => ['SstUpdateGhe'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ghe/form', $this->data))->loadView();
    }

    private function update(int $id): void
    {
        if ($id <= 0 || !CSRFHelper::validateCSRFToken('sst_ghe_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }
        $nome = trim((string) ($_POST['nome'] ?? ''));
        if ($nome === '') {
            $_SESSION['msg'] = 'Informe o nome do GHE.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-ghe/' . $id);
            exit;
        }
        $ok = (new SstGheRepository())->update($id, [
            'codigo' => trim((string) ($_POST['codigo'] ?? '')) ?: null,
            'nome' => $nome,
            'descricao' => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'ambiente_local' => trim((string) ($_POST['ambiente_local'] ?? '')) ?: null,
            'adms_department_id' => (int) ($_POST['adms_department_id'] ?? 0) ?: null,
            'status' => ($_POST['status'] ?? 'Ativo') === 'Inativo' ? 'Inativo' : 'Ativo',
        ]);
        $_SESSION['msg'] = $ok ? 'GHE atualizado.' : 'Erro ao atualizar.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-ghe/' . $id);
        exit;
    }
}
