<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class TakeGamificationQuiz
{
    private array|string|null $data = null;

    public function index(int|string|null $quizId = null): void
    {
        $quizId = (int)($quizId ?? 0);
        if ($quizId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['error'] = 'Sessão expirada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TakeGamificationQuiz']);
        if (!is_array($perms) || !in_array('TakeGamificationQuiz', $perms, true)) {
            $_SESSION['error'] = 'Sem permissão para responder quizzes.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $quiz = $repo->findPublishedById($quizId);
        if (!$quiz) {
            $_SESSION['error'] = 'Quiz não disponível.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $questions = $repo->listQuestionsForQuiz($quizId);
        if ($questions === []) {
            $_SESSION['error'] = 'Este quiz ainda não possui questões.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $withOptions = [];
        $maxScore = 0;
        foreach ($questions as $q) {
            $opts = $repo->listOptionsForQuestion((int)$q['id']);
            if (count($opts) < 2) {
                continue;
            }
            $q['options'] = $opts;
            $withOptions[] = $q;
            $maxScore += max(0, (int)($q['points_correct'] ?? 0));
        }
        if ($withOptions === [] || $maxScore <= 0) {
            $_SESSION['error'] = 'Pontuação do quiz inválida. Contate o administrador.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $maxAttempts = max(1, (int)($quiz['max_attempts'] ?? 1));
        $completed = $repo->countCompletedAttempts($quizId, $userId);
        if ($completed >= $maxAttempts) {
            $_SESSION['error'] = 'Você já utilizou o número máximo de tentativas para este quiz.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $repo->abandonStaleInProgressAttempts($quizId, $userId, 6);
        $attempt = $repo->findOpenAttempt($quizId, $userId);
        if (!$attempt) {
            $aid = $repo->createAttempt($quizId, $userId, $maxScore);
            $attempt = $repo->findAttempt($aid, $userId);
        }
        if (!$attempt) {
            $_SESSION['error'] = 'Não foi possível iniciar a tentativa.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $this->data['quiz'] = $quiz;
        $this->data['questions'] = $withOptions;
        $this->data['attempt'] = $attempt;

        $pageElements = [
            'title_head' => 'Responder quiz — ' . (string)$quiz['title'],
            'menu' => 'GamificationQuizCatalog',
            'buttonPermission' => [
                'SubmitGamificationQuizAttempt',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/take_quiz', $this->data);
        $loadView->loadView();
    }
}
