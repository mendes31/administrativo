<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Views\Services\LoadViewService;

class ListInformativos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1)
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        
        // Garantir que a página seja sempre pelo menos 1
        $page = max(1, (int)$page);
        
        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
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
        
        $repo = new InformativosRepository();

        // Permissões para decidir escopo de listagem
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['CreateInformativo','UpdateInformativo']);
        $isEditor = is_array($perms) && count($perms) > 0;

        if (!$isEditor) {
            // Usuário comum: apenas ativos e dentro da janela de publicação
            $filters['ativo'] = '1';
            $filters['apenas_janela_publicacao'] = true;
        }

        $this->data['informativos'] = $repo->getAllInformativos((int)$page, (int)$this->limitResult, $filters);
        $totalInformativos = $repo->getTotalInformativos($filters);

        // IDs não lidos (mesma regra do sino) para exibir badge "Novo" / "Ciência pendente" na lista
        $informativoIds = array_map(static fn ($i) => (int)($i['id'] ?? 0), $this->data['informativos'] ?? []);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $this->data['unreadInformativoIds'] = $userId > 0 ? $repo->getNaoLidosIdsByInformativoIds($userId, $informativoIds) : [];

        // Ordenação: não lidos / sem ciência primeiro, depois por data descrescente.
        $unreadInformativoIdSet = array_fill_keys($this->data['unreadInformativoIds'] ?? [], true);
        if (!empty($this->data['informativos'])) {
            usort($this->data['informativos'], static function (array $a, array $b) use ($unreadInformativoIdSet): int {
                $aId = (int)($a['id'] ?? 0);
                $bId = (int)($b['id'] ?? 0);

                $aUnread = $aId > 0 && isset($unreadInformativoIdSet[$aId]);
                $bUnread = $bId > 0 && isset($unreadInformativoIdSet[$bId]);

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
            (int) $totalInformativos,
            (int) $this->limitResult,
            (int) $page,
            'list-informativos',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['categorias'] = $repo->getCategorias();
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['filters'] = $filters;
        $this->data['isEditor'] = $isEditor;
        
        $pageElements = [
            'title_head' => 'Listar Informativos',
            'menu' => 'list-informativos',
            'buttonPermission' => ['CreateInformativo', 'ViewInformativo', 'UpdateInformativo', 'DeleteInformativo', 'RelatorioInformativo'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/informativos/list', $this->data);
        $loadView->loadView();
    }
} 