<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstRiscoCargoRepository;
use App\adms\Models\Repository\SstRiscoEpiRepository;
use App\adms\Models\Repository\SstRiscoExameRepository;
use App\adms\Models\Repository\SstRiscoTreinamentoRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
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
        $this->data['cargosVinculados'] = (new SstRiscoCargoRepository())->getAllByRisco($itemId);
        $this->data['examesVinculadosRows'] = (new SstRiscoExameRepository())->getAllByRisco($itemId);
        $epiRows = (new SstRiscoEpiRepository())->getAllByRisco($itemId);
        $episVinculadosMap = [];
        foreach ($epiRows as $row) {
            $epiId = (int) ($row['adms_sst_epi_id'] ?? 0);
            if ($epiId > 0) {
                $episVinculadosMap[$epiId] = ['obrigatorio' => !empty($row['obrigatorio'])];
            }
        }
        $this->data['episVinculadosMap'] = $episVinculadosMap;

        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $treinamentoRows = (new SstRiscoTreinamentoRepository())->getAllByRisco($itemId);
        $treinamentosVinculadosMap = [];
        foreach ($treinamentoRows as $row) {
            $tid = (int) ($row['adms_sst_treinamento_id'] ?? 0);
            if ($tid > 0) {
                $treinamentosVinculadosMap[$tid] = [
                    'obrigatorio' => !empty($row['obrigatorio']),
                    'validade_meses' => $row['validade_meses'] ?? null,
                ];
            }
        }
        $this->data['treinamentosVinculadosMap'] = $treinamentosVinculadosMap;

        $pageElements = [
            'title_head' => 'Visualizar Risco - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => [
                'SstViewRisco', 'SstUpdateRisco', 'SstDeleteRisco', 'SstSaveRiscoRelacionamentos', 'SstSaveRiscoTreinamentos',
                'SstCreateRiscoCargo', 'SstUpdateRiscoCargo', 'SstDeleteRiscoCargo',
                'SstCreateRiscoExame', 'SstUpdateRiscoExame', 'SstDeleteRiscoExame',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos/view', $this->data))->loadView();
    }
}
