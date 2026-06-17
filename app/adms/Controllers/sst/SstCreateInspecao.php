<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstInspecoesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateInspecao
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        $this->data['item'] = ['status' => 'Aberta', 'data_inspecao' => date('Y-m-d'), 'tipo' => 'Rotina'];
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $pageElements = ['title_head' => 'Nova inspeção', 'menu' => 'sst-list-inspecoes', 'buttonPermission' => ['SstCreateInspecao']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/inspecoes/form', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_inspecoes_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $data = $this->parsePost();
        if ($data['titulo'] === '') {
            $_SESSION['msg'] = 'Informe o título.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-inspecao');
            exit;
        }
        $id = (new SstInspecoesRepository())->create($data);
        if ($id) {
            $_SESSION['msg'] = 'Inspeção registrada.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-inspecao/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-inspecao');
        }
        exit;
    }

    /** @return array<string, mixed> */
    private function parsePost(): array
    {
        return [
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
    }
}
