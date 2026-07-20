<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CriticalPositionsRepository;
use App\adms\Models\Services\SuccessionService;
use App\adms\Views\Services\LoadViewService;

class UpdateCriticalPosition
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $cid = (int) $id;
        $repository = new CriticalPositionsRepository();
        $item = $cid > 0 ? $repository->getById($cid) : null;
        if (!$item) {
            $_SESSION['error'] = 'Cargo crítico não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-critical-positions');
            exit;
        }

        $this->data['item'] = $item;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_update_critical_position', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de segurança inválido.';
            } else {
                $result = (new SuccessionService())->updateCritical($cid, $_POST);
                if (!$result['ok']) {
                    $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar.';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Atualizado!</div>';
                    GenerateLog::generateLog('info', 'Cargo crítico atualizado.', ['id' => $cid]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-critical-position/' . $cid);
                    exit;
                }
            }
            $this->data['item'] = $repository->getById($cid) ?? $item;
        }

        $pageElements = [
            'title_head' => 'Editar Cargo Crítico',
            'menu' => 'list-critical-positions',
            'buttonPermission' => ['ListCriticalPositions', 'ViewCriticalPosition'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/performance/update_critical_position', $this->data))->loadView();
    }
}
