<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstInspecoesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateInspecao
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $repo = new SstInspecoesRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Inspeção não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $pageElements = ['title_head' => 'Editar inspeção', 'menu' => 'sst-list-inspecoes', 'buttonPermission' => ['SstUpdateInspecao']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/inspecoes/form', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_inspecoes_form', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'tipo' => $_POST['tipo'] ?? 'Rotina',
            'data_inspecao' => $_POST['data_inspecao'] ?? date('Y-m-d'),
            'adms_department_id' => !empty($_POST['adms_department_id']) ? (int) $_POST['adms_department_id'] : null,
            'local' => $_POST['local'] ?? null,
            'inspetor_adms_user_id' => !empty($_POST['inspetor_adms_user_id']) ? (int) $_POST['inspetor_adms_user_id'] : null,
            'participantes' => $_POST['participantes'] ?? null,
            'descricao' => $_POST['descricao'] ?? null,
            'conclusao' => $_POST['conclusao'] ?? null,
            'status' => $_POST['status'] ?? 'Aberta',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        (new SstInspecoesRepository())->update($id, $data);
        $_SESSION['msg'] = 'Inspeção atualizada.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-inspecao/' . $id);
        exit;
    }
}
