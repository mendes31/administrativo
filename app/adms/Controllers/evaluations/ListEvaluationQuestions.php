<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável em listar perguntas de avaliação.
 *
 * Esta classe gerencia a listagem de perguntas de avaliação, incluindo paginação, filtros e 
 * validação de permissões de acesso. Ela utiliza o `EvaluationQuestionsRepository` para buscar 
 * dados do banco e o `LoadViewService` para carregar as views.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ListEvaluationQuestions
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 20;

    /**
     * Método principal que executa a listagem de perguntas de avaliação.
     *
     * Este método verifica permissões, processa filtros de pesquisa, busca dados paginados
     * e carrega a view correspondente.
     *
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Recupera os parâmetros de busca enviados por GET (se houver)
        $searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
        $modelId = filter_input(INPUT_GET, 'model_id', FILTER_VALIDATE_INT);
        $tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);

        // Configura os critérios de busca
        $criteria = [];

        if ($searchTerm) {
            $criteria['search'] = $searchTerm;
        }
        if ($modelId) {
            $criteria['model_id'] = $modelId;
        }
        if ($tipo) {
            $criteria['tipo'] = $tipo;
        }

        // Instanciar o Repository para recuperar os registros do banco de dados
        $questionsRepository = new EvaluationQuestionsRepository();
        $modelsRepository = new EvaluationModelsRepository();

        // Recuperar as perguntas para a página atual com filtros
        $this->data['questions'] = $questionsRepository->getAllQuestions($criteria, (int) $page, (int) $this->limitResult);

        // Buscar modelos para o filtro
        $this->data['models'] = $modelsRepository->getAllModels();

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $questionsRepository->getAmountQuestions($criteria),
            (int) $this->limitResult,
            (int) $page,
            'list-evaluation-questions',
            $criteria
        );

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Listar Perguntas de Avaliação',
            'menu' => 'list-evaluation-questions',
            'buttonPermission' => ['CreateEvaluationQuestion', 'ViewEvaluationQuestion', 'UpdateEvaluationQuestion', 'DeleteEvaluationQuestion'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Enviar também os filtros para a view
        $this->data['criteria'] = $criteria;

        // Adicionar $this->data['access_level'] = 1; antes de carregar a view
        $this->data['access_level'] = 1;
        $loadView = new LoadViewService("adms/Views/evaluations/questions/list", $this->data);
        $loadView->loadView();
    }
} 