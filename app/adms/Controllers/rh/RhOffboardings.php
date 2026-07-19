<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\RhOffboardingRepository;
use App\adms\Views\Services\LoadViewService;

final class RhOffboardings
{
    private array $data = [];

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
        ];

        $repo = new RhOffboardingRepository();
        $result = $repo->list($filters, $page, 20);

        $this->data = [
            'title_head' => 'Offboarding',
            'menu' => 'rh-offboardings',
            'buttonPermission' => ['RhOffboardings', 'RhOffboardingsCreate', 'RhOffboardingsView'],
            'planos' => $result['data'],
            'filters' => $filters,
            'tipos' => RhOffboardingRepository::TIPOS,
            'pagination' => PaginationService::generatePagination(
                $result['total'],
                20,
                $page,
                'rh-offboardings',
                array_filter($filters, static fn ($v) => $v !== '' && $v !== null)
            ),
        ];
        $this->data['paginator'] = $this->data['pagination']['html'] ?? '';

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/offboarding/list', $this->data))->loadView();
    }
}
