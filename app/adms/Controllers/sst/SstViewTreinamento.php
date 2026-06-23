<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstRiscoTreinamentoRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewTreinamento
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamentos');
            exit;
        }
        $repo = new SstTreinamentosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamentos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-treinamento/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_treinamentos', $itemId, $returnUrl);
        $this->data['riscosRelacionados'] = (new SstRiscoTreinamentoRepository())->getRiscosByTreinamentoId($itemId);
        $cfg = require dirname(__DIR__, 4) . '/scripts/sst_entities_config.php';
        $this->data['entity'] = $cfg['treinamentos'];
        $pageElements = [
            'title_head' => 'Visualizar Treinamento SST',
            'menu' => 'sst-list-treinamentos',
            'buttonPermission' => ['SstViewTreinamento', 'SstUpdateTreinamento', 'SstDeleteTreinamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamentos/view', $this->data))->loadView();
    }
}
