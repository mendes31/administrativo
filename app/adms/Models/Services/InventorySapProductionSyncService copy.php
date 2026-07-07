<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostProductionBatchesRepository;
use App\adms\Models\Repository\inventory\InvCostProductionSyncRunsRepository;
use App\adms\Models\Repository\inventory\InvCostProductionWarehousesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use Throwable;

class InventorySapProductionSyncService
{
    /**
     * @param list<string>|null $warehouseCodes
     * @return array{success: bool, message: string, inserted: int, updated: int, skipped: int, sync_run_id: int}
     */
    public function syncFull(?array $warehouseCodes = null, bool $incremental = false): array
    {
        $stats = [
            'success' => false,
            'message' => '',
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'sync_run_id' => 0,
        ];

        $warehousesRepo = new InvCostProductionWarehousesRepository();
        $codes = $this->normalizeWarehouseCodes($warehouseCodes ?? $warehousesRepo->getCodesForSync());
        if ($codes === []) {
            $stats['message'] = 'Nenhum depósito configurado para sincronização. Cadastre TJQP/APQP em inv_cost_production_warehouses.';
            return $stats;
        }

        $runsRepo = new InvCostProductionSyncRunsRepository();
        $batchesRepo = new InvCostProductionBatchesRepository();
        $filterFromDate = null;
        if ($incremental) {
            $filterFromDate = $batchesRepo->getMaxProductionDate();
        }

        $runId = $runsRepo->create([
            'source' => 'SAP',
            'sync_mode' => $incremental ? 'incremental' : 'full',
            'warehouse_codes_synced' => json_encode($codes, JSON_UNESCAPED_UNICODE),
            'filter_from_date' => $filterFromDate,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);
        $stats['sync_run_id'] = $runId;

        try {
            $sap = new SapReportApiService();
            $rows = $this->extractSapRows($sap->execute($this->getProductionQuery($codes, $filterFromDate)));

            $itemsRepo = new InvItemsRepository();
            $importedBy = (int)($_SESSION['user_id'] ?? 0) ?: null;
            $now = date('Y-m-d H:i:s');

            foreach ($rows as $row) {
                $mapped = $this->mapSapRow($row);
                if ($mapped === null) {
                    $stats['skipped']++;
                    continue;
                }

                $erpCode = $mapped['erp_code'];
                $item = $itemsRepo->findByErpCode($erpCode);
                $mapped['inv_item_id'] = $item !== null ? (int)$item['id'] : null;
                $mapped['sap_sync_run_id'] = $runId;
                $mapped['imported_by'] = $importedBy;
                $mapped['imported_at'] = $now;
                $mapped['source'] = 'SAP';

                $existing = $batchesRepo->findByNaturalKey(
                    $mapped['base_entry'],
                    $mapped['batch_number'],
                    $mapped['erp_code'],
                    $mapped['goods_receipt_doc_num'],
                    $mapped['warehouse_code']
                );

                if ($existing !== null) {
                    if ($batchesRepo->update((int)$existing['id'], $mapped)) {
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                    continue;
                }

                if ($batchesRepo->insert($mapped) > 0) {
                    $stats['inserted']++;
                } else {
                    $stats['skipped']++;
                }
            }

            $runsRepo->update($runId, [
                'rows_inserted' => $stats['inserted'],
                'rows_updated' => $stats['updated'],
                'rows_skipped' => $stats['skipped'],
                'status' => 'completed',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            $stats['success'] = true;
            $stats['message'] = sprintf(
                'Sincronização concluída. Inseridos: %d | Atualizados: %d | Ignorados: %d | Depósitos: %s%s.',
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped'],
                implode(', ', $codes),
                $incremental && $filterFromDate ? " (incremental desde {$filterFromDate})" : ''
            );
        } catch (Throwable $e) {
            $runsRepo->update($runId, [
                'rows_inserted' => $stats['inserted'],
                'rows_updated' => $stats['updated'],
                'rows_skipped' => $stats['skipped'],
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $stats['message'] = 'Falha na sincronização de lotes produzidos: ' . $e->getMessage();
        }

        return $stats;
    }

    /**
     * @param list<string> $warehouseCodes
     */
    private function getProductionQuery(array $warehouseCodes, ?string $sinceDate = null): string
    {
        $quoted = array_map(static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'", $warehouseCodes);
        $inList = implode(', ', $quoted);
        $sinceClause = '';
        if ($sinceDate !== null && $sinceDate !== '') {
            $since = str_replace("'", "''", $sinceDate);
            $sinceClause = " AND T0.\"InDate\" >= '{$since}'";
        }

        return <<<SQL
SELECT
    T1."BaseNum" AS goods_receipt_doc_num,
    T0."InDate" AS production_date,
    T1."DocDate" AS doc_date,
    T3."Remark" AS series_remark,
    T0."ItemCode" AS erp_code,
    T2."ItemName" AS item_description,
    T1."Quantity" AS quantity,
    T0."DistNumber" AS batch_number,
    T0."MnfDate" AS mnf_date,
    T0."ExpDate" AS exp_date,
    T1."BaseType" AS base_type,
    T1."BaseEntry" AS base_entry,
    T1."WhsCode" AS warehouse_code,
    W."WhsName" AS warehouse_name
FROM "OBTN" T0
INNER JOIN "IBT1" T1 ON T0."ItemCode" = T1."ItemCode" AND T0."DistNumber" = T1."BatchNum"
INNER JOIN "OITM" T2 ON T0."ItemCode" = T2."ItemCode"
INNER JOIN "NNM1" T3 ON T2."Series" = T3."Series"
LEFT JOIN "OWHS" W ON T1."WhsCode" = W."WhsCode"
WHERE T1."ItemCode" LIKE '4%'
  AND T1."WhsCode" IN ({$inList})
  AND T1."BaseType" = 59
  {$sinceClause}
SQL;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function mapSapRow(array $row): ?array
    {
        $erpCode = trim((string)($row['erp_code'] ?? $row['ERP_CODE'] ?? $row['ItemCode'] ?? ''));
        $batchNumber = trim((string)($row['batch_number'] ?? $row['BATCH_NUMBER'] ?? $row['DistNumber'] ?? ''));
        $warehouseCode = trim((string)($row['warehouse_code'] ?? $row['WAREHOUSE_CODE'] ?? $row['WhsCode'] ?? ''));
        $productionDate = $this->normalizeDate($row['production_date'] ?? $row['PRODUCTION_DATE'] ?? $row['InDate'] ?? null);

        if ($erpCode === '' || $batchNumber === '' || $warehouseCode === '' || $productionDate === null) {
            return null;
        }

        $quantity = $this->normalizeDecimal($row['quantity'] ?? $row['QUANTITY'] ?? $row['Quantity'] ?? 0);
        if ($quantity <= 0) {
            return null;
        }

        return [
            'erp_code' => $erpCode,
            'item_description' => trim((string)($row['item_description'] ?? $row['ITEM_DESCRIPTION'] ?? $row['ItemName'] ?? '')) ?: null,
            'series_remark' => trim((string)($row['series_remark'] ?? $row['SERIES_REMARK'] ?? '')) ?: null,
            'production_date' => $productionDate,
            'doc_date' => $this->normalizeDate($row['doc_date'] ?? $row['DOC_DATE'] ?? $row['DocDate'] ?? null),
            'goods_receipt_doc_num' => $this->normalizeInt($row['goods_receipt_doc_num'] ?? $row['GOODS_RECEIPT_DOC_NUM'] ?? $row['BaseNum'] ?? null),
            'production_order_num' => $this->normalizeInt($row['production_order_num'] ?? $row['PRODUCTION_ORDER_NUM'] ?? null),
            'base_type' => $this->normalizeInt($row['base_type'] ?? $row['BASE_TYPE'] ?? $row['BaseType'] ?? null),
            'base_entry' => $this->normalizeInt($row['base_entry'] ?? $row['BASE_ENTRY'] ?? $row['BaseEntry'] ?? null),
            'batch_number' => $batchNumber,
            'mnf_date' => $this->normalizeDate($row['mnf_date'] ?? $row['MNF_DATE'] ?? $row['MnfDate'] ?? null),
            'exp_date' => $this->normalizeDate($row['exp_date'] ?? $row['EXP_DATE'] ?? $row['ExpDate'] ?? null),
            'warehouse_code' => $warehouseCode,
            'warehouse_name' => trim((string)($row['warehouse_name'] ?? $row['WAREHOUSE_NAME'] ?? $row['WhsName'] ?? '')) ?: null,
            'quantity' => $quantity,
        ];
    }

    /**
     * @param list<string>|null $codes
     * @return list<string>
     */
    private function normalizeWarehouseCodes(?array $codes): array
    {
        if ($codes === null) {
            return [];
        }

        $normalized = [];
        foreach ($codes as $code) {
            $value = strtoupper(trim((string)$code));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, mixed> $response
     * @return list<array<string, mixed>>
     */
    private function extractSapRows(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return array_values($response['data']);
        }
        if (isset($response[0]) && is_array($response[0])) {
            return array_values($response);
        }

        return [];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $string = trim((string)$value);
        if ($string === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            return substr($string, 0, 10);
        }
        $timestamp = strtotime($string);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function normalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }

    private function normalizeDecimal(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float)$value, 6);
    }
}
