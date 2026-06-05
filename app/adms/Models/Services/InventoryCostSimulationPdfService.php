<?php

namespace App\adms\Models\Services;

use Dompdf\Dompdf;

class InventoryCostSimulationPdfService
{
    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $breakdown
     * @param array<string, float> $scenario
     */
    public static function streamPdf(
        array $item,
        array $breakdown,
        array $scenario,
        string $title = 'Simulação de Custos',
        ?string $savedAt = null
    ): void {
        $html = self::buildHtml($item, $breakdown, $scenario, $title, $savedAt);
        $code = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)($item['code'] ?? 'item')) ?: 'item';
        $filename = 'simulacao_custo_' . $code . '_' . date('Y-m-d_His') . '.pdf';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $breakdown
     * @param array<string, float> $scenario
     */
    public static function buildHtml(
        array $item,
        array $breakdown,
        array $scenario,
        string $title = 'Simulação de Custos',
        ?string $savedAt = null
    ): string {
        $fmt = static fn(float $v): string => number_format($v, 4, ',', '.');
        $fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
        $batchSize = (float)($breakdown['standard_batch_size'] ?? 1);
        $baseUnit = (float)($breakdown['base_total'] ?? 0);
        $baseBatch = (float)($breakdown['base_total_batch'] ?? ($baseUnit * $batchSize));
        $simUnit = (float)($breakdown['simulated_total'] ?? 0);
        $simBatch = (float)($breakdown['simulated_total_batch'] ?? ($simUnit * $batchSize));
        $diffUnit = $simUnit - $baseUnit;
        $diffBatch = $simBatch - $baseBatch;

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; }
            h1 { font-size: 16px; margin: 0 0 4px; }
            h2 { font-size: 11px; margin: 14px 0 6px; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
            .meta { font-size: 8px; color: #555; margin-bottom: 10px; }
            .cards { width: 100%; margin-bottom: 10px; }
            .cards td { width: 33%; vertical-align: top; padding: 6px; border: 1px solid #ddd; }
            .cards .label { font-size: 7px; text-transform: uppercase; color: #777; }
            .cards .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
            .cards .sub { font-size: 7px; color: #666; margin-top: 3px; }
            table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
            table.data th, table.data td { border: 1px solid #ccc; padding: 3px 4px; }
            table.data th { background: #e8e8e8; font-size: 7px; text-align: left; }
            table.data td.num { text-align: right; white-space: nowrap; }
            .small { font-size: 7px; color: #666; }
        </style></head><body>';

        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="meta">';
        $html .= 'Item: <strong>' . htmlspecialchars(trim(($item['code'] ?? '') . ' — ' . ($item['description'] ?? ''))) . '</strong><br>';
        if (!empty($item['erp_code'])) {
            $html .= 'ERP: ' . htmlspecialchars((string)$item['erp_code']) . ' · ';
        }
        $html .= 'Unidade: ' . htmlspecialchars((string)($item['unit_name'] ?? '—'));
        $html .= ' · Lote: ' . $fmt($batchSize) . ' un.';
        $html .= ' · Gerado em ' . ($savedAt ? htmlspecialchars($savedAt) : date('d/m/Y H:i'));
        $html .= '<br>Cenário: Mat. ' . $fmtPct((float)($scenario['material_adjust_pct'] ?? 0)) . '% · Rota '
            . $fmtPct((float)($scenario['operations_adjust_pct'] ?? 0)) . '% · Global '
            . $fmtPct((float)($scenario['global_adjust_pct'] ?? 0)) . '%';
        $html .= '</div>';

        $html .= '<table class="cards"><tr>';
        $html .= '<td><div class="label">Custo base / SKU</div><div class="value">R$ ' . $fmt($baseUnit) . '</div>'
            . '<div class="sub">Lote: R$ ' . $fmt($baseBatch) . '</div></td>';
        $html .= '<td><div class="label">Custo simulado / SKU</div><div class="value">R$ ' . $fmt($simUnit) . '</div>'
            . '<div class="sub">Lote: R$ ' . $fmt($simBatch) . '</div></td>';
        $html .= '<td><div class="label">Variação / SKU</div><div class="value">R$ ' . $fmt($diffUnit) . '</div>'
            . '<div class="sub">Lote: R$ ' . $fmt($diffBatch) . '</div></td>';
        $html .= '</tr></table>';

        $html .= '<h2>Lista de materiais</h2>';
        $html .= '<table class="data"><thead><tr>
            <th>Componente</th><th>Grupo</th><th class="num">Qtd/lote</th>
            <th class="num">C. comp./un.</th><th class="num">C. SKU</th><th class="num">C. lote</th>
        </tr></thead><tbody>';
        $materials = $breakdown['materials'] ?? [];
        if ($materials === []) {
            $html .= '<tr><td colspan="6" class="small">Sem componentes na BOM.</td></tr>';
        } else {
            foreach ($materials as $line) {
                $lineUnit = (float)($line['line_cost'] ?? 0);
                $lineBatch = (float)($line['line_cost_batch'] ?? ($lineUnit * $batchSize));
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string)($line['component_description'] ?? $line['component_code'] ?? '')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($line['group_name'] ?? 'Outros')) . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['effective_qty'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['unit_cost'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . $fmt($lineUnit) . '</td>';
                $html .= '<td class="num">' . $fmt($lineBatch) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Rota de produção</h2>';
        $html .= '<table class="data"><thead><tr>
            <th>Operação</th><th class="num">Min</th><th class="num">HH</th>
            <th class="num">MO SAP SKU</th><th class="num">Equip. SKU</th><th class="num">MO cad. SKU</th>
            <th class="num">C. SKU</th><th class="num">C. lote</th>
        </tr></thead><tbody>';
        $operations = $breakdown['operations'] ?? [];
        if ($operations === []) {
            $html .= '<tr><td colspan="8" class="small">Sem operações na rota.</td></tr>';
        } else {
            foreach ($operations as $line) {
                $lineUnit = (float)($line['line_cost'] ?? 0);
                $lineBatch = (float)($line['line_cost_batch'] ?? ($lineUnit * $batchSize));
                $opName = (string)($line['operation_name'] ?? $line['operation_code'] ?? '');
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($opName) . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['time_minutes'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . number_format((float)($line['labor_hours'] ?? 0), 2, ',', '.') . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['sap_labor_line_cost'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['equipment_line_cost'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . $fmt((float)($line['manual_labor_line_cost'] ?? 0)) . '</td>';
                $html .= '<td class="num">' . $fmt($lineUnit) . '</td>';
                $html .= '<td class="num">' . $fmt($lineBatch) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></body></html>';

        return $html;
    }
}
