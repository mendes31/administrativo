<?php

namespace App\adms\Controllers\workShifts;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\WorkShiftsRepository;

class DeleteWorkShift
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (!isset($this->data['form']['csrf_token'])
            || !CSRFHelper::validateCSRFToken('form_delete_work_shift', $this->data['form']['csrf_token'])
            || !isset($this->data['form']['id'])) {
            GenerateLog::generateLog('error', 'Exclusão de turno: token ou id inválido.', []);
            $_SESSION['error'] = 'Operação não permitida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');

            return;
        }

        $repo = new WorkShiftsRepository();
        $row = $repo->getWorkShift((int) $this->data['form']['id']);
        if (!$row) {
            $_SESSION['error'] = 'Turno não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');

            return;
        }

        if ($repo->deleteWorkShift((int) $this->data['form']['id'])) {
            $_SESSION['success'] = 'Turno apagado com sucesso!';
        } else {
            $_SESSION['error'] = 'Turno não apagado.';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-work-shifts');
    }
}
