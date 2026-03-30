<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar avaliação de desempenho
 */
class UpdatePerformanceReview
{
    private array|string|null $data = null;

    public function index(?string $id = null): void
    {
        if (empty($id)) {
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
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && 
            $review['created_by'] != $userId && 
            $review['reviewer_id'] != $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para editar esta avaliação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        } else {
            $this->showForm((int)$id, $review);
        }
    }

    private function showForm(int $id, array $review): void
    {
        $this->data['review'] = $review;

        // Buscar usuários
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        // Buscar avaliações
        $evaluationRepo = new \App\adms\Models\Repository\EvaluationModelsRepository();
        $this->data['evaluations'] = $evaluationRepo->getAllModels([], 1, 100);

        $pageElements = [
            'title_head' => 'Editar Avaliação de Desempenho',
            'menu' => 'update-performance-review',
            'buttonPermission' => [
                'ListPerformanceReviews',
                'ViewPerformanceReview',
                'DeletePerformanceReview',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $data = [
            'review_type' => $_POST['review_type'] ?? null,
            'review_period_start' => $_POST['review_period_start'] ?? null,
            'review_period_end' => $_POST['review_period_end'] ?? null,
            'review_date' => $_POST['review_date'] ?? null,
            'status' => $_POST['status'] ?? null,
            'overall_score' => !empty($_POST['overall_score']) ? $_POST['overall_score'] : null,
            'strengths' => $_POST['strengths'] ?? null,
            'improvements' => $_POST['improvements'] ?? null,
            'comments' => $_POST['comments'] ?? null,
            'employee_comments' => $_POST['employee_comments'] ?? null,
            'evaluation_id' => !empty($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : null,
        ];

        // Remover campos vazios
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });

        if (isset($data['status']) && $data['status'] === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $repository = new PerformanceReviewsRepository();
        
        try {
            $success = $repository->update($id, $data);
            
            if ($success) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Avaliação de desempenho atualizada com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-review/' . $id);
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Nenhuma alteração foi feita.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'update-performance-review/' . $id);
            }
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar avaliação: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-performance-review/' . $id);
            exit;
        }
    }
}

