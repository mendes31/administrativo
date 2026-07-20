<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Services\PerformanceFeedbackService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar feedbacks de desempenho
 */
class ListPerformanceFeedbacks
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
            unset($_SESSION['list_performance_feedbacks_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            exit;
        }

        $filters = [];
        if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
            $filters['employee_id'] = (int) $_GET['employee_id'];
        }
        if (isset($_GET['given_by']) && !empty($_GET['given_by'])) {
            $filters['given_by'] = (int) $_GET['given_by'];
        }
        if (isset($_GET['feedback_type']) && $_GET['feedback_type'] !== '') {
            $filters['feedback_type'] = $_GET['feedback_type'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if (!empty($filters)) {
            $_SESSION['list_performance_feedbacks_filters'] = $filters;
        } elseif (isset($_SESSION['list_performance_feedbacks_filters'])) {
            $filters = $_SESSION['list_performance_feedbacks_filters'];
        }

        $repository = new PerformanceFeedbacksRepository();
        $feedbacks = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        $actorId = (int) ($_SESSION['user_id'] ?? 0);
        $fullAccess = UserAccessHelper::hasFullSystemAccess();
        $service = new PerformanceFeedbackService();
        foreach ($feedbacks as &$row) {
            $row['author_display'] = $service->displayAuthorName($row, $actorId, $fullAccess);
        }
        unset($row);

        $this->data['feedbacks'] = $feedbacks;

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-performance-feedbacks',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;

        $this->data['employees'] = $service->listEligibleRecipients($actorId, $fullAccess);
        $this->data['viewer_id'] = $actorId;
        $this->data['viewer_full_access'] = $fullAccess;

        $pageElements = [
            'title_head' => 'Listar Feedbacks de Desempenho',
            'menu' => 'list-performance-feedbacks',
            'buttonPermission' => [
                'CreatePerformanceFeedback',
                'ViewPerformanceFeedback',
                'UpdatePerformanceFeedback',
                'DeletePerformanceFeedback',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/list_feedbacks', $this->data);
        $loadView->loadView();
    }
}
