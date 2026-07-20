<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\PulseCampaignService;
use App\adms\Views\Services\LoadViewService;

class CreatePulseCampaign
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_create_pulse_campaign', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new PulseCampaignService())->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Campanha criada!</div>';
                    GenerateLog::generateLog('info', 'Campanha pulse criada.', ['id' => $result['id']]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-pulse-campaign/' . $result['id']);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro ao criar.';
                $this->data['form'] = $_POST;
            }
        }
        if (!isset($this->data['form'])) {
            $this->data['form'] = ['name' => '', 'campaign_type' => 'enps', 'anonymous' => '1'];
        }
        $pageElements = [
            'title_head' => 'Nova Pesquisa Pulse/eNPS',
            'menu' => 'list-pulse-campaigns',
            'buttonPermission' => ['ListPulseCampaigns'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/create_pulse_campaign', $this->data))->loadView();
    }
}
