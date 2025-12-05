<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Dashboard de Desempenho
 */
class PerformanceDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $reviewsRepo = new PerformanceReviewsRepository();
        $competenciesRepo = new CompetenciesRepository();
        
        // Estatísticas gerais
        $this->data['total_reviews'] = count($reviewsRepo->getAll([], 1, 1000));
        $this->data['completed_reviews'] = count($reviewsRepo->getAll(['status' => 'completed'], 1, 1000));
        $this->data['pending_reviews'] = count($reviewsRepo->getAll(['status' => 'pending'], 1, 1000));
        $this->data['in_progress_reviews'] = count($reviewsRepo->getAll(['status' => 'in_progress'], 1, 1000));
        $this->data['total_competencies'] = count($competenciesRepo->getAll());
        
        // Últimas avaliações
        $this->data['recent_reviews'] = array_slice($reviewsRepo->getAll([], 1, 10), 0, 5);
        
        // Estatísticas por tipo de avaliação
        $allReviews = $reviewsRepo->getAll([], 1, 1000);
        $reviewsByType = [];
        foreach ($allReviews as $review) {
            $type = $review['review_type'] ?? 'outros';
            $reviewsByType[$type] = ($reviewsByType[$type] ?? 0) + 1;
        }
        $this->data['reviews_by_type'] = $reviewsByType;
        
        // Estatísticas por status
        $reviewsByStatus = [];
        foreach ($allReviews as $review) {
            $status = $review['status'] ?? 'draft';
            $reviewsByStatus[$status] = ($reviewsByStatus[$status] ?? 0) + 1;
        }
        $this->data['reviews_by_status'] = $reviewsByStatus;
        
        // Avaliações por mês (últimos 12 meses)
        $monthlyReviews = [];
        $currentDate = new \DateTime();
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = clone $currentDate;
            $monthDate->modify("-$i months");
            $monthKey = $monthDate->format('M/Y');
            $monthlyReviews[$monthKey] = 0;
        }
        
        foreach ($allReviews as $review) {
            if (!empty($review['review_date'])) {
                $reviewDate = new \DateTime($review['review_date']);
                $monthKey = $reviewDate->format('M/Y');
                if (isset($monthlyReviews[$monthKey])) {
                    $monthlyReviews[$monthKey]++;
                }
            }
        }
        $this->data['monthly_reviews'] = $monthlyReviews;
        
        // Média de notas (apenas avaliações concluídas)
        $completedReviews = array_filter($allReviews, function($r) {
            return ($r['status'] ?? '') === 'completed' && !empty($r['overall_score']);
        });
        
        if (!empty($completedReviews)) {
            $totalScore = array_sum(array_column($completedReviews, 'overall_score'));
            $this->data['average_score'] = round($totalScore / count($completedReviews), 2);
        } else {
            $this->data['average_score'] = 0;
        }
        
        $pageElements = [
            'title_head' => 'Dashboard de Desempenho',
            'menu' => 'performance-dashboard',
            'buttonPermission' => [
                'ListPerformanceReviews',
                'ListCompetencies',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/dashboard', $this->data);
        $loadView->loadView();
    }
}

