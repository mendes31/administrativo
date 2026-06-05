<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryLaborRole
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $rid = (int) $id;

        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_inventory_labor_role', $this->data['form']['csrf_token'])) {
            $this->save($rid);
            return;
        }

        $repo = new InvLaborRolesRepository();
        $this->data['form'] = $repo->getOne($rid);
        if (!$this->data['form']) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Papel não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            return;
        }
        $this->view($rid);
    }

    private function view(int $id): void
    {
        $positionsRepo = new PositionsRepository();
        $this->data['listPositions'] = $positionsRepo->getAllPositionsSelect();
        $returnUrl = $_ENV['URL_ADM'] . 'update-inventory-labor-role/' . $id;
        $this->data['log_resumo'] = LogResumoService::getResumo('inv_labor_roles', $id, $returnUrl);

        $pageElements = [
            'title_head' => 'Editar Papel de MO',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryLaborRoles'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/labor_roles/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Nome é obrigatório.</div>";
            $this->view($id);
            return;
        }

        $repo = new InvLaborRolesRepository();
        $ok = $repo->update($id, [
            'code' => trim((string) ($form['code'] ?? '')),
            'name' => trim((string) $form['name']),
            'default_cost_per_min' => (float) ($form['default_cost_per_min'] ?? 0),
            'adms_position_id' => !empty($form['adms_position_id']) ? (int) $form['adms_position_id'] : null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($ok) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Papel atualizado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar.</div>";
        $this->view($id);
    }
}
