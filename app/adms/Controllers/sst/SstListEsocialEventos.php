<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEsocialEventosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEsocialEventos
{
    private array $data = [];
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'tipo_evento' => $_GET['tipo_evento'] ?? '',
            'status' => $_GET['status'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $repo = new SstEsocialEventosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-esocial-eventos',
            $filters
        );
        $this->data['filters'] = $filters;
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Eventos eSocial SST',
            'menu' => 'sst-list-esocial-eventos',
            'buttonPermission' => [
                'SstListEsocialEventos', 'SstViewEsocialEvento', 'SstGenerateEsocialEvento',
                'SstMarkEsocialEnviado', 'SstSyncEsocialPendentes',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/esocial_eventos/list', $this->data))->loadView();
    }
}
