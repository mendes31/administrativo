<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar respostas de avaliação com filtros, exportação e visualização detalhada.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ListEvaluationAnswers
{
    /** @var array|string|null $data Dados enviados para a VIEW */
    private array|string|null $data = null;
    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        // Filtros
        $usuarioId = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
        $modeloId = filter_input(INPUT_GET, 'modelo_id', FILTER_VALIDATE_INT);
        $perguntaId = filter_input(INPUT_GET, 'pergunta_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
        $dataIni = filter_input(INPUT_GET, 'data_ini', FILTER_SANITIZE_SPECIAL_CHARS);
        $dataFim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_SPECIAL_CHARS);
        $export = filter_input(INPUT_GET, 'export', FILTER_SANITIZE_SPECIAL_CHARS);

        $criteria = [];
        if ($usuarioId) $criteria['usuario_id'] = $usuarioId;
        if ($modeloId) $criteria['modelo_id'] = $modeloId;
        if ($perguntaId) $criteria['pergunta_id'] = $perguntaId;
        if ($status) $criteria['status'] = $status;
        if ($dataIni) $criteria['data_ini'] = $dataIni;
        if ($dataFim) $criteria['data_fim'] = $dataFim;

        $answersRepository = new EvaluationAnswersRepository();
        $modelsRepository = new EvaluationModelsRepository();
        $questionsRepository = new EvaluationQuestionsRepository();

        // Exportação CSV
        if ($export === 'csv') {
            $answers = $answersRepository->exportAnswersCSV($criteria);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="respostas_avaliacao.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Usuário', 'Modelo', 'Pergunta', 'Resposta', 'Pontuação', 'Comentário', 'Status', 'Data']);
            foreach ($answers as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['usuario'],
                    $row['modelo'],
                    $row['pergunta'],
                    $row['resposta'],
                    $row['pontuacao'],
                    $row['comentario'],
                    $row['status'],
                    $row['created_at'],
                ]);
            }
            fclose($output);
            exit;
        }

        // Listagem paginada
        $this->data['answers'] = $answersRepository->getAllAnswers($criteria, (int)$page, $this->limitResult);
        $this->data['models'] = $modelsRepository->getAllModels();
        $this->data['questions'] = $questionsRepository->getAllQuestions();
        $this->data['pagination'] = PaginationService::generatePagination(
            (int)$answersRepository->getAmountAnswers($criteria),
            (int)$this->limitResult,
            (int)$page,
            'list-evaluation-answers',
            $criteria
        );
        $this->data['criteria'] = $criteria;

        // Elementos de página e permissões
        $pageElements = [
            'title_head' => 'Listar Respostas de Avaliação',
            'menu' => 'list-evaluation-answers',
            'buttonPermission' => ['ViewEvaluationAnswer', 'DeleteEvaluationAnswer', 'CreateEvaluationAnswer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $this->data['access_level'] = 1;
        $loadView = new LoadViewService('adms/Views/evaluations/answers/list', $this->data);
        $loadView->loadView();
    }

    /**
     * Visualização detalhada de uma resposta
     */
    public function view(int $id): void
    {
        $answersRepository = new EvaluationAnswersRepository();
        $answer = $answersRepository->getAnswerDetail($id);
        if (!$answer) {
            header('Location: ' . getenv('URL_ADM') . 'list-evaluation-answers/index');
            exit;
        }
        $this->data['answer'] = $answer;
        $pageElements = [
            'title_head' => 'Visualizar Resposta de Avaliação',
            'menu' => 'list-evaluation-answers',
            'buttonPermission' => ['ListEvaluationAnswers', 'DeleteEvaluationAnswer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/evaluations/answers/view', $this->data);
        $loadView->loadView();
    }
} 