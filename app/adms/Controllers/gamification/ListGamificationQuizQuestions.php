<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class ListGamificationQuizQuestions
{
    private array|string|null $data = null;

    public function index(int|string $quizId): void
    {
        $quizId = (int)$quizId;
        if ($quizId <= 0) {
            $_SESSION['error'] = 'Quiz inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $quiz = $repo->findById($quizId);
        if (!$quiz) {
            $_SESSION['error'] = 'Quiz não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $questions = $repo->listQuestionsForQuiz($quizId);
        foreach ($questions as &$q) {
            $q['options'] = $repo->listOptionsForQuestion((int)$q['id']);
        }
        unset($q);

        $this->data['quiz'] = $quiz;
        $this->data['questions'] = $questions;

        $pageElements = [
            'title_head' => 'Questões do quiz — Gamificação',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'CreateGamificationQuizQuestion',
                'UpdateGamificationQuizQuestion',
                'DeleteGamificationQuizQuestion',
                'UpdateGamificationQuiz',
                'ListGamificationQuizzes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/list_quiz_questions', $this->data);
        $loadView->loadView();
    }
}
