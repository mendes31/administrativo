<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Diretório de colaboradores (membros) com link para o perfil na timeline.
 */
class TimelineMembers
{
    private array $data = [];

    public function index(): void
    {
        $searchQ = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($searchQ) > 200) {
            $searchQ = mb_substr($searchQ, 0, 200);
        }
        $searchForRepo = $searchQ !== '' ? $searchQ : null;

        $userRepo = new UsersRepository();
        /** @var int Limite de segurança para listagem única (sem paginação). */
        $maxMembers = 5000;
        $members = $userRepo->listActiveUsersForTimelineDirectory($maxMembers, 0, $searchForRepo);

        $this->data['members'] = $members;
        $this->data['search_query'] = $searchQ;

        $pageElements = [
            'title_head' => 'Membros — Timeline',
            'menu' => 'timeline',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/timeline/members', $this->data);
        $loadView->loadView();
    }
}
