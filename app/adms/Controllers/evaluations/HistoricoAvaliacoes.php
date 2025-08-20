<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class HistoricoAvaliacoes
{
    private array $data = [];

    public function index(): void
    {
        $usuarioId = $_SESSION['user_id'] ?? null;
        if (!$usuarioId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        try {
            $repo = new EvaluationAnswersRepository();
            $this->data['answeredEvaluations'] = $repo->getAnsweredEvaluationsByUser($usuarioId);
        } catch (\Throwable $e) {
            $this->data['answeredEvaluations'] = [];
            $this->data['error'] = 'Erro ao buscar histórico: ' . $e->getMessage();
        }
        $pageLayout = new PageLayoutService();
        $this->data = $pageLayout->configurePageElements([
            'title_head' => 'Histórico de Avaliações',
            'menu' => 'historico-avaliacoes',
            'buttonPermission' => [],
        ]);
        $loadView = new LoadViewService('adms/Views/evaluations/historicoAvaliacoes', $this->data);
        $loadView->loadView();
    }
} 