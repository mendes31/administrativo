<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEpiFichas
{
    private array $data = [];

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'adms_user_id' => (int) ($_GET['adms_user_id'] ?? 0) ?: null,
            'status_assinatura' => trim((string) ($_GET['status_assinatura'] ?? '')) ?: null,
        ];
        $repo = new SstEpiFichasRepository();
        $this->data['items'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->getTotal($filters);
        $this->data['pagination'] = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
        $this->data['filters'] = $filters;
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $pageElements = [
            'title_head' => 'Fichas de entrega EPI - SST',
            'menu' => 'sst-list-epi-fichas',
            'buttonPermission' => ['SstListEpiFichas', 'SstCreateEpiFicha', 'SstViewEpiFicha'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_fichas/list', $this->data))->loadView();
    }
}
