<?php

namespace App\adms\Controllers\supplier;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Fornecedores
 * @package App\adms\Controllers\supplier
 * @author Rafael Mendes
 */
class ListSuppliers
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 20;

    /**
     * Recuperar e listar Fornecedores com paginação e busca.
     *
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        // Atualizar o campo busy e user_temp
        $payRepo = new PaymentsRepository();
        $payRepo->getUserTemp($_SESSION['user_id']); //  ID de usuário que tiver



        if ($payRepo) {
            $payRepo->clearUser($_SESSION['user_id']);
        }


        // Recupera os parâmetros de busca enviados por GET (se houver)
        $searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
        $cardCode = filter_input(INPUT_GET, 'card_code', FILTER_SANITIZE_SPECIAL_CHARS);  // Filtro para código do fornecedor
        $cardName = filter_input(INPUT_GET, 'card_name', FILTER_SANITIZE_SPECIAL_CHARS);  // Filtro para nome do fornecedor
        $active = filter_input(INPUT_GET, 'active', FILTER_VALIDATE_INT);  // Filtro para status ativo

        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        // Configura os critérios de busca
        $criteria = [];

        if ($searchTerm) {
            $criteria['search'] = $searchTerm;
        }
        if ($cardCode) {
            $criteria['card_code'] = $cardCode;
        }
        if ($cardName) {
            $criteria['card_name'] = $cardName;
        }
        if ($active !== null) {
            $criteria['active'] = $active;
        }

        // Instancia o repositório
        $listSuppliers = new SupplierRepository();

        // Busca os fornecedores com os critérios e paginação
        // $this->data['suppliers'] = $listSuppliers->getAllSuppliers(
        //     ['search' => $searchTerm],  // Passa o termo de busca como parte do array
        //     (int) $page,  // Página atual
        //     (int) $this->limitResult,  // Limite de registros por página
        // );
        $this->data['suppliers'] = $listSuppliers->getAllSuppliers(
            $criteria,  // Aqui antes estava passando apenas ['search' => $searchTerm]
            (int) $page,
            (int) $this->limitResult,
        );
        // Total de fornecedores (ajustado para considerar a busca com filtros)
        $total = $listSuppliers->getAmountSuppliers($criteria);

        // Gera a paginação
        // $this->data['pagination'] = PaginationService::generatePagination(
        //     (int) $total,
        //     (int) $this->limitResult,
        //     (int) $page,
        //     'list-suppliers',
        //     ['search' => $searchTerm] // Passa o parâmetro de busca
        // );
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $total,
            (int) $this->limitResult,
            (int) $page,
            'list-suppliers',
            array_merge($criteria, ['per_page' => $this->limitResult])
        );

        // Define dados da página (título, menu ativo, permissões de botões)
        $pageElements = [
            'title_head' => 'Listar Fornecedores',
            'menu' => 'list-suppliers',
            'buttonPermission' => ['CreateSupplier', 'ViewSupplier', 'UpdateSupplier', 'DeleteSupplier'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Envia também os filtros para a view
        $this->data['criteria'] = $criteria;
        $this->data['per_page'] = $this->limitResult;

        // Carrega a VIEW
        $loadView = new LoadViewService("adms/Views/supplier/list", $this->data);
        $loadView->loadView();
    }
}
