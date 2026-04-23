<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class ListGamificationQuizzes
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new GamificationQuizRepository();
        $this->data['quizzes'] = $repo->listAllForAdmin();

        $pageElements = [
            'title_head' => 'Gamificação — Quizzes',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'CreateGamificationQuiz',
                'UpdateGamificationQuiz',
                'DeleteGamificationQuiz',
                'ListGamificationQuizQuestions',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/list_quizzes', $this->data);
        $loadView->loadView();
    }
}
