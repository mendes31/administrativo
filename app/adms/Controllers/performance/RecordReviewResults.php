<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Models\Repository\PerformanceCompetenciesRepository;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para registrar resultados de avaliação de desempenho
 * Permite adicionar competências, metas, notas e comentários
 */
class RecordReviewResults
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: ID da avaliação não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        $repository = new PerformanceReviewsRepository();
        $review = $repository->getById((int)$id);

        if (!$review) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Avaliação não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        // Verificar permissão
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && 
            $review['created_by'] != $userId && 
            $review['reviewer_id'] != $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para registrar resultados desta avaliação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveResults((int)$id, $review);
        } else {
            $this->showForm((int)$id, $review);
        }
    }

    private function showForm(int $id, array $review): void
    {
        $this->data['review'] = $review;

        // Buscar competências já avaliadas
        $perfCompRepo = new PerformanceCompetenciesRepository();
        $this->data['review_competencies'] = $perfCompRepo->getByReviewId($id);

        // Buscar todas as competências ativas (para adicionar novas)
        $compRepo = new CompetenciesRepository();
        $allCompetencies = $compRepo->getAll();
        
        // Filtrar competências já adicionadas
        $addedCompetencyIds = array_column($this->data['review_competencies'], 'competency_id');
        $this->data['available_competencies'] = array_filter($allCompetencies, function($comp) use ($addedCompetencyIds) {
            return !in_array($comp['id'], $addedCompetencyIds);
        });

        // Buscar metas já vinculadas
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['review_goals'] = $goalsRepo->getAll(['performance_review_id' => $id], 1, 100);

        // Buscar metas disponíveis do colaborador (não vinculadas)
        $availableGoals = $goalsRepo->getAll([
            'employee_id' => $review['employee_id'],
            'status' => 'in_progress'
        ], 1, 100);
        
        $addedGoalIds = array_column($this->data['review_goals'], 'id');
        $this->data['available_goals'] = array_filter($availableGoals, function($goal) use ($addedGoalIds) {
            return !in_array($goal['id'], $addedGoalIds);
        });

        $pageElements = [
            'title_head' => 'Registrar Resultados da Avaliação',
            'menu' => 'record-review-results',
            'buttonPermission' => [
                'ListPerformanceReviews',
                'ViewPerformanceReview',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/record_results', $this->data);
        $loadView->loadView();
    }

    private function saveResults(int $id, array $review): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_record_review_results', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'record-review-results/' . $id);
            exit;
        }

        $repository = new PerformanceReviewsRepository();
        $perfCompRepo = new PerformanceCompetenciesRepository();
        $goalsRepo = new PerformanceGoalsRepository();

        try {
            // 1. Atualizar dados gerais da avaliação
            $updateData = [];
            
            if (isset($_POST['overall_score']) && $_POST['overall_score'] !== '') {
                $updateData['overall_score'] = (float)$_POST['overall_score'];
            }
            
            if (isset($_POST['potential_score']) && $_POST['potential_score'] !== '') {
                $updateData['potential_score'] = (float)$_POST['potential_score'];
            }
            
            if (isset($_POST['strengths'])) {
                $updateData['strengths'] = trim($_POST['strengths']);
            }
            
            if (isset($_POST['improvements'])) {
                $updateData['improvements'] = trim($_POST['improvements']);
            }
            
            if (isset($_POST['comments'])) {
                $updateData['comments'] = trim($_POST['comments']);
            }
            
            if (isset($_POST['employee_comments'])) {
                $updateData['employee_comments'] = trim($_POST['employee_comments']);
            }
            
            if (isset($_POST['status'])) {
                $updateData['status'] = $_POST['status'];
                if ($_POST['status'] === 'completed') {
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                }
            }

            if (!empty($updateData)) {
                $repository->update($id, $updateData);
            }

            // 2. Atualizar competências existentes
            if (isset($_POST['competencies']) && is_array($_POST['competencies'])) {
                foreach ($_POST['competencies'] as $compId => $compData) {
                    if (isset($compData['assessed_level']) && $compData['assessed_level'] !== '') {
                        $perfCompRepo->update((int)$compId, [
                            'assessed_level' => (int)$compData['assessed_level'],
                            'comments' => $compData['comments'] ?? null,
                        ]);
                    }
                }
            }

            // 3. Adicionar novas competências
            if (isset($_POST['new_competencies']) && is_array($_POST['new_competencies'])) {
                foreach ($_POST['new_competencies'] as $compData) {
                    if (!empty($compData['competency_id'])) {
                        $perfCompRepo->create([
                            'performance_review_id' => $id,
                            'competency_id' => (int)$compData['competency_id'],
                            'current_level' => (int)($compData['current_level'] ?? 1),
                            'target_level' => (int)($compData['target_level'] ?? 3),
                            'assessed_level' => !empty($compData['assessed_level']) ? (int)$compData['assessed_level'] : null,
                            'comments' => $compData['comments'] ?? null,
                        ]);
                    }
                }
            }

            // 4. Vincular metas existentes
            if (isset($_POST['link_goals']) && is_array($_POST['link_goals'])) {
                foreach ($_POST['link_goals'] as $goalId) {
                    $goal = $goalsRepo->getById((int)$goalId);
                    if ($goal && empty($goal['performance_review_id'])) {
                        $goalsRepo->update((int)$goalId, [
                            'performance_review_id' => $id,
                        ]);
                    }
                }
            }

            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Resultados da avaliação registrados com sucesso!</div>';
            GenerateLog::generateLog("info", "Resultados da avaliação registrados.", ['review_id' => $id]);
            header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-review/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao registrar resultados: ' . $e->getMessage();
            header('Location: ' . $_ENV['URL_ADM'] . 'record-review-results/' . $id);
            exit;
        }
    }
}

