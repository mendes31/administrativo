<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CareerLevelsRepository;
use App\adms\Models\Repository\CareerPromotionsRepository;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\CareerService;
use App\adms\Views\Services\LoadViewService;

class UpdateCareerPromotion
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $pid = (int) $id;
        $repo = new CareerPromotionsRepository();
        $item = $pid > 0 ? $repo->getById($pid) : null;
        if (!$item) {
            $_SESSION['error'] = 'Promoção não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-career-promotions');
            exit;
        }
        $this->data['promotion'] = $item;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_update_career_promotion', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new CareerService())->updatePromotion($pid, $_POST, (int) ($_SESSION['user_id'] ?? 0));
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Promoção atualizada!</div>';
                    GenerateLog::generateLog('info', 'Promoção atualizada.', ['id' => $pid]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-career-promotion/' . $pid);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro.';
            }
            $this->data['promotion'] = $repo->getById($pid) ?? $item;
        }
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['tracks'] = (new CareerTracksRepository())->listActive();
        $trackId = (int) ($this->data['promotion']['career_track_id'] ?? 0);
        $this->data['levels'] = $trackId > 0 ? (new CareerLevelsRepository())->getByTrackId($trackId) : [];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Editar Promoção',
            'menu' => 'list-career-promotions',
            'buttonPermission' => ['ListCareerPromotions', 'ViewCareerPromotion'],
        ]));
        (new LoadViewService('adms/Views/performance/update_career_promotion', $this->data))->loadView();
    }
}
