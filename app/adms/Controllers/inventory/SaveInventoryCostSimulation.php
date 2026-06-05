<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostSimulationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventoryCostService;

class SaveInventoryCostSimulation
{
    public function index(int|string $itemId = 0): void
    {
        $itemId = (int)$itemId;
        if ($itemId <= 0) {
            $_SESSION['error'] = 'Item inválido para salvar simulação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId);
            return;
        }

        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (empty($form['csrf_token']) || !CSRFHelper::validateCSRFToken('form_save_inventory_cost_simulation', (string)$form['csrf_token'])) {
            $_SESSION['error'] = 'Sessão expirada. Recarregue a página e tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId);
            return;
        }

        $title = trim((string)($form['simulation_title'] ?? ''));
        if ($title === '') {
            $title = 'Simulação ' . date('d/m/Y H:i');
        }

        $itemsRepo = new InvItemsRepository();
        $item = $itemsRepo->getOne($itemId);
        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $scenario = [
            'material_adjust_pct' => $this->parsePct($form['material_adjust_pct'] ?? '0'),
            'operations_adjust_pct' => $this->parsePct($form['operations_adjust_pct'] ?? '0'),
            'global_adjust_pct' => $this->parsePct($form['global_adjust_pct'] ?? '0'),
            'standard_batch_size' => $this->parseBatchSize($form['standard_batch_size'] ?? '1'),
        ];
        $breakdown = InventoryCostService::calculateBreakdown($itemId, $scenario);

        $simRepo = new InvCostSimulationsRepository();
        $newId = $simRepo->create([
            'inv_item_id' => $itemId,
            'title' => mb_substr($title, 0, 150),
            'material_adjust_pct' => $scenario['material_adjust_pct'],
            'operations_adjust_pct' => $scenario['operations_adjust_pct'],
            'global_adjust_pct' => $scenario['global_adjust_pct'],
            'standard_batch_size' => (float)($breakdown['standard_batch_size'] ?? 1),
            'base_total_unit' => (float)($breakdown['base_total'] ?? 0),
            'base_total_batch' => (float)($breakdown['base_total_batch'] ?? 0),
            'simulated_total_unit' => (float)($breakdown['simulated_total'] ?? 0),
            'simulated_total_batch' => (float)($breakdown['simulated_total_batch'] ?? 0),
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'created_by' => (int)($_SESSION['user_id'] ?? 0) ?: null,
        ]);

        if ($newId <= 0) {
            $_SESSION['error'] = 'Não foi possível salvar a simulação.';
        } else {
            $_SESSION['success'] = 'Simulação salva com sucesso.';
        }

        $query = http_build_query([
            'material_adjust_pct' => $scenario['material_adjust_pct'],
            'operations_adjust_pct' => $scenario['operations_adjust_pct'],
            'global_adjust_pct' => $scenario['global_adjust_pct'],
            'standard_batch_size' => $scenario['standard_batch_size'],
            'saved' => $newId > 0 ? $newId : null,
        ]);
        header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId . '?' . $query);
    }

    private function parsePct(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float)$value, 4);
    }

    private function parseBatchSize(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 1.0;
        }
        $batch = (float)$value;

        return $batch > 0 ? round($batch, 6) : 1.0;
    }
}
