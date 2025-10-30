<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Parceiros CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListPartners
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        // Capturar filtros
        $filters = [
            'search' => $_GET['search'] ?? '',
            'segment' => $_GET['segment'] ?? '',
            'partner_type' => $_GET['partner_type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'tag_id' => $_GET['tag_id'] ?? ''
        ];

        // Paginação
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        // Repository
        $partnersRepo = new CrmPartnersRepository();
        $totalPartners = $partnersRepo->getAmountPartners($filters);
        $this->data['partners'] = $partnersRepo->getAllPartners((int)$page, (int)$this->limitResult, $filters);
        
        // Carregar tags de cada parceiro
        $tagsRepo = new CrmTagsRepository();
        foreach ($this->data['partners'] as &$partner) {
            $partner['tags'] = $tagsRepo->getPartnerTags($partner['id']);
        }
        unset($partner); // Limpar referência
        
        // Paginação
        $pagination = PaginationService::generatePagination(
            (int)$totalPartners,
            (int)$this->limitResult,
            (int)$page,
            'crm-list-partners',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        // Dados para filtros
        $this->data['segments'] = ['Farma', 'Suplementos', 'Ambos'];
        $this->data['partner_types'] = ['Lead', 'Cliente', 'Prospect'];
        $this->data['statuses'] = ['Ativo', 'Inativo', 'Bloqueado'];
        
        // Tags para filtro
        $tagsRepo = new CrmTagsRepository();
        $this->data['all_tags'] = $tagsRepo->getAllTags();
        
        // Usuários e departamentos para filtros
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        // Layout
        $pageElements = [
            'title_head' => 'Gestão de Parceiros - CRM',
            'menu' => 'crm-list-partners',
            'buttonPermission' => ['CrmCreatePartner', 'CrmViewPartner', 'CrmUpdatePartner', 'CrmDeletePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar VIEW
        $loadView = new LoadViewService("adms/Views/crm/partners/list", $this->data);
        $loadView->loadView();
    }
}

