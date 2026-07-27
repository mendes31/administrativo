<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ApprovalDelegationsRepository;
use App\adms\Views\Services\LoadViewService;

class ListApprovalDelegations
{
    private array|string|null $data = null;

    public function index(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $repo = new ApprovalDelegationsRepository();
        $isFull = UserAccessHelper::hasFullSystemAccess();

        $this->data['delegations'] = $isFull
            ? $repo->listAllActive()
            : $repo->listForUser($userId, true);

        $pageElements = [
            'title_head' => 'Delegações de Aprovação',
            'menu' => 'list-approval-delegations',
            'buttonPermission' => [
                'ListApprovalDelegations',
                'CreateApprovalDelegation',
                'DeleteApprovalDelegation',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/list_approval_delegations', $this->data);
        $loadView->loadView();
    }
}
