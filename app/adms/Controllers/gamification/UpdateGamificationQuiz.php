<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateGamificationQuiz
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Quiz inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $quiz = $repo->findById($id);
        if (!$quiz) {
            $_SESSION['error'] = 'Quiz não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quizzes');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->handlePost($id, $repo);
            $quiz = $repo->findById($id) ?? $quiz;
        }

        $this->data['quiz'] = $quiz;

        $pageElements = [
            'title_head' => 'Editar quiz — Gamificação',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'ListGamificationQuizzes',
                'ListGamificationQuizQuestions',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/update_quiz', $this->data);
        $loadView->loadView();
    }

    private function handlePost(int $id, GamificationQuizRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_gamification_quiz', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
        if ($title === '' || $slug === '') {
            $_SESSION['error'] = 'Título e slug são obrigatórios.';
            return;
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            $_SESSION['error'] = 'Slug inválido.';
            return;
        }
        if ($repo->slugExists($slug, $id)) {
            $_SESSION['error'] = 'Já existe outro quiz com este slug.';
            return;
        }

        $repo->update($id, [
            'title' => $title,
            'slug' => $slug,
            'summary' => trim((string)($_POST['summary'] ?? '')),
            'status' => (string)($_POST['status'] ?? 'draft'),
            'passing_percent' => $_POST['passing_percent'] ?? null,
            'max_attempts' => (int)($_POST['max_attempts'] ?? 1),
            'points_on_completion' => (int)($_POST['points_on_completion'] ?? 0),
            'available_from' => trim((string)($_POST['available_from'] ?? '')) ?: null,
            'available_until' => trim((string)($_POST['available_until'] ?? '')) ?: null,
        ]);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Quiz atualizado.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'update-gamification-quiz/' . $id);
        exit;
    }
}
