<?php

namespace App\adms\Controllers\customer;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Clientes
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Clientes no sistema. Utiliza um repositório
 * para obter dados dos Clientes e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\customer
 * @author Rafael Mendes
 */
class ListCustomers
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar Clientes com paginação.
     * 
     * Este método recupera os Clientes a partir do repositório de Clientes com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Clientes.
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
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listCustomers = new CustomerRepository();
        $totalCustomers = $listCustomers->getAmountCustomers();
        $this->data['customers'] = $listCustomers->getAllCustomers((int) $page, (int) $this->limitResult);
        $pagination = PaginationService::generatePagination((int) $totalCustomers, (int) $this->limitResult, (int) $page, 'list-customers', ['per_page' => $this->limitResult]);
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Clientes',
            'menu' => 'list-customers',
            'buttonPermission' => ['CreateCustomer', 'ViewCustomer', 'UpdateCustomer', 'DeleteCustomer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/customer/list", $this->data);
        $loadView->loadView();
    }
}
