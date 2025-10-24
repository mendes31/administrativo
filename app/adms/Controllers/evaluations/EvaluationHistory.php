<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Repository\EvaluationAttemptsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

/**
 * Controller para visualizar histórico de tentativas de uma avaliação
 * 
 * @package App\adms\Controllers\evaluations
 */
class EvaluationHistory
{
    private array $data = [];

    public function index($assignmentId = null): void
    {
        // Converter para int se for string
        if (is_string($assignmentId)) {
            $assignmentId = !empty($assignmentId) ? (int)$assignmentId : null;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['msg'] = 'Faça login para acessar!';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if (!$assignmentId) {
            $_SESSION['msg'] = 'Atribuição não informada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Buscar atribuição
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $assignment = $assignmentsRepo->getById($assignmentId);

        if (!$assignment || $assignment['adms_user_id'] != $userId) {
            $_SESSION['msg'] = 'Histórico não encontrado ou você não tem permissão!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Buscar histórico de tentativas
        $attemptsRepo = new EvaluationAttemptsRepository();
        $this->data['assignment'] = $assignment;
        $this->data['tentativas'] = $attemptsRepo->getDetailedHistory($assignmentId);
        $this->data['melhor_tentativa'] = $attemptsRepo->getBestAttempt($assignmentId);

        // Calcular estatísticas
        if (!empty($this->data['tentativas'])) {
            $notas = array_column($this->data['tentativas'], 'nota_obtida');
            $this->data['estatisticas'] = [
                'total_tentativas' => count($this->data['tentativas']),
                'melhor_nota' => max($notas),
                'pior_nota' => min($notas),
                'media_notas' => array_sum($notas) / count($notas),
                'aprovacoes' => count(array_filter($this->data['tentativas'], function($t) use ($assignment) {
                    return $t['nota_obtida'] >= $assignment['nota_minima_aprovacao'];
                })),
                'reprovacoes' => count(array_filter($this->data['tentativas'], function($t) use ($assignment) {
                    return $t['nota_obtida'] < $assignment['nota_minima_aprovacao'];
                }))
            ];
        }

        // Layout
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Histórico de Tentativas',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]));

        $loadView = new LoadViewService('adms/Views/evaluations/answer/history', $this->data);
        $loadView->loadView();
    }
}

