<?php

namespace App\adms\Controllers\workShifts;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\WorkShiftsRepository;
use App\adms\Views\Services\LoadViewService;

class ViewWorkShift
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int) $id) {
            GenerateLog::generateLog('error', 'Turno não encontrado', ['id' => $id]);
            $_SESSION['error'] = 'Turno não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');

            return;
        }

        $repo = new WorkShiftsRepository();
        $this->data['work_shift'] = $repo->getWorkShift((int) $id);
        if (!$this->data['work_shift']) {
            GenerateLog::generateLog('error', 'Turno não encontrado', ['id' => (int) $id]);
            $_SESSION['error'] = 'Turno não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');

            return;
        }

        GenerateLog::generateLog('info', 'Visualizado turno de trabalho.', ['id' => (int) $id]);

        $pageElements = [
            'title_head' => 'Visualizar turno de trabalho',
            'menu' => 'list-work-shifts',
            'buttonPermission' => ['ListWorkShifts', 'UpdateWorkShift', 'DeleteWorkShift'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/workShifts/view', $this->data);
        $loadView->loadView();
    }
}
