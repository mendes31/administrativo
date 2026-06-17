<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstProgramasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Views\Services\LoadViewService;

class SstCreatePrograma
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $this->loadFormData();
        $this->data['item'] = ['status' => 'Rascunho', 'vigencia_inicio' => date('Y-m-d'), 'tipo' => 'PGR'];

        $pageElements = [
            'title_head' => 'Novo programa SST - SST',
            'menu' => 'sst-list-programas',
            'buttonPermission' => ['SstCreatePrograma'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/programas/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_programas_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $data = $this->parsePost();
        if ($data['titulo'] === '' || $data['vigencia_inicio'] === '') {
            $_SESSION['msg'] = 'Preencha título e vigência inicial.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-programa');
            exit;
        }

        $repo = new SstProgramasRepository();
        $newId = $repo->create($data);
        if ($newId) {
            (new SstAnexosUploadService())->processUploads('programas', $newId);
            $_SESSION['msg'] = 'Programa cadastrado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-programa/' . $newId);
            exit;
        }

        $_SESSION['msg'] = 'Erro ao salvar programa.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-programa');
        exit;
    }

    private function loadFormData(): void
    {
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['medicos'] = (new SstMedicosRepository())->getAll(1, 500);
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
    }

    /** @return array<string, mixed> */
    private function parsePost(): array
    {
        return [
            'tipo' => $_POST['tipo'] ?? 'PGR',
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'descricao' => $_POST['descricao'] ?? null,
            'versao' => $_POST['versao'] ?? null,
            'vigencia_inicio' => $_POST['vigencia_inicio'] ?? null,
            'vigencia_fim' => $_POST['vigencia_fim'] ?? null,
            'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id']) ? (int) $_POST['responsavel_adms_user_id'] : null,
            'adms_sst_medico_id' => !empty($_POST['adms_sst_medico_id']) ? (int) $_POST['adms_sst_medico_id'] : null,
            'adms_position_id' => !empty($_POST['adms_position_id']) ? (int) $_POST['adms_position_id'] : null,
            'adms_department_id' => !empty($_POST['adms_department_id']) ? (int) $_POST['adms_department_id'] : null,
            'status' => $_POST['status'] ?? 'Rascunho',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
    }
}
