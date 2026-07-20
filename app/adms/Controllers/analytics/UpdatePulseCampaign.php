<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Models\Services\PulseCampaignService;
use App\adms\Views\Services\LoadViewService;

class UpdatePulseCampaign
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $cid = (int) $id;
        $repo = new PulseCampaignsRepository();
        $campaign = $repo->getById($cid);
        if (!$campaign) {
            $_SESSION['error'] = 'Campanha não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pulse-campaigns');
            exit;
        }
        $this->data['campaign'] = $campaign;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_update_pulse_campaign', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $r = (new PulseCampaignService())->update($cid, $_POST);
                if ($r['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Campanha atualizada.</div>';
                    GenerateLog::generateLog('info', 'Campanha pulse atualizada.', ['id' => $cid]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-pulse-campaign/' . $cid);
                    exit;
                }
                $_SESSION['error'] = $r['error'] ?? 'Erro.';
            }
            $this->data['campaign'] = $repo->getById($cid) ?? $campaign;
        }

        $pageElements = [
            'title_head' => 'Editar Pesquisa Pulse/eNPS',
            'menu' => 'list-pulse-campaigns',
            'buttonPermission' => ['ListPulseCampaigns', 'ViewPulseCampaign'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/update_pulse_campaign', $this->data))->loadView();
    }
}
