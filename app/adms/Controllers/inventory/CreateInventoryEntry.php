<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryEntry
{
    private array|string|null $data = null;

    public function index(): void
    {
        // Usa $_POST diretamente para preservar arrays aninhados (items[][])
        $this->data['form'] = !empty($_POST) ? $_POST : filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_entry', $this->data['form']['csrf_token'])) {
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
        // Sequência para exibir no cabeçalho
        $movRepo = new \App\adms\Models\Repository\inventory\InvMovementsRepository();
        $this->data['nextDocId'] = $movRepo->getNextSequence('entry');

        $pageElements = [
            'title_head' => 'Entrada de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryItems'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/movements/entry', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        // Garante leitura de arrays aninhados vindos do POST
        $form = !empty($_POST) ? $_POST : ($this->data['form'] ?? []);
        \App\adms\Helpers\GenerateLog::generateLog('info', 'EntradaPOST', ['form_keys' => array_keys($form), 'items_present' => isset($form['items']), 'raw_items' => $form['items'] ?? null]);
        $items = $form['items'] ?? [];
        if (empty($items)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Preencha pelo menos um item.</div>";
            $this->view();
            return;
        }

        // Normalizar decimais (pt-BR → en-US) e validar estoque/posição por linha
        $normalizedItems = [];
        $lineNumber = 0;
        foreach ($items as $idx => $it) {
            $line = $it;
            // Ignorar linhas totalmente vazias (sem qualquer campo preenchido)
            $rawQty = $line['qty'] ?? '';
            $hasContent = (
                ($line['inv_item_id'] ?? '') !== '' ||
                ($line['to_stock_id'] ?? '') !== '' ||
                ($line['to_position_id'] ?? '') !== '' ||
                ($line['batch_code'] ?? '') !== '' ||
                ($line['serial_list'] ?? '') !== '' ||
                ($rawQty !== '')
            );
            if (!$hasContent) { continue; }
            $lineNumber++;
            // Quantidade e custo
            if (isset($line['qty'])) { $line['qty'] = (float)str_replace(',', '.', (string)$line['qty']); }
            if (isset($line['unit_cost'])) { $line['unit_cost'] = (float)str_replace(',', '.', (string)$line['unit_cost']); }
            if (empty($line['inv_item_id'])) {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Selecione o item na linha " . ($idx + 1) . ".</div>";
                $this->view();
                return;
            }
            if (!isset($line['qty']) || $line['qty'] === '' || (float)$line['qty'] <= 0) {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Informe quantidade válida na linha " . ($lineNumber) . ".</div>";
                $this->view();
                return;
            }
            // Estoque por item é obrigatório
            if (empty($line['to_stock_id'])) {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Informe o estoque destino em todas as linhas.</div>";
                $this->view();
                return;
            }
            $line['to_stock_id'] = (int)$line['to_stock_id'];
            if (isset($line['to_position_id']) && $line['to_position_id'] !== '') { $line['to_position_id'] = (int)$line['to_position_id']; }
            $normalizedItems[] = $line;
        }

        if (empty($normalizedItems)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Inclua pelo menos um item com dados.</div>";
            $this->view();
            return;
        }

        $movement = [
            'type' => 'entry',
            'user_id' => (int)($_SESSION['user_id'] ?? 0),
            'movement_date' => !empty($form['movement_date']) ? ($form['movement_date'] . ' 00:00:00') : null,
            // Estoque será definido por item
            'to_stock_id' => null,
            'to_position_id' => null,
            'reason_id' => !empty($form['reason_id']) ? (int)$form['reason_id'] : null,
            'equipment_id' => !empty($form['equipment_id']) ? (int)$form['equipment_id'] : null,
            'notes' => $form['notes'] ?? null,
        ];

        // Espera itens como [{inv_item_id, qty, unit_cost, to_stock_id, to_position_id, batch_code, expiration_date, serial_list}]
        $repo = new InvMovementsRepository();
        try {
            $repo->registerMovement($movement, $normalizedItems);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Entrada registrada com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'create-inventory-entry');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
            $this->view();
        }
    }
}


