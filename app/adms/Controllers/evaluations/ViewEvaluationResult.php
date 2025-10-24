<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAttemptsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Services\EvaluationGradingService;
use App\adms\Helpers\EvaluationLogService;

/**
 * Controller para visualizar resultado de uma tentativa de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 */
class ViewEvaluationResult
{
    private array $data = [];

    public function index($attemptId = null): void
    {
        // Converter para int se for string
        if (is_string($attemptId)) {
            $attemptId = !empty($attemptId) ? (int)$attemptId : null;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['msg'] = 'Faça login para acessar!';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if (!$attemptId) {
            $_SESSION['msg'] = 'Tentativa não informada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        $attemptsRepo = new EvaluationAttemptsRepository();
        $attempt = $attemptsRepo->getById($attemptId);

        if (!$attempt || $attempt['adms_user_id'] != $userId) {
            $_SESSION['msg'] = 'Resultado não encontrado ou você não tem permissão!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Decodificar respostas
        $respostas = json_decode($attempt['respostas'], true);

        if (!$respostas) {
            $_SESSION['msg'] = 'Erro ao carregar respostas!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Buscar questões
        $questoesIds = array_column($respostas, 'questao_id');
        $questionsRepo = new EvaluationQuestionsRepository();
        $attemptsRepo = new EvaluationAttemptsRepository();
        $conn = $attemptsRepo->getConnection();

        $placeholders = implode(',', array_fill(0, count($questoesIds), '?'));
        $sql = "SELECT id, pergunta, tipo, opcoes FROM adms_evaluation_questions WHERE id IN ($placeholders) ORDER BY ordem ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($questoesIds);
        $questoes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Indexar questões
        $questoesIndexadas = [];
        foreach ($questoes as $q) {
            $questoesIndexadas[$q['id']] = $q;
        }

        // Combinar respostas com questões
        foreach ($respostas as &$resp) {
            $questaoId = $resp['questao_id'];
            $resp['questao_texto'] = $questoesIndexadas[$questaoId]['pergunta'] ?? '';
            $resp['tipo'] = $questoesIndexadas[$questaoId]['tipo'] ?? '';
            
            if ($resp['tipo'] === 'multipla_escolha' && !empty($questoesIndexadas[$questaoId]['opcoes'])) {
                $resp['alternativas'] = explode("\n", trim($questoesIndexadas[$questaoId]['opcoes']));
            }
        }

        $this->data['attempt'] = $attempt;
        $this->data['respostas'] = $respostas;
        $this->data['aprovado'] = $attempt['nota_obtida'] >= $attempt['nota_minima_aprovacao'];
        $this->data['feedback'] = EvaluationGradingService::gerarFeedback(
            $attempt['nota_obtida'],
            $attempt['nota_minima_aprovacao']
        );

        // LOG
        EvaluationLogService::logResultViewed($attemptId, $userId);

        // Layout
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Resultado da Avaliação',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]));

        $loadView = new LoadViewService('adms/Views/evaluations/answer/viewResult', $this->data);
        $loadView->loadView();
    }
}

