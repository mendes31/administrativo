<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstTreinamentoAplicacoesRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewTreinamentoVinculo
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
            exit;
        }
        $repo = new SstTreinamentoVinculosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_treinamento_vinculos', $itemId, $returnUrl);
        $this->data['aplicacoes'] = (new SstTreinamentoAplicacoesRepository())->getByVinculoId($itemId);
        $pageElements = [
            'title_head' => 'Vínculo Treinamento SST',
            'menu' => 'sst-list-treinamento-vinculos',
            'buttonPermission' => ['SstViewTreinamentoVinculo', 'SstApplyTreinamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_vinculos/view', $this->data))->loadView();
    }
}
