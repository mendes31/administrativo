<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingListCommittees
{
    private array $data = [];

    public function index(): void
    {
        $repo = new WhistleblowingCommitteesRepository();
        $this->data['committees'] = $repo->getAllCommittees();
        $committeeIds = array_map(
            static fn(array $committee): int => (int) ($committee['id'] ?? 0),
            $this->data['committees']
        );
        $this->data['committee_details'] = $repo->getCommitteeListDetails($committeeIds);

        $pageElements = [
            'title_head' => 'Comitês — Canal de Denúncias',
            'menu' => 'list-whistleblowing-committees',
            'buttonPermission' => ['WhistleblowingCreateCommittee', 'WhistleblowingUpdateCommittee'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/committees/list', $this->data);
        $loadView->loadView();
    }
}
