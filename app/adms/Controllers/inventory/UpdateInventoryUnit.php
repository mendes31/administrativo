<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryUnit
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new InvUnitsRepository();
        $unitId = (int)($id ?: ($_GET['id'] ?? 0));
        if (!$unitId) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-units'); return; }

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_unit', $this->data['form']['csrf_token'])) {
            $this->save($unitId, $repo);
            return;
        }
        $this->data['unit'] = $repo->getOne($unitId);
        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Unidade de Medida',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryUnits'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/units/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $unitId, InvUnitsRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['code']) || empty($form['name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Código e Nome são obrigatórios.</div>";
            $this->view();
            return;
        }
        if ($repo->update($unitId, ['code' => trim($form['code']), 'name' => trim($form['name'])])) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Unidade atualizada.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-units');
            exit;
        }
        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar unidade.</div>";
        $this->view();
    }
}








