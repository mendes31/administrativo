<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstGheColaboradoresRepository;
use App\adms\Models\Repository\SstGheEpisRepository;
use App\adms\Models\Repository\SstGheRepository;
use App\adms\Models\Repository\SstGheTreinamentosRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\SstEpisObrigatoriosResolver;
use App\adms\Views\Services\LoadViewService;

class SstViewGhe
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $gheId = (int) $id;
        $item = (new SstGheRepository())->getById($gheId);
        if (!$item) {
            $_SESSION['msg'] = 'GHE não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ghe');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_ghe', $gheId, $_ENV['URL_ADM'] . 'sst-view-ghe/' . $gheId);
        $this->data['colaboradores_vinculados'] = (new SstGheColaboradoresRepository())->getAtivosByGheId($gheId);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $treinamentoRepoGhe = new SstGheTreinamentosRepository();
        $treinamentoRepoGhe->promoverVinculosSemFlagParaObrigatorio();
        $treinamentoRows = $treinamentoRepoGhe->getAllByGhe($gheId);
        $map = [];
        foreach ($treinamentoRows as $row) {
            $tid = (int) ($row['adms_sst_treinamento_id'] ?? 0);
            if ($tid > 0) {
                $map[$tid] = [
                    'obrigatorio' => !empty($row['obrigatorio']),
                    'validade_meses' => $row['validade_meses'] ?? null,
                ];
            }
        }
        $this->data['treinamentosVinculadosMap'] = $map;

        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $epiRows = (new SstGheEpisRepository())->getAllByGhe($gheId);
        $epiMap = [];
        foreach ($epiRows as $row) {
            $eid = (int) ($row['adms_sst_epi_id'] ?? 0);
            if ($eid > 0) {
                $epiMap[$eid] = [
                    'obrigatorio' => !empty($row['obrigatorio']),
                ];
            }
        }
        $this->data['episVinculadosMap'] = $epiMap;

        $episJaNoCargo = [];
        $resolverEpi = new SstEpisObrigatoriosResolver();
        foreach ($this->data['colaboradores_vinculados'] as $colab) {
            $uid = (int) ($colab['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($resolverEpi->cargoEpiIdsForUser($uid) as $epiId) {
                $episJaNoCargo[$epiId] = true;
            }
        }
        $this->data['episJaNoCargoIds'] = $episJaNoCargo;

        $pageElements = [
            'title_head' => 'GHE — ' . ($item['nome'] ?? ''),
            'menu' => 'sst-list-ghe',
            'buttonPermission' => [
                'SstViewGhe', 'SstUpdateGhe', 'SstDeleteGhe', 'SstSaveGheRelacionamentos',
                'SstSyncTreinamentoVinculos',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ghe/view', $this->data))->loadView();
    }
}
