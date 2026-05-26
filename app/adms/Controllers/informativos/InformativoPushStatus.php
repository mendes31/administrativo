<?php

declare(strict_types=1);

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativoPushStatusService;
use App\adms\Models\Services\InformativosPermissionService;
use App\adms\Views\Services\LoadViewService;

/**
 * Relatório de status de entrega push (PWA) para um informativo.
 */
class InformativoPushStatus
{
    private array|string|null $data = null;

    public function index(string|int $id = null): void
    {
        $informativoId = (int) ($id ?: ($_GET['informativo_id'] ?? 0));
        $redirect = ($_ENV['URL_ADM'] ?? '') . 'list-informativos';

        if ($informativoId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById($informativoId);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Sem permissão para visualizar o status push deste informativo.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $report = InformativoPushStatusService::buildReport($informativoId);

        $this->data['informativo'] = $informativo;
        $this->data['push_users'] = $report['users'];
        $this->data['push_summary'] = $report['summary'];

        $pageElements = [
            'title_head' => 'Status Push — Informativo',
            'menu' => 'list-informativos',
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/informativos/pushStatus', $this->data);
        $loadView->loadView();
    }
}
