<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\PerformanceCompetenciesRepository;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar avaliação de desempenho
 */
class ViewPerformanceReview
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

        // Verificar permissão de acesso
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && 
            $review['employee_id'] != $userId && 
            $review['reviewer_id'] != $userId && 
            $review['created_by'] != $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para acessar esta avaliação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        $this->data['review'] = $review;

        // Buscar competências avaliadas
        $competenciesRepo = new PerformanceCompetenciesRepository();
        $this->data['competencies'] = $competenciesRepo->getByReviewId((int)$id);

        // Buscar metas
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['goals'] = $goalsRepo->getAll(['performance_review_id' => (int)$id], 1, 100);

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Visualizar Avaliação de Desempenho',
            'menu' => 'view-performance-review',
            'buttonPermission' => [
                'ListPerformanceReviews',
                'UpdatePerformanceReview',
                'RecordReviewResults',
                'DeletePerformanceReview',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/view', $this->data);
        $loadView->loadView();
    }
}

