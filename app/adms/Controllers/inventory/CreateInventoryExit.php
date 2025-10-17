<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryExit
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_exit', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $stocksRepo = new InvStocksRepository();
        $this->data['listStocks'] = $stocksRepo->getAllForSelect();
        $itemsRepo = new InvItemsRepository();
        $this->data['listItems'] = $itemsRepo->getAllForSelectWithAdminType();
        // Próximo número sequencial por tipo (saída)
        $movRepo = new InvMovementsRepository();
        $this->data['nextDocId'] = $movRepo->getNextSequence('exit');

        $pageElements = [
            'title_head' => 'Saída de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryItems'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/movements/exit', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $items = $form['items'] ?? [];
        if (empty($items)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Inclua ao menos um item.</div>";
            $this->view();
            return;
        }

        // Cabeçalho mínimo
        $movement = [
            'type' => 'exit',
            'user_id' => (int)($_SESSION['user_id'] ?? 0),
            'from_stock_id' => null, // definido por linha
            'from_position_id' => null,
            'reason_id' => !empty($form['reason_id']) ? (int)$form['reason_id'] : null,
            'equipment_id' => null, // opcional por linha
            'notes' => $form['notes'] ?? null,
            'movement_date' => !empty($form['movement_date']) ? ($form['movement_date'] . ' 00:00:00') : date('Y-m-d H:i:s'),
        ];

        // Normaliza por-linha
        $normalized = [];
        foreach ($items as $i => $it) {
            if (empty($it['inv_item_id'])) { continue; }
            $n = [
                'inv_item_id' => (int)$it['inv_item_id'],
                'qty' => (float)str_replace(',', '.', (string)($it['qty'] ?? 0)),
                'from_stock_id' => (int)($it['from_stock_id'] ?? 0),
                'from_position_id' => isset($it['from_position_id']) && $it['from_position_id'] !== '' ? (int)$it['from_position_id'] : null,
                'batch_code' => $it['batch_code'] ?? null,
                'expiration_date' => $it['expiration_date'] ?? null,
            ];
            if (!empty($it['equipment_tag'])) {
                $movement['notes'] = trim(($movement['notes'] ?? '') . ' | Equip.: ' . $it['equipment_tag']);
            }
            if ($n['from_stock_id'] === 0) {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Informe o estoque origem na linha " . ($i + 1) . ".</div>";
                $this->view();
                return;
            }
            if ($n['qty'] <= 0) {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Informe quantidade válida na linha " . ($i + 1) . ".</div>";
                $this->view();
                return;
            }
            $normalized[] = $n;
        }

        if (!$normalized) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Inclua ao menos um item válido.</div>";
            $this->view();
            return;
        }

        $repo = new InvMovementsRepository();
        try {
            $repo->registerMovement($movement, $normalized);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Saída registrada com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'create-inventory-exit');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
            $this->view();
        }
    }
}


