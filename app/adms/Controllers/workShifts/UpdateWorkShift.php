<?php

namespace App\adms\Controllers\workShifts;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationWorkShiftService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\WorkShiftsRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateWorkShift
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_work_shift', $this->data['form']['csrf_token'])) {
            $this->editWorkShift();

            return;
        }

        $repo = new WorkShiftsRepository();
        $this->data['form'] = $repo->getWorkShift((int) $id);
        if (!$this->data['form']) {
            GenerateLog::generateLog('error', 'Turno não encontrado', ['id' => (int) $id]);
            $_SESSION['error'] = 'Turno não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');

            return;
        }

        $f = &$this->data['form'];
        for ($i = 1; $i <= 3; $i++) {
            $f['entry_' . $i] = WorkShiftsRepository::timeInputValue($f['entry_' . $i] ?? null);
            $f['exit_' . $i] = WorkShiftsRepository::timeInputValue($f['exit_' . $i] ?? null);
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Editar turno de trabalho',
            'menu' => 'list-work-shifts',
            'buttonPermission' => ['ListWorkShifts', 'ViewWorkShift'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/workShifts/update', $this->data);
        $loadView->loadView();
    }

    private function editWorkShift(): void
    {
        $validation = new ValidationWorkShiftService();
        $this->data['errors'] = $validation->validate($this->data['form']);
        if (!empty($this->data['errors'])) {
            $this->viewForm();

            return;
        }

        $repo = new WorkShiftsRepository();
        $result = $repo->updateWorkShift($this->data['form']);
        if ($result) {
            $_SESSION['success'] = 'Turno atualizado com sucesso!';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-work-shift/' . (int) $this->data['form']['id']);

            return;
        }
        $this->data['errors'][] = 'Não foi possível atualizar o turno.';
        $this->viewForm();
    }
}
