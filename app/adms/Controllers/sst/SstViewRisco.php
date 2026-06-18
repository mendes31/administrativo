<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstRiscoEpiRepository;
use App\adms\Models\Repository\SstRiscoExameRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewRisco
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }
        $repo = new SstRiscosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-risco/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_riscos', $itemId, $returnUrl);

        $this->data['exames'] = (new SstExamesRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $this->data['examesVinculados'] = (new SstRiscoExameRepository())->getExameIdsByRisco($itemId);
        $this->data['episVinculados'] = (new SstRiscoEpiRepository())->getEpiIdsByRisco($itemId);

        $pageElements = [
            'title_head' => 'Visualizar Risco - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => [
                'SstViewRisco', 'SstUpdateRisco', 'SstDeleteRisco', 'SstSaveRiscoRelacionamentos',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos/view', $this->data))->loadView();
    }
}
