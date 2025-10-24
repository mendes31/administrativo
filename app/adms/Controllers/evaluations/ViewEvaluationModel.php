<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

/**
 * Controller para visualizar detalhes de um modelo de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 */
class ViewEvaluationModel
{
    private array $data = [];

    public function index($id = null): void
    {
        // Converter para int se for string
        if (is_string($id)) {
            $id = !empty($id) ? (int)$id : null;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID do modelo não informado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        // Buscar modelo
        $modelsRepo = new EvaluationModelsRepository();
        $model = $modelsRepo->getModel($id);

        if (!$model) {
            $_SESSION['msg'] = 'Modelo não encontrado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        $this->data['model'] = $model;

        // Buscar questões
        $questionsRepo = new EvaluationQuestionsRepository();
        $criteria = ['model_id' => $id];
        $this->data['questions'] = $questionsRepo->getAllQuestions($criteria, 1, 1000); // Todas

        // Calcular total de pontos
        $totalPontos = 0;
        foreach ($this->data['questions'] as $q) {
            $totalPontos += (float)($q['pontos'] ?? 1.00);
        }
        $this->data['total_pontos'] = $totalPontos;

        // Buscar estatísticas de atribuições
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $this->data['estatisticas_status'] = $assignmentsRepo->countByStatus($id);

        // Buscar atribuições recentes
        $this->data['atribuicoes_recentes'] = array_slice(
            $assignmentsRepo->getAssignmentsByModel($id),
            0,
            10
        );

        $pageElements = [
            'title_head' => 'Visualizar Modelo de Avaliação',
            'menu' => 'list-evaluation-models',
            'buttonPermission' => ['UpdateEvaluationModel', 'DeleteEvaluationModel', 'AssignEvaluation'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/evaluations/models/view', $this->data);
        $loadView->loadView();
    }
}
