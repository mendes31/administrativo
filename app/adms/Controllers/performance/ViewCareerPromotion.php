<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CareerPromotionsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewCareerPromotion
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $pid = (int) $id;
        $item = $pid > 0 ? (new CareerPromotionsRepository())->getById($pid) : null;
        if (!$item) {
            $_SESSION['error'] = 'Promoção não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-career-promotions');
            exit;
        }
        $this->data['promotion'] = $item;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_career_promotions', $pid, $_ENV['URL_ADM'] . 'view-career-promotion/' . $pid);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Visualizar Promoção',
            'menu' => 'list-career-promotions',
            'buttonPermission' => ['ListCareerPromotions', 'UpdateCareerPromotion', 'ListCareerTracks'],
        ]));
        (new LoadViewService('adms/Views/performance/view_career_promotion', $this->data))->loadView();
    }
}
