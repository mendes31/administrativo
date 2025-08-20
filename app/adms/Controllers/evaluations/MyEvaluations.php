<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class MyEvaluations
{
    private array $data = [];

    public function index(): void
    {
        // Recuperar o ID do usuário logado (ajuste conforme seu sistema de autenticação)
        $usuarioId = $_SESSION['user_id'] ?? null;
        if (!$usuarioId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        try {
            $repo = new EvaluationAnswersRepository();
            $this->data['pendingEvaluations'] = $repo->getPendingEvaluationsByUser($usuarioId);
        } catch (\Throwable $e) {
            $this->data['pendingEvaluations'] = [];
            $this->data['error'] = 'Erro ao buscar avaliações: ' . $e->getMessage();
        }

        // Layout e permissões
        $pageLayout = new PageLayoutService();
        $this->data = $pageLayout->configurePageElements([
            'title_head' => 'Minhas Avaliações',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]);

        $loadView = new LoadViewService('adms/Views/evaluations/myEvaluations', $this->data);
        $loadView->loadView();
    }
} 