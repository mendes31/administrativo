<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstProgramasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Views\Services\LoadViewService;

class SstUpdatePrograma
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update();
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $repo = new SstProgramasRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Programa não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $this->loadFormData();
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('programas', (int) $id);
        $pageElements = [
            'title_head' => 'Editar programa SST - SST',
            'menu' => 'sst-list-programas',
            'buttonPermission' => ['SstUpdatePrograma'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/programas/form', $this->data))->loadView();
    }

    private function update(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_programas_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->parsePost();
        if ($id <= 0 || $data['titulo'] === '') {
            $_SESSION['msg'] = 'Dados inválidos.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $repo = new SstProgramasRepository();
        if ($repo->update($id, $data)) {
            $uploadService = new SstAnexosUploadService();
            $uploadService->processDeletions($_POST['delete_anexos'] ?? [], 'programas', $id);
            $uploadService->processUploads('programas', $id);
            $_SESSION['msg'] = 'Programa atualizado com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao atualizar programa.';
            $_SESSION['msg_type'] = 'danger';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-programa/' . $id);
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
