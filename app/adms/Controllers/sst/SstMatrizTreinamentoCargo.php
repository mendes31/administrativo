<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\SstTreinamentoNecessidadeRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstMatrizTreinamentoCargo
{
    private array $data = [];

    public function index(string|int|null $positionId = null): void
    {
        $repo = new SstTreinamentoNecessidadeRepository();
        $positionId = (int) ($positionId ?: ($_GET['adms_position_id'] ?? 0));
        $departmentId = (int) ($_GET['adms_department_id'] ?? 0) ?: null;

        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['resumo_cargos'] = $repo->getResumoMatrizPorCargo();
        $this->data['position_id'] = $positionId;
        $this->data['department_id'] = $departmentId;
        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);

        $vinculadosMap = [];
        if ($positionId > 0) {
            foreach ($repo->getMatrizByPosition($positionId, $departmentId) as $row) {
                $tid = (int) ($row['adms_sst_treinamento_id'] ?? 0);
                if ($tid > 0) {
                    $vinculadosMap[$tid] = true;
                }
            }
        }
        $this->data['vinculadosMap'] = $vinculadosMap;

        $pageElements = [
            'title_head' => 'Matriz Treinamentos por Cargo',
            'menu' => 'sst-matriz-treinamento-cargo',
            'buttonPermission' => [
                'SstMatrizTreinamentoCargo',
                'SstSaveMatrizTreinamentoCargo',
                'SstSyncTreinamentoVinculos',
                'SstListTreinamentoNecessidade',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_necessidade/matriz', $this->data))->loadView();
    }
}
