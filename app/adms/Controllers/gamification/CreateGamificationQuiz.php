<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class CreateGamificationQuiz
{
    private array|string|null $data = null;

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->create();
        }

        $pageElements = [
            'title_head' => 'Novo quiz — Gamificação',
            'menu' => 'ListGamificationQuizzes',
            'buttonPermission' => [
                'ListGamificationQuizzes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/create_quiz', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_gamification_quiz', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-gamification-quiz');
            exit;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
        if ($title === '' || $slug === '') {
            $_SESSION['error'] = 'Título e identificador (slug) são obrigatórios.';
            return;
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            $_SESSION['error'] = 'Slug inválido: use apenas letras minúsculas, números, hífen e underscore.';
            return;
        }

        $repo = new GamificationQuizRepository();
        if ($repo->slugExists($slug)) {
            $_SESSION['error'] = 'Já existe um quiz com este slug.';
            return;
        }

        $newId = $repo->create([
            'title' => $title,
            'slug' => $slug,
            'summary' => trim((string)($_POST['summary'] ?? '')),
            'status' => (string)($_POST['status'] ?? 'draft'),
            'passing_percent' => $_POST['passing_percent'] ?? null,
            'max_attempts' => (int)($_POST['max_attempts'] ?? 1),
            'points_on_completion' => (int)($_POST['points_on_completion'] ?? 0),
            'available_from' => trim((string)($_POST['available_from'] ?? '')) ?: null,
            'available_until' => trim((string)($_POST['available_until'] ?? '')) ?: null,
            'created_by' => (int)($_SESSION['user_id'] ?? 0),
        ]);
        if ($newId <= 0) {
            $_SESSION['error'] = 'Não foi possível criar o quiz.';
            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Quiz criado. Adicione as questões.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . $newId);
        exit;
    }
}
