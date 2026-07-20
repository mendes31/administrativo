<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TalentNominationsRepository;
use App\adms\Models\Services\TalentNominationService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Matriz 9BOX
 */
class NineBoxMatrix
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'nominate') {
            $this->nominateFromMatrix();
        }

        $repository = new PerformanceReviewsRepository();
        $departmentsRepo = new DepartmentsRepository();
        $positionsRepo = new PositionsRepository();

        $filters = [];
        if (!empty($_GET['department_id'])) {
            $filters['department_id'] = (int) $_GET['department_id'];
        }
        if (!empty($_GET['position_id'])) {
            $filters['position_id'] = (int) $_GET['position_id'];
        }
        if (!empty($_GET['period_start'])) {
            $filters['period_start'] = $_GET['period_start'];
        }
        if (!empty($_GET['period_end'])) {
            $filters['period_end'] = $_GET['period_end'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }

        $matrixData = $repository->getNineBoxData($filters);

        $this->data['boxes'] = $matrixData['boxes'];
        $this->data['employees'] = $matrixData['employees'];
        $this->data['total'] = $matrixData['total'];
        $this->data['filters'] = $filters;

        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['positions'] = $positionsRepo->getAllPositionsSelect();
        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);

        $this->data['nominations_map'] = [];
        if (!empty($filters['performance_cycle_id'])) {
            $this->data['nominations_map'] = (new TalentNominationsRepository())
                ->getActiveMapByCycle((int) $filters['performance_cycle_id']);
        }

        $this->data['box_stats'] = [];
        foreach ($matrixData['boxes'] as $boxNum => $employees) {
            $this->data['box_stats'][$boxNum] = count($employees);
        }

        $pageElements = [
            'title_head' => 'Matriz 9BOX',
            'menu' => 'nine-box-matrix',
            'buttonPermission' => [
                'ListPerformanceReviews',
                'CreateTalentNomination',
                'ListTalentNominations',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/nine_box_matrix', $this->data);
        $loadView->loadView();
    }

    private function nominateFromMatrix(): void
    {
        $cycleId = (int) ($_POST['performance_cycle_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'nine-box-matrix'
            . ($cycleId > 0 ? '?performance_cycle_id=' . $cycleId : '');

        if (!CSRFHelper::validateCSRFToken('form_nine_box_nominate', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $redirect);
            exit;
        }

        $result = (new TalentNominationService())->create([
            'user_id' => (int) ($_POST['user_id'] ?? 0),
            'performance_cycle_id' => $cycleId,
            'nine_box' => $_POST['nine_box'] ?? null,
            'notes' => $_POST['notes'] ?? 'Nomeado a partir da Matriz 9BOX',
        ], (int) ($_SESSION['user_id'] ?? 0));

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao nomear.';
            header('Location: ' . $redirect);
            exit;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Colaborador nomeado no talent pool.</div>';
        GenerateLog::generateLog('info', 'Nomeação HiPo via Nine Box.', ['id' => $result['id']]);
        header('Location: ' . $redirect);
        exit;
    }
}
