<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;

class DeleteGamificationQuizQuestion
{
    public function index(int|string $id): void
    {
        $qid = (int)$id;
        if ($qid <= 0) {
            $_SESSION['error'] = 'Questão inválida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_gamification_quiz_question', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
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

        $repo->deleteQuestion($qid, $quizId);
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Questão excluída.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . $quizId);
        exit;
    }
}
