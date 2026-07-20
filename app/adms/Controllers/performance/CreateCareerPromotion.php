<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\CareerService;
use App\adms\Views\Services\LoadViewService;

class CreateCareerPromotion
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_create_career_promotion', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new CareerService())->createPromotion($_POST, (int) ($_SESSION['user_id'] ?? 0));
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Promoção registrada!</div>';
                    GenerateLog::generateLog('info', 'Promoção criada.', ['id' => $result['id']]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-career-promotion/' . $result['id']);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro.';
                $this->data['form'] = $_POST;
            }
        }
        $this->data['employees'] = (new UsersRepository())->getAllUsers(1, 1000);
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['tracks'] = (new CareerTracksRepository())->listActive();
        $this->data['form'] ??= [
            'user_id' => '', 'from_position_id' => '', 'to_position_id' => '',
            'career_track_id' => '', 'effective_date' => date('Y-m-d'), 'notes' => '',
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Registrar Promoção',
            'menu' => 'list-career-promotions',
            'buttonPermission' => ['ListCareerPromotions'],
        ]));
        (new LoadViewService('adms/Views/performance/create_career_promotion', $this->data))->loadView();
    }
}
