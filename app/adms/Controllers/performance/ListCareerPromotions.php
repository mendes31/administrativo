<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CareerPromotionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class ListCareerPromotions
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : (is_numeric($page) ? (int) $page : 1);
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_career_promotions_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-career-promotions');
            exit;
        }
        $filters = [];
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int) $_GET['user_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if ($filters !== []) {
            $_SESSION['list_career_promotions_filters'] = $filters;
        } elseif (isset($_SESSION['list_career_promotions_filters'])) {
            $filters = $_SESSION['list_career_promotions_filters'];
        }
        $repo = new CareerPromotionsRepository();
        $this->data['promotions'] = $repo->getAll($filters, $page, 20);
        $total = $repo->count($filters);
        $pag = PaginationService::generatePagination((int) $total, 20, (int) $page, 'list-career-promotions', $filters);
        $this->data['pagination'] = $pag['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['employees'] = (new UsersRepository())->getAllUsers(1, 1000);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Promoções de Carreira',
            'menu' => 'list-career-promotions',
            'buttonPermission' => ['CreateCareerPromotion', 'ViewCareerPromotion', 'UpdateCareerPromotion', 'ListCareerTracks'],
        ]));
        (new LoadViewService('adms/Views/performance/list_career_promotions', $this->data))->loadView();
    }
}
