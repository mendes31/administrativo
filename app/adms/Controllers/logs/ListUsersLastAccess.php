<?php

declare(strict_types=1);

namespace App\adms\Controllers\logs;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Views\Services\LoadViewService;

class ListUsersLastAccess
{
    private array|string|null $data = null;

    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $allowedPerPage = [10, 20, 50, 100];
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $allowedPerPage, true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filtros = [
            'usuario_nome' => trim((string) ($_GET['usuario_nome'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'apenas_nunca' => ($_GET['apenas_nunca'] ?? '') === '1' ? '1' : '',
            'sort' => ($_GET['sort'] ?? '') === 'ultimo_login' ? 'ultimo_login' : 'nome',
        ];

        $repo = new LogAcessosRepository();
        $total = $repo->countUsersLastLogin($filtros);
        $this->data['users'] = $repo->listUsersLastLogin((int) $page, $this->limitResult, $filtros);
        $this->data['total'] = $total;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;
        $this->data['current_user_id'] = (int) ($_SESSION['user_id'] ?? 0);
        $this->data['consulted_at'] = date('d/m/Y H:i:s');
        $this->data['total_nunca'] = $repo->countUsersLastLogin(array_merge($filtros, ['apenas_nunca' => '1']));
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'list-users-last-access',
            array_filter([
                'per_page' => $this->limitResult,
                'usuario_nome' => $filtros['usuario_nome'],
                'status' => $filtros['status'],
                'apenas_nunca' => $filtros['apenas_nunca'],
                'sort' => $filtros['sort'],
            ], static fn ($v) => $v !== '' && $v !== null)
        );

        $pageElements = [
            'title_head' => 'Último acesso por usuário',
            'menu' => 'list-users-last-access',
            'buttonPermission' => ['ListUsersLastAccess', 'ExportUsersLastAccessPdf', 'ExportUsersLastAccessExcel'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/logs/listUsersLastAccess', $this->data);
        $loadView->loadView();
    }
}
