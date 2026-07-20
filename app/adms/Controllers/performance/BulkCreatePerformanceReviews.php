<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\PerformanceReviewBulkService;
use App\adms\Views\Services\LoadViewService;

/**
 * Gerar avaliações em massa a partir de um ciclo.
 */
class BulkCreatePerformanceReviews
{
    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $cycleId = (int) $id;
        if ($cycleId <= 0) {
            $cycleId = (int) ($_GET['performance_cycle_id'] ?? $_POST['performance_cycle_id'] ?? 0);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->generate($cycleId);
        }

        $cyclesRepo = new PerformanceCyclesRepository();
        $cycle = $cycleId > 0 ? $cyclesRepo->getById($cycleId) : null;
        if (!$cycle) {
            $_SESSION['error'] = 'Ciclo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-cycles');
            exit;
        }

        $this->data['cycle'] = $cycle;
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $usersRepo = new UsersRepository();
        $this->data['managers'] = $usersRepo->getAllUsersForSelect();
        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'performance_cycle_id' => $cycleId,
                'review_type' => '180',
                'scope' => UserAccessHelper::hasFullSystemAccess() ? 'all_active' : 'manager',
                'department_id' => '',
                'manager_id' => (string) ($_SESSION['user_id'] ?? ''),
                'reviewer_mode' => 'supervisor',
                'reviewer_id' => '',
                'skip_existing' => '1',
            ];
        }

        $pageElements = [
            'title_head' => 'Gerar Avaliações em Massa',
            'menu' => 'list-performance-cycles',
            'buttonPermission' => [
                'ListPerformanceCycles',
                'ListPerformanceReviews',
                'BulkCreatePerformanceReviews',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/bulk_create_reviews', $this->data);
        $loadView->loadView();
    }

    private function generate(int $cycleId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_bulk_create_performance_reviews', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'bulk-create-performance-reviews/' . $cycleId);
            exit;
        }

        $cycleId = (int) ($_POST['performance_cycle_id'] ?? $cycleId);
        $result = (new PerformanceReviewBulkService())->generateForCycle(
            $cycleId,
            $_POST,
            (int) ($_SESSION['user_id'] ?? 0),
            UserAccessHelper::hasFullSystemAccess()
        );

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao gerar avaliações.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">'
            . htmlspecialchars((string) ($result['message'] ?? 'Avaliações geradas.'))
            . '</div>';
        GenerateLog::generateLog('info', 'Avaliações geradas em massa.', [
            'cycle_id' => $cycleId,
            'created' => $result['created'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
            'errors' => $result['errors'] ?? 0,
        ]);
        header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews?performance_cycle_id=' . $cycleId);
        exit;
    }
}
