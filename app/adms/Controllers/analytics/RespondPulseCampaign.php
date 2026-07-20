<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Models\Repository\PulseQuestionsRepository;
use App\adms\Models\Services\PulseCampaignService;
use App\adms\Views\Services\LoadViewService;

class RespondPulseCampaign
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
        $questions = (new PulseQuestionsRepository())->listByCampaign($cid);
        $this->data['campaign'] = $campaign;
        $this->data['questions'] = $questions;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_respond_pulse_campaign', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $r = (new PulseCampaignService())->submitAnswers(
                    $cid,
                    (int) ($_SESSION['user_id'] ?? 0),
                    $_POST['answers'] ?? []
                );
                if ($r['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Respostas enviadas. Obrigado!</div>';
                    GenerateLog::generateLog('info', 'Resposta pulse enviada.', ['campaign_id' => $cid]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'list-pulse-campaigns');
                    exit;
                }
                $_SESSION['error'] = $r['error'] ?? 'Erro ao responder.';
            }
        }

        $pageElements = [
            'title_head' => 'Responder Pesquisa',
            'menu' => 'list-pulse-campaigns',
            'buttonPermission' => ['ListPulseCampaigns'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/respond_pulse_campaign', $this->data))->loadView();
    }
}
