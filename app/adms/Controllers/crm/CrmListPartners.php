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
        // Usar CrmPermissionService para verificar hierarquia
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $isManager = $permissionService::isManager();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        // Obter IDs permitidos (usuário + subordinados do departamento comercial)
        $allowedUserIds = $permissionService::getAllowedUserIds();
        
        // Capturar filtros
        $filters = [
            'search' => $_GET['search'] ?? '',
            'segment' => $_GET['segment'] ?? '',
            'partner_type' => $_GET['partner_type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'tag_id' => $_GET['tag_id'] ?? ''
        ];
        
        // APLICAR FILTRO AUTOMÁTICO POR HIERARQUIA
        if (!$isSuperAdmin) {
            // Se filtrou por um usuário específico, validar se tem permissão
            if (!empty($filters['responsible_user_id'])) {
                if (!in_array($filters['responsible_user_id'], $allowedUserIds)) {
                    // Usuário sem permissão - resetar filtro e mostrar alerta
                    $filters['responsible_user_id'] = '';
                    $_SESSION['msg'] = "Você não tem permissão para visualizar este usuário.";
                    $_SESSION['msg_type'] = "warning";
                }
            }
            
            // FILTRO AUTOMÁTICO: Se não filtrou por usuário específico, aplicar filtro por IDs permitidos
            if (empty($filters['responsible_user_id']) && !empty($allowedUserIds)) {
                $filters['allowed_user_ids'] = $allowedUserIds; // Array de IDs permitidos
            }
        }

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
        
        // OTIMIZADO: Buscar todas as tags de uma vez (resolve N+1)
        $tagsRepo = new CrmTagsRepository();
        if (!empty($this->data['partners'])) {
            $partnerIds = array_column($this->data['partners'], 'id');
            $allTags = $tagsRepo->getPartnersTags($partnerIds);
            
            // Associar tags aos parceiros
            foreach ($this->data['partners'] as &$partner) {
                $partner['tags'] = $allTags[$partner['id']] ?? [];
            }
            unset($partner);
        }
        
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
        
        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();
        
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

