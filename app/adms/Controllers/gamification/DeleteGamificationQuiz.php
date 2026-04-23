<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;

class DeleteGamificationQuiz
{
    public function index(int|string $id): void
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Quiz inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_gamification_quiz', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $repo = new GamificationQuizRepository();
        if (!$repo->findById($id)) {
            $_SESSION['error'] = 'Quiz não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $repo->delete($id);
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Quiz excluído.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
        exit;
    }
}
