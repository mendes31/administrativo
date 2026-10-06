<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\TiRustdeskSecretService;
use App\adms\Views\Services\LoadViewService;

final class TiRustdesk
{
    private array $data = [];
    private int $limitResult = 24;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [12, 24, 48, 96], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filter = trim((string) ($_GET['q'] ?? ''));
        $filterStatus = trim((string) ($_GET['status'] ?? 'ativo'));
        $filterDeptRaw = trim((string) ($_GET['departamento'] ?? ''));
        $filterDept = null;
        if ($filterDeptRaw === '0') {
            $filterDept = 0;
        } elseif ($filterDeptRaw !== '' && ctype_digit($filterDeptRaw)) {
            $filterDept = (int) $filterDeptRaw;
        }

        $repo = new TiRustdeskRepository();
        $total = $repo->countAll($filter, $filterStatus, $filterDept);
        $this->data['registros'] = $repo->getAll((int) $page, $this->limitResult, $filter, $filterStatus, $filterDept);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'ti-rustdesk',
            [
                'per_page' => $this->limitResult,
                'q' => $filter,
                'status' => $filterStatus,
                'departamento' => $filterDeptRaw,
            ]
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_q'] = $filter;
        $this->data['filter_status'] = $filterStatus;
        $this->data['filter_departamento'] = $filterDeptRaw;
        $this->data['departamentos'] = $repo->getDepartmentsSelect();
        $this->data['encryption_ok'] = TiRustdeskSecretService::isConfigured();
        $this->data['csrf_reveal'] = CSRFHelper::generateCSRFToken('form_ti_rustdesk_reveal');

        $pageElements = [
            'title_head' => 'RustDesk (TI)',
            'menu' => 'ti-rustdesk',
            'buttonPermission' => [
                'TiRustdeskCreate',
                'TiRustdeskView',
                'TiRustdeskUpdate',
                'TiRustdeskReveal',
                'ImportCenterTi',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/rustdesk/list', $this->data))->loadView();
    }
}
