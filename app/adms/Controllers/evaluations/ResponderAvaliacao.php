<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ResponderAvaliacao
{
    private array $data = [];

    public function index($modeloId = null, $perguntaId = null): void
    {
        $usuarioId = $_SESSION['user_id'] ?? null;
        if (!$usuarioId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        $modeloId = $modeloId ?? ($_GET['modelo_id'] ?? null);
        $perguntaId = $perguntaId ?? ($_GET['pergunta_id'] ?? null);
        if (!$modeloId || !$perguntaId) {
            $_SESSION['msg'] = 'Avaliação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        $questionsRepo = new EvaluationQuestionsRepository();
        $pergunta = $questionsRepo->getQuestion($perguntaId);
        if (!$pergunta || $pergunta['evaluation_model_id'] != $modeloId) {
            $_SESSION['msg'] = 'Pergunta não encontrada ou não pertence ao modelo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        $this->data['pergunta'] = $pergunta;
        $this->data['modelo_id'] = $modeloId;
        $this->data['pergunta_id'] = $perguntaId;

        // Processar resposta
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resposta'])) {
            $repo = new EvaluationAnswersRepository();
            $resposta = trim($_POST['resposta']);
            $salvo = $repo->create([
                'usuario_id' => $usuarioId,
                'evaluation_model_id' => $modeloId,
                'evaluation_question_id' => $perguntaId,
                'resposta' => $resposta,
                'status' => 'respondido',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if ($salvo) {
                $_SESSION['msg'] = 'Resposta registrada com sucesso!';
                header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
                exit;
            } else {
                $this->data['error'] = 'Erro ao salvar resposta. Tente novamente.';
            }
        }

        // Layout e permissões
        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements([
            'title_head' => 'Responder Avaliação',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]));

        $loadView = new LoadViewService('adms/Views/evaluations/responderAvaliacao', $this->data);
        $loadView->loadView();
    }
} 