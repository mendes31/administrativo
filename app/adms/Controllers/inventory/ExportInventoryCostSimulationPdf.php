<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Repository\inventory\InvCostSimulationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventoryCostService;
use App\adms\Models\Services\InventoryCostSimulationPdfService;

class ExportInventoryCostSimulationPdf
{
    public function index(int|string $id = 0): void
    {
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '120');

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Identificador inválido para exportação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $hasLiveScenario = isset($_REQUEST['material_adjust_pct'])
            || isset($_REQUEST['operations_adjust_pct'])
            || isset($_REQUEST['global_adjust_pct'])
            || isset($_REQUEST['standard_batch_size']);

        if ($hasLiveScenario) {
            $this->exportLive($id);
            return;
        }

        $simRepo = new InvCostSimulationsRepository();
        $saved = $simRepo->getOne($id);
        if (is_array($saved)) {
            $this->exportSaved($saved, $simRepo);
            return;
        }

        $this->exportLive($id);
    }

    /**
     * @param array<string, mixed> $saved
     */
    private function exportSaved(array $saved, InvCostSimulationsRepository $simRepo): void
    {
        $breakdown = $simRepo->decodeBreakdown($saved);
        if (!is_array($breakdown)) {
            $_SESSION['error'] = 'Dados da simulação salva estão corrompidos.';
            header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . (int)($saved['inv_item_id'] ?? 0));
            return;
        }

        $item = [
            'code' => $saved['item_code'] ?? '',
            'description' => $saved['item_description'] ?? '',
            'erp_code' => $saved['item_erp_code'] ?? '',
            'unit_name' => $saved['unit_name'] ?? '',
            'category_name' => $saved['category_name'] ?? '',
        ];
        $scenario = [
            'material_adjust_pct' => (float)($saved['material_adjust_pct'] ?? 0),
            'operations_adjust_pct' => (float)($saved['operations_adjust_pct'] ?? 0),
            'global_adjust_pct' => (float)($saved['global_adjust_pct'] ?? 0),
        ];
        $title = (string)($saved['title'] ?? 'Simulação de Custos');
        $savedAt = !empty($saved['created_at']) ? date('d/m/Y H:i', strtotime((string)$saved['created_at'])) : null;

        InventoryCostSimulationPdfService::streamPdf($item, $breakdown, $scenario, $title, $savedAt);
    }

    private function exportLive(int $itemId): void
    {
        $itemsRepo = new InvItemsRepository();
        $item = $itemsRepo->getOne($itemId);
        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $scenario = [
            'material_adjust_pct' => $this->parsePct($_REQUEST['material_adjust_pct'] ?? '0'),
            'operations_adjust_pct' => $this->parsePct($_REQUEST['operations_adjust_pct'] ?? '0'),
            'global_adjust_pct' => $this->parsePct($_REQUEST['global_adjust_pct'] ?? '0'),
            'standard_batch_size' => $this->parseBatchSize($_REQUEST['standard_batch_size'] ?? '1'),
        ];
        $breakdown = InventoryCostService::calculateBreakdown($itemId, $scenario);
        $title = trim((string)($_REQUEST['pdf_title'] ?? ''));
        if ($title === '') {
            $title = 'Simulação de Custos — ' . (string)($item['code'] ?? '');
        }

        InventoryCostSimulationPdfService::streamPdf($item, $breakdown, $scenario, $title);
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
