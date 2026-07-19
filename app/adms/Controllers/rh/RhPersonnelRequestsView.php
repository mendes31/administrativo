<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhPersonnelRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/** Detalhe / aprovação / conversão de requisição de pessoal. */
class RhPersonnelRequestsView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int) $id;
        $repo = new RhPersonnelRequestsRepository();
        $req = $repo->getById($id);
        if (!$req) {
            $_SESSION['error'] = 'Requisição não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests');
            return;
        }

        $this->data['request'] = $req;
        $this->data['csrf_approve'] = CSRFHelper::generateCSRFToken('form_approve_rh_personnel_request');
        $this->data['csrf_reject'] = CSRFHelper::generateCSRFToken('form_reject_rh_personnel_request');
        $this->data['csrf_convert'] = CSRFHelper::generateCSRFToken('form_convert_rh_personnel_request');

        $pageElements = [
            'title_head' => 'Requisição de Pessoal #' . $id,
            'menu' => 'rh-personnel-requests',
            'buttonPermission' => [
                'RhPersonnelRequests',
                'RhPersonnelRequestsApprove',
                'RhPersonnelRequestsReject',
                'RhPersonnelRequestsConvert',
            ],
        ];
        $layout = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $layout->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/rh/personnel_requests/view', $this->data))->loadView();
    }
}
