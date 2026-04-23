<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateGamificationQuizQuestion
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $qid = (int)$id;
        if ($qid <= 0) {
            $_SESSION['error'] = 'Questão inválida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $question = $repo->findQuestionById($qid);
        if (!$question) {
            $_SESSION['error'] = 'Questão não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $quizId = (int)($question['quiz_id'] ?? 0);
        $quiz = $repo->findById($quizId);
        if (!$quiz) {
            $_SESSION['error'] = 'Quiz não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->handlePost($qid, $quizId, $repo);
            $question = $repo->findQuestion($qid, $quizId) ?? $question;
        }

        $this->data['quiz'] = $quiz;
        $this->data['question'] = $question;
        $this->data['options'] = $repo->listOptionsForQuestion($qid);

        $pageElements = [
            'title_head' => 'Editar questão — Gamificação',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'ListGamificationQuizQuestions',
                'ListGamificationQuizzes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/update_quiz_question', $this->data);
        $loadView->loadView();
    }

    private function handlePost(int $qid, int $quizId, GamificationQuizRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_gamification_quiz_question', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $body = trim((string)($_POST['body'] ?? ''));
        $type = (string)($_POST['question_type'] ?? 'single');
        $type = $type === 'multiple' ? 'multiple' : 'single';
        $sort = (int)($_POST['sort_order'] ?? 0);
        $pts = (int)($_POST['points_correct'] ?? 1);
        if ($body === '') {
            $_SESSION['error'] = 'Informe o enunciado.';
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
            $_SESSION['error'] = 'Informe pelo menos duas opções.';
            return;
        }
        $correctCount = count(array_filter($options, static fn ($o) => $o['correct']));
        if ($correctCount < 1) {
            $_SESSION['error'] = 'Marque pelo menos uma opção correta.';
            return;
        }
        if ($type === 'single' && $correctCount !== 1) {
            $_SESSION['error'] = 'Escolha única exige exatamente uma opção correta.';
            return;
        }

        $repo->updateQuestion($qid, $quizId, $body, $type, $sort, $pts);
        $repo->deleteOptionsForQuestion($qid);
        $ord = 0;
        foreach ($options as $o) {
            $repo->createOption($qid, $o['label'], $o['correct'], $ord);
            $ord++;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Questão atualizada.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . $quizId);
        exit;
    }
}
