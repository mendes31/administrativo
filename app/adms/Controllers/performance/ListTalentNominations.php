<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\TalentNominationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class ListTalentNominations
{
    private array|string|null $data = null;
    private int $limitResult = 20;

    public function index(string|int|null $page = null): void
    {
        if ($page === null || $page === '') {
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
        } elseif (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        } else {
            $page = is_numeric($page) ? (int) $page : 1;
        }

        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_talent_nominations_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-talent-nominations');
            exit;
        }

        $filters = [];
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int) $_GET['user_id'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['nine_box'])) {
            $filters['nine_box'] = (int) $_GET['nine_box'];
        }

        if (!empty($filters)) {
            $_SESSION['list_talent_nominations_filters'] = $filters;
        } elseif (isset($_SESSION['list_talent_nominations_filters'])) {
            $filters = $_SESSION['list_talent_nominations_filters'];
        } else {
            $filters['status'] = 'active';
        }

        $repository = new TalentNominationsRepository();
        $this->data['nominations'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-talent-nominations',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['totalRecords'] = $totalRecords;
        $this->data['employees'] = (new UsersRepository())->getAllUsers(1, 1000);
        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);

        $pageElements = [
            'title_head' => 'Talent Pool (HiPo)',
            'menu' => 'list-talent-nominations',
            'buttonPermission' => [
                'CreateTalentNomination',
                'ViewTalentNomination',
                'UpdateTalentNomination',
                'NineBoxMatrix',
                'ListPdiPlans',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/list_talent_nominations', $this->data);
        $loadView->loadView();
    }
}
