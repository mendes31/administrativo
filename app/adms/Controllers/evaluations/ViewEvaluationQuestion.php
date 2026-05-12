<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar detalhes de uma pergunta de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ViewEvaluationQuestion
{
    /** @var array $data Dados que devem ser enviados para a VIEW */
    private array $data = [];

    /**
     * Exibe os detalhes de uma pergunta de avaliação
     *
     * @param int|string|null $id ID da pergunta
     * @return void
     */
    public function index($id = null): void
    {
        // Converter para int se for string
        if (is_string($id)) {
            $id = !empty($id) ? (int)$id : null;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID da pergunta não informado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;
        }

        // Buscar pergunta
        $questionsRepo = new EvaluationQuestionsRepository();
        $question = $questionsRepo->getQuestion($id);

        if (!$question) {
            $_SESSION['msg'] = 'Pergunta não encontrada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;
        }

        $this->data['question'] = $question;

        // Buscar modelo de avaliação relacionado
        $modelsRepo = new EvaluationModelsRepository();
        $model = $modelsRepo->getModel($question['model_id']);
        $this->data['model'] = $model;

        // Processar opções para exibição (se múltipla escolha)
        if ($question['tipo'] === 'multipla_escolha' && !empty($question['opcoes'])) {
            $this->data['opcoes_array'] = explode("\n", $question['opcoes']);
        }

        // Definir tipo legível
        $tiposLegiveis = [
            'texto' => 'Texto Livre',
            'multipla_escolha' => 'Múltipla Escolha',
            'verdadeiro_falso' => 'Verdadeiro ou Falso',
            'numerica' => 'Numérica'
        ];
        $this->data['tipo_legivel'] = $tiposLegiveis[$question['tipo']] ?? $question['tipo'];

        $qid = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'view-evaluation-question/' . $qid;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_evaluation_questions', $qid, $returnUrl);

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Visualizar Pergunta de Avaliação',
            'menu' => 'list-evaluation-questions',
            'buttonPermission' => ['UpdateEvaluationQuestion', 'DeleteEvaluationQuestion', 'ListEvaluationQuestions'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService('adms/Views/evaluations/questions/view', $this->data);
        $loadView->loadView();
    }
}

