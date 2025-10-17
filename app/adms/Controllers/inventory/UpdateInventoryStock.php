<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryStock
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new InvStocksRepository();
        $stockId = (int)($id ?: ($_GET['id'] ?? 0));
        if (!$stockId) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks'); return; }
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_stock', $this->data['form']['csrf_token'])) {
            $this->save($stockId, $repo);
            return;
        }
        $this->data['stock'] = $repo->getOne($stockId);
        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryStocks'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/stocks/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id, InvStocksRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name']) || empty($form['code'])) { $_SESSION['msg'] = "<div class='alert alert-danger'>Nome e Código são obrigatórios.</div>"; $this->view(); return; }
        $ok = $repo->update($id, [
            'name' => trim($form['name']),
            'code' => trim($form['code']),
            'adms_branch_id' => !empty($form['adms_branch_id']) ? (int)$form['adms_branch_id'] : null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);
        if ($ok) { $_SESSION['msg'] = "<div class='alert alert-success'>Estoque atualizado.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar estoque.</div>"; $this->view();
    }
}








