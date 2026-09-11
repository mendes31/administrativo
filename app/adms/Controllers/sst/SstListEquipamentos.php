<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\SstEquipamentoSiteHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEquipamentos
{
    private array $data = [];
    private int $limit = 15;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'adms_sst_equipamento_tipo_id' => $_GET['tipo_id'] ?? '',
            'adms_department_id' => $_GET['department_id'] ?? '',
            'empresa_contratante' => $_GET['empresa_contratante'] ?? '',
            'recarga_alerta' => !empty($_GET['recarga_alerta']) ? '1' : '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstEquipamentosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-equipamentos', $filters);
        $this->data['filters'] = $filters;
        $this->data['tipos'] = (new SstEquipamentoTiposRepository())->getAllActiveForSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['empresas_contratantes'] = SstEquipamentoSiteHelper::options();
        $pageElements = [
            'title_head' => 'Equipamentos de segurança - SST',
            'menu' => 'sst-list-equipamentos',
            'buttonPermission' => ['SstListEquipamentos', 'SstCreateEquipamento', 'SstViewEquipamento', 'SstUpdateEquipamento', 'SstDeleteEquipamento', 'SstScanEquipamento', 'SstExportEquipamentoAuditoriaPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/list', $this->data))->loadView();
    }
}
