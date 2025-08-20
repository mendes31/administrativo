<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Modelos de Avaliação
 *
 * Esta classe é responsável por recuperar e exibir uma lista de modelos de avaliação no sistema. 
 * Utiliza um repositório para obter dados dos modelos e um serviço de paginação para gerenciar 
 * a navegação entre páginas de resultados.
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ListEvaluationModels
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 20;

    /**
     * Recuperar e listar modelos de avaliação com paginação.
     * 
     * Este método recupera os modelos de avaliação a partir do repositório com base na página atual 
     * e no limite de registros por página. Gera os dados de paginação e carrega a visualização.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Recupera os parâmetros de busca enviados por GET (se houver)
        $searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
        $trainingId = filter_input(INPUT_GET, 'training_id', FILTER_VALIDATE_INT);
        $active = filter_input(INPUT_GET, 'active', FILTER_VALIDATE_INT);

        // Configura os critérios de busca
        $criteria = [];

        if ($searchTerm) {
            $criteria['search'] = $searchTerm;
        }
        if ($trainingId) {
            $criteria['training_id'] = $trainingId;
        }
        if ($active !== null) {
            $criteria['ativo'] = $active;
        }

        // Instanciar o Repository para recuperar os registros do banco de dados
        $listModels = new EvaluationModelsRepository();

        // Recuperar os modelos para a página atual com filtros
        $this->data['models'] = $listModels->getAllModels($criteria, (int) $page, (int) $this->limitResult);

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $listModels->getAmountModels($criteria),
            (int) $this->limitResult,
            (int) $page,
            'list-evaluation-models',
            $criteria
        );

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Listar Modelos de Avaliação',
            'menu' => 'list-evaluation-models',
            'buttonPermission' => ['CreateEvaluationModel', 'ViewEvaluationModel', 'UpdateEvaluationModel', 'DeleteEvaluationModel'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Enviar também os filtros para a view
        $this->data['criteria'] = $criteria;

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/evaluations/models/list", $this->data);
        $loadView->loadView();
    }
} 