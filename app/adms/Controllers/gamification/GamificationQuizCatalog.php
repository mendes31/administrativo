<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Views\Services\LoadViewService;

class GamificationQuizCatalog
{
    private array|string|null $data = null;

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['GamificationQuizCatalog']);
        if (!is_array($perms) || !in_array('GamificationQuizCatalog', $perms, true)) {
            $_SESSION['error'] = 'Sem permissão para ver o catálogo de quizzes.';
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $this->data['quizzes'] = $repo->listPublishedAvailableForUser((int)$_SESSION['user_id']);

        $pageElements = [
            'title_head' => 'Quizzes disponíveis',
            'menu' => 'GamificationQuizCatalog',
            'buttonPermission' => [
                'TakeGamificationQuiz',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/quiz_catalog', $this->data);
        $loadView->loadView();
    }
}
