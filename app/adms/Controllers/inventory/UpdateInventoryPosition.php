<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvPositionsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryPosition
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new InvPositionsRepository();
        $posId = (int)($id ?: ($_GET['id'] ?? 0));
        if (!$posId) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-positions'); return; }
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_position', $this->data['form']['csrf_token'])) {
            $this->save($posId, $repo);
            return;
        }
        $stocksRepo = new InvStocksRepository();
        $this->data['stocks'] = $stocksRepo->getAllForSelect();
        $this->data['position'] = $repo->getOne($posId);
        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Posição Interna',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryPositions'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/positions/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id, InvPositionsRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['inv_stock_id']) || empty($form['code']) || empty($form['description'])) { $_SESSION['msg'] = "<div class='alert alert-danger'>Preencha todos os campos.</div>"; $this->view(); return; }
        if ($repo->update($id, ['inv_stock_id' => (int)$form['inv_stock_id'], 'code' => trim($form['code']), 'description' => trim($form['description'])])) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Posição atualizada.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-positions'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar posição.</div>"; $this->view();
    }
}








