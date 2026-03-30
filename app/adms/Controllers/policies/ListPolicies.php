<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Views\Services\LoadViewService;

class ListPolicies
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $page = max(1, (int) $page);

        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filters = [
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'ativo' => $_GET['ativo'] ?? '',
            'urgente' => $_GET['urgente'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'busca' => $_GET['busca'] ?? '',
        ];

        $repo = new PoliciesRepository();

        // Permissões para decidir escopo de listagem (editores de políticas internas)
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['CreatePolicy', 'UpdatePolicy']);
        $isEditor = is_array($perms) && count($perms) > 0;

        if (!$isEditor) {
            // Usuário comum: somente políticas ativas e dentro da janela de publicação
            $filters['ativo'] = '1';
            $filters['apenas_janela_publicacao'] = true;
        }

        $this->data['policies'] = $repo->getAllPolicies((int) $page, (int) $this->limitResult, $filters);
        $total = $repo->getTotalPolicies($filters);

        // IDs não lidos (mesma regra do sino) para exibir badge "Novo" / "Ciência pendente" na lista
        $policyIds = array_map(static fn ($p) => (int)($p['id'] ?? 0), $this->data['policies'] ?? []);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $this->data['unreadPolicyIds'] = $userId > 0 ? $repo->getNaoLidosIdsByPolicyIds($userId, $policyIds) : [];

        // Ordenação: não lidas / sem ciência primeiro, depois por data descrescente
        // (mantém coerência entre desktop e mobile).
        $unreadPolicyIdSet = array_fill_keys($this->data['unreadPolicyIds'] ?? [], true);
        if (!empty($this->data['policies'])) {
            usort($this->data['policies'], static function (array $a, array $b) use ($unreadPolicyIdSet): int {
                $aId = (int)($a['id'] ?? 0);
                $bId = (int)($b['id'] ?? 0);

                $aUnread = $aId > 0 && isset($unreadPolicyIdSet[$aId]);
                $bUnread = $bId > 0 && isset($unreadPolicyIdSet[$bId]);

                if ($aUnread !== $bUnread) {
                    return $aUnread ? -1 : 1;
                }

                $aRef = $a['publish_at'] ?? $a['created_at'] ?? null;
                $bRef = $b['publish_at'] ?? $b['created_at'] ?? null;
                $aTs = $aRef ? strtotime((string)$aRef) : 0;
                $bTs = $bRef ? strtotime((string)$bRef) : 0;

                return $bTs <=> $aTs; // desc
            });
        }

        $pagination = PaginationService::generatePagination(
            (int) $total,
            (int) $this->limitResult,
            (int) $page,
            'list-policies',
            array_merge($filters, ['per_page' => $this->limitResult])
        );

        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['categorias'] = $repo->getCategorias();
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['filters'] = $filters;
        $this->data['isEditor'] = $isEditor;

        // Elementos de página + permissões de botões (seguindo padrão de ListInformativos/Users)
        $pageElements = [
            'title_head' => 'Políticas Internas',
            'menu'       => 'gestao_pessoas',
            'buttonPermission' => ['CreatePolicy', 'ViewPolicy', 'UpdatePolicy', 'DeletePolicy', 'RelatorioPolicy'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Garantir que Super Administrador (nível 1) enxergue todas as ações,
        // mesmo se houver alguma inconsistência de configuração.
        if (\App\adms\Helpers\UserAccessHelper::hasFullSystemAccess()) {
            $this->data['buttonPermission'] = ['CreatePolicy', 'ViewPolicy', 'UpdatePolicy', 'DeletePolicy'];
        }

        $loadView = new LoadViewService('adms/Views/policies/list', $this->data);
        $loadView->loadView();
    }
}