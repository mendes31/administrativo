<?php

namespace App\adms\Controllers\frequency;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Frequências
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Frequências no sistema. Utiliza um repositório
 * para obter dados dos Frequências e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\frequency
 * @author Rafael Mendes
 */
class ListFrequencies
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar Frequências com paginação.
     * 
     * Este método recupera os Frequências a partir do repositório de Frequências com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Frequências.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }
        // Capturar filtro de nome
        $filterName = isset($_GET['name']) ? trim($_GET['name']) : '';
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listFrequencies = new FrequencyRepository();
        $totalFrequencies = $listFrequencies->getAmountFrequencies($filterName);
        $this->data['frequencies'] = $listFrequencies->getAllFrequencies((int) $page, (int) $this->limitResult, $filterName);
        $pagination = PaginationService::generatePagination((int) $totalFrequencies, (int) $this->limitResult, (int) $page, 'list-frequencies', ['per_page' => $this->limitResult, 'name' => $filterName]);
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_name'] = $filterName;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Frequências',
            'menu' => 'list-frequencies',
            'buttonPermission' => ['CreateFrequency', 'ViewFrequency', 'UpdateFrequency', 'DeleteFrequency'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/frequency/list", $this->data);
        $loadView->loadView();
    }
}
