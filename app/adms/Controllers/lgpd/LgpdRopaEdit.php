<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdRopaEdit
{
    private array|string|null $data = null;

    public function index($id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_edit_ropa', $this->data['form']['csrf_token'])) {
            $this->editRopa($id);
        } else {
            $repo = new LgpdRopaRepository();
            $this->data['form'] = $repo->getById($id);
            if (!$this->data['form']) {
                $_SESSION['error'] = "Registro não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
                return;
            }
            $this->viewEdit();
        }
    }

    private function viewEdit(): void
    {
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();

        $pageElements = [
            'title_head' => 'Editar ROPA',
            'menu' => 'ListLgpdRopa',
            'buttonPermission' => ['ListLgpdRopa', 'EditLgpdRopa'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/ropa/edit", $this->data);
        $loadView->loadView();
    }

    private function editRopa($id): void
    {
        $repo = new LgpdRopaRepository();
        $result = $repo->update($this->data['form']);
        
        if ($result) {
            $_SESSION['success'] = "Registro ROPA editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
            return;
        } else {
            $this->data['errors'][] = "Registro não editado!";
            $this->viewEdit();
        }
    }
} 