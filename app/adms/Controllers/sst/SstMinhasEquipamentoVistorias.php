<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstMinhasEquipamentoVistorias
{
    private array $data = [];
    private int $limit = 20;

    public function index(string|int $page = 1): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $userRow = (new UsersRepository())->getUser($userId);
        $deptId = (is_array($userRow) && !empty($userRow['user_department_id'])) ? (int) $userRow['user_department_id'] : null;

        $filters = [
            'minhas' => true,
            'adms_user_id' => $userId,
            'adms_department_id_user' => $deptId,
            'status_open' => ($_GET['aba'] ?? 'pendentes') !== 'concluidas',
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        if (($_GET['aba'] ?? '') === 'concluidas') {
            unset($filters['status_open']);
            $filters['status'] = 'Concluída';
        }
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $repo = new SstEquipamentoVistoriasRepository();
        $this->data['resumo'] = $repo->countMinhasResumo($userId, $deptId);
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-minhas-equipamento-vistorias', $filters);
        $this->data['filters'] = $filters;
        $pageElements = [
            'title_head' => 'Minhas vistorias - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => ['SstMinhasEquipamentoVistorias', 'SstExecuteEquipamentoVistoria', 'SstScanEquipamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/minhas_vistorias', $this->data))->loadView();
    }
}
