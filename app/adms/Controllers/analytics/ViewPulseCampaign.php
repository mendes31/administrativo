<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Models\Repository\PulseQuestionsRepository;
use App\adms\Models\Repository\PulseResponsesRepository;
use App\adms\Models\Services\PulseCampaignService;
use App\adms\Views\Services\LoadViewService;

class ViewPulseCampaign
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $cid = (int) $id;
        $campaign = (new PulseCampaignsRepository())->getById($cid);
        if (!$campaign) {
            $_SESSION['error'] = 'Campanha não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pulse-campaigns');
            exit;
        }

        $service = new PulseCampaignService();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_question') {
            if (!CSRFHelper::validateCSRFToken('form_add_pulse_question', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $r = $service->addQuestion($cid, $_POST);
                $_SESSION[$r['ok'] ? 'msg' : 'error'] = $r['ok']
                    ? '<div class="alert alert-success">Pergunta adicionada.</div>'
                    : ($r['error'] ?? 'Erro');
                header('Location: ' . $_ENV['URL_ADM'] . 'view-pulse-campaign/' . $cid);
                exit;
            }
        }

        $this->data['campaign'] = $campaign;
        $this->data['questions'] = (new PulseQuestionsRepository())->listByCampaign($cid);
        $this->data['responses_count'] = (new PulseResponsesRepository())->countByCampaign($cid);
        $this->data['enps'] = $service->computeEnps($cid);

        $pageElements = [
            'title_head' => 'Pesquisa Pulse/eNPS',
            'menu' => 'list-pulse-campaigns',
            'buttonPermission' => ['ListPulseCampaigns', 'UpdatePulseCampaign', 'RespondPulseCampaign'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/view_pulse_campaign', $this->data))->loadView();
    }
}
