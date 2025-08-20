<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\MovBetweenAccountsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável por listar as transferências entre contas.
 *
 * @package App\adms\Controllers
 * @author Rafael Mendes
 */
class ListMovBetweenAccounts
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Ajuste conforme necessário

    /**
     * Recuperar e listar Bancos com paginação.
     * 
     * Este método recupera os Bancos a partir do repositório de Bancos com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Bancos.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Receber filtros do GET
        $filterFrom = $_GET['from_bank_name'] ?? '';
        $filterTo = $_GET['to_bank_name'] ?? '';
        $filterDescription = $_GET['description'] ?? '';
        $filterUser = $_GET['user_name'] ?? '';
        $filterDate = $_GET['created_at'] ?? '';
        $perPage = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $this->limitResult = $perPage;

        // Repositório para buscar transferências
        $listMovBetweenAccounts = new MovBetweenAccountsRepository();

        // Recuperar transferências para a página atual com filtros
        $this->data['movBetweenAccounts'] = $listMovBetweenAccounts->getAllMovBetweenAccounts(
            (int) $page,
            (int) $this->limitResult,
            $filterFrom,
            $filterTo,
            $filterDescription,
            $filterUser,
            $filterDate
        );

        // Gerar dados de paginação com filtros
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $listMovBetweenAccounts->getAmountMovBetweenAccounts(
                $filterFrom,
                $filterTo,
                $filterDescription,
                $filterUser,
                $filterDate
            ),
            (int) $this->limitResult,
            (int) $page,
            'list-mov-between-accounts'
        );

        // Manter filtros preenchidos na view
        $this->data['filters'] = [
            'from_bank_name' => $filterFrom,
            'to_bank_name' => $filterTo,
            'description' => $filterDescription,
            'user_name' => $filterUser,
            'created_at' => $filterDate,
            'per_page' => $perPage,
        ];

        $pageElements = [
            'title_head' => 'Listar Transferências entre Contas',
            'menu' => 'list-mov-between-accounts',
            'buttonPermission' => ['UpdateMovBetweenAccounts', 'DeleteMovBetweenAccounts', 'MovBetweenAccounts','ViewMovBetweenAccounts','ViewTransfer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/banks/listMovBetweenAccounts", $this->data);
        $loadView->loadView();
    }
}
