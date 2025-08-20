<?php

namespace App\adms\Controllers\accountsPlan;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Planos de Conta
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Planos de Conta no sistema. Utiliza um repositório
 * para obter dados dos Planos de Conta e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\accountsPlan
 * @author Rafael Mendes
 */
class ListAccountsPlan
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar Planos de Conta com paginação.
     * 
     * Este método recupera os Planos de Conta a partir do repositório de Planos de Conta com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Planos de Conta.
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
        // Filtros de busca
        $filtros = [
            'name' => $_GET['name'] ?? '',
            'code' => $_GET['code'] ?? '',
            'type' => $_GET['type'] ?? '',
        ];
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listAccountPlan = new AccountPlanRepository();
        $this->data['accountsPlan'] = $listAccountPlan->getAllAccountsPlan((int) $page, (int) $this->limitResult, $filtros);
        $totalAccountsPlan = $listAccountPlan->getAmountAccountsPlan($filtros);
        $pagination = PaginationService::generatePagination((int) $totalAccountsPlan, (int) $this->limitResult, (int) $page, 'list-accounts-plan', array_merge($filtros, ['per_page' => $this->limitResult]));
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Planos de Conta',
            'menu' => 'list-accounts-plan',
            'buttonPermission' => ['CreateAccountPlan', 'ViewAccountPlan', 'UpdateAccountPlan', 'DeleteAccountPlan'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/accountsPlan/list", $this->data);
        $loadView->loadView();
    }
}
