<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Views\Services\LoadViewService;

class ListCareerTracks
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : (is_numeric($page) ? (int) $page : 1);
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_career_tracks_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-career-tracks');
            exit;
        }
        $filters = [];
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string) $_GET['search']);
        }
        if ($filters !== []) {
            $_SESSION['list_career_tracks_filters'] = $filters;
        } elseif (isset($_SESSION['list_career_tracks_filters'])) {
            $filters = $_SESSION['list_career_tracks_filters'];
        } else {
            $filters['status'] = 'active';
        }
        $repo = new CareerTracksRepository();
        $this->data['tracks'] = $repo->getAll($filters, $page, 20);
        $total = $repo->count($filters);
        $pag = PaginationService::generatePagination((int) $total, 20, (int) $page, 'list-career-tracks', $filters);
        $this->data['pagination'] = $pag['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Trilhas de Carreira',
            'menu' => 'list-career-tracks',
            'buttonPermission' => ['CreateCareerTrack', 'ViewCareerTrack', 'UpdateCareerTrack', 'ListCareerPromotions'],
        ]));
        (new LoadViewService('adms/Views/performance/list_career_tracks', $this->data))->loadView();
    }
}
