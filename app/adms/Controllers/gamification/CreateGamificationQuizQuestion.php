<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class CreateGamificationQuizQuestion
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

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->handlePost($quizId, $repo);
        }

        $this->data['quiz'] = $quiz;
        $this->data['next_sort'] = count($repo->listQuestionsForQuiz($quizId));

        $pageElements = [
            'title_head' => 'Nova questão — Gamificação',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'ListGamificationQuizQuestions',
                'ListGamificationQuizzes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/create_quiz_question', $this->data);
        $loadView->loadView();
    }

    private function handlePost(int $quizId, GamificationQuizRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_gamification_quiz_question', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $body = trim((string)($_POST['body'] ?? ''));
        $type = (string)($_POST['question_type'] ?? 'single');
        $type = $type === 'multiple' ? 'multiple' : 'single';
        $sort = (int)($_POST['sort_order'] ?? 0);
        $pts = (int)($_POST['points_correct'] ?? 1);
        if ($body === '') {
            $_SESSION['error'] = 'Informe o enunciado da questão.';
            return;
        }

        $labels = $_POST['option_label'] ?? [];
        $correctFlags = $_POST['option_correct'] ?? [];
        if (!is_array($labels)) {
            $labels = [];
        }
        if (!is_array($correctFlags)) {
            $correctFlags = [];
        }

        $options = [];
        foreach ($labels as $i => $lbl) {
            $lbl = trim((string)$lbl);
            if ($lbl === '') {
                continue;
            }
            $isCorrect = isset($correctFlags[$i]) && (string)$correctFlags[$i] === '1';
            $options[] = ['label' => $lbl, 'correct' => $isCorrect];
        }
        if (count($options) < 2) {
            $_SESSION['error'] = 'Informe pelo menos duas opções de resposta.';
            return;
        }
        $correctCount = count(array_filter($options, static fn ($o) => $o['correct']));
        if ($correctCount < 1) {
            $_SESSION['error'] = 'Marque pelo menos uma opção correta.';
            return;
        }
        if ($type === 'single' && $correctCount !== 1) {
            $_SESSION['error'] = 'Questão de escolha única deve ter exatamente uma opção correta.';
            return;
        }

        $qid = $repo->createQuestion($quizId, $body, $type, $sort, $pts);
        if ($qid <= 0) {
            $_SESSION['error'] = 'Erro ao criar questão.';
            return;
        }
        $ord = 0;
        foreach ($options as $o) {
            $repo->createOption($qid, $o['label'], $o['correct'], $ord);
            $ord++;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Questão criada.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . $quizId);
        exit;
    }
}
