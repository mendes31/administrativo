<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdRopaCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_ropa', $this->data['form']['csrf_token'])) {
            $this->addRopa();
        } else {
            $this->viewCreate();
        }
    }

    private function viewCreate(): void
    {
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();

        $pageElements = [
            'title_head' => 'Cadastrar ROPA',
            'menu' => 'ListLgpdRopa',
            'buttonPermission' => ['ListLgpdRopa'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/ropa/create", $this->data);
        $loadView->loadView();
    }

    private function addRopa(): void
    {
        // Aqui você pode adicionar validação de campos se desejar
        $repo = new LgpdRopaRepository();
        $result = $repo->create($this->data['form']);
        if ($result) {
            $_SESSION['success'] = "Registro ROPA cadastrado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa-view/$result");
            return;
        } else {
            $this->data['errors'][] = "Registro não cadastrado!";
            $this->viewCreate();
        }
    }
} 