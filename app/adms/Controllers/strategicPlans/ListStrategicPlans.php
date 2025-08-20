<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Planos Estratégicos
 * @package App\adms\Controllers\strategicPlans
 */
class ListStrategicPlans
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 20;

    /**
     * Recuperar e listar Planos Estratégicos com paginação e busca.
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

        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        // Recupera os parâmetros de busca enviados por GET (se houver)
        $titulo = filter_input(INPUT_GET, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
        $departamento = filter_input(INPUT_GET, 'departamento', FILTER_SANITIZE_SPECIAL_CHARS);
        $responsavel = filter_input(INPUT_GET, 'responsavel', FILTER_SANITIZE_SPECIAL_CHARS);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

        // Configura os critérios de busca
        $criteria = [];
        if ($titulo) {
            $criteria['titulo'] = $titulo;
        }
        if ($departamento) {
            $criteria['departamento'] = $departamento;
        }
        if ($responsavel) {
            $criteria['responsavel'] = $responsavel;
        }
        if ($status) {
            $criteria['status'] = $status;
        }

        // Instancia o repositório
        $plansRepo = new StrategicPlansRepository();

        // Busca os planos estratégicos com os critérios e paginação
        $this->data['plans'] = $plansRepo->getAllStrategicPlans(
            $criteria,
            (int) $page,
            (int) $this->limitResult,
        );
        // Total de planos estratégicos (ajustado para considerar a busca com filtros)
        $total = $plansRepo->getAmountStrategicPlans($criteria);

        // Gera a paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $total,
            (int) $this->limitResult,
            (int) $page,
            'list-strategic-plans',
            array_merge($criteria, ['per_page' => $this->limitResult])
        );

        // Define dados da página (título, menu ativo, permissões de botões)
        $pageElements = [
            'title_head' => 'Listar Planos Estratégicos',
            'menu' => 'list-strategic-plans',
            'buttonPermission' => ['CreateStrategicPlan', 'ViewStrategicPlan', 'UpdateStrategicPlan', 'DeleteStrategicPlan'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Envia também os filtros para a view
        $this->data['criteria'] = $criteria;
        $this->data['per_page'] = $this->limitResult;

        // Carrega a VIEW
        $loadView = new LoadViewService("adms/Views/strategicPlans/list", $this->data);
        $loadView->loadView();
    }
} 