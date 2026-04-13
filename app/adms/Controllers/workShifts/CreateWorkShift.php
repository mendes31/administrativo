<?php

namespace App\adms\Controllers\workShifts;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationWorkShiftService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WorkShiftsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateWorkShift
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_work_shift', $this->data['form']['csrf_token'])) {
            $this->addWorkShift();
        } else {
            $this->viewForm();
        }
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar turno de trabalho',
            'menu' => 'list-work-shifts',
            'buttonPermission' => ['ListWorkShifts'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/workShifts/create', $this->data);
        $loadView->loadView();
    }

    private function addWorkShift(): void
    {
        $validation = new ValidationWorkShiftService();
        $this->data['errors'] = $validation->validate($this->data['form']);
        if (!empty($this->data['errors'])) {
            $this->viewForm();

            return;
        }

        $repo = new WorkShiftsRepository();
        $result = $repo->createWorkShift($this->data['form']);
        if ($result) {
            $_SESSION['success'] = 'Turno cadastrado com sucesso!';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-work-shift/' . $result);

            return;
        }
        $this->data['errors'][] = 'Não foi possível cadastrar o turno.';
        $this->viewForm();
    }
}
