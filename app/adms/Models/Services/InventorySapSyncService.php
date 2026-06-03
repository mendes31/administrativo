<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use Throwable;

class InventorySapSyncService
{
    /**
     * Sincroniza itens de estoque com SAP e atualiza custos de comprados.
     *
     * @return array{success: bool, message: string, synced: int, created: int, updated: int, recalculated: int}
     */
    public function syncItemsAndCosts(): array
    {
        $stats = [
            'success' => false,
            'message' => '',
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'recalculated' => 0,
        ];

        try {
            $sap = new SapReportApiService();
            $rows = $this->extractSapRows($sap->execute($this->getSapItemsQuery()));

            $itemsRepo = new InvItemsRepository();
            $categoriesRepo = new InvCategoriesRepository();
            $unitsRepo = new InvUnitsRepository();

            $defaultUnitId = $unitsRepo->findIdByCode('UN');
            if ($defaultUnitId === null) {
                $firstUnit = $unitsRepo->getAll(1, 1);
                $defaultUnitId = (int)($firstUnit[0]['id'] ?? 0);
            }

            foreach ($rows as $row) {
                $erpCode = trim((string)($row['ItemCode'] ?? ''));
                $description = trim((string)($row['ItemName'] ?? ''));
                if ($erpCode === '' || $description === '') {
                    continue;
                }

                $categoryName = $this->normalizeCategoryName((string)($row['ItemGroupName'] ?? 'Geral'));
                $categoryId = $categoriesRepo->findOrCreateByName($categoryName);
                if ($categoryId === null) {
                    continue;
                }

                $uomCode = strtoupper(trim((string)($row['InvntryUom'] ?? '')));
                $unitId = $unitsRepo->findIdByCode($uomCode) ?? $defaultUnitId;
                if (!$unitId) {
                    continue;
                }

                $itemType = $this->inferItemTypeFromCategory($categoryName);
                $sapCost = $this->extractSapCost($row);
                $active = strtoupper((string)($row['validFor'] ?? 'Y')) === 'Y' ? 1 : 0;

                $existing = $itemsRepo->findByErpCode($erpCode);
                if ($existing !== null) {
                    $shouldUpdateCost = in_array($itemType, ['MP', 'EMB'], true);
                    $payload = [
                        'code' => (string)($existing['code'] ?? $erpCode),
                        'erp_code' => $erpCode,
                        'description' => $description,
                        'inv_unit_id' => (int)$unitId,
                        'inv_category_id' => (int)$categoryId,
                        'admin_type' => (string)($existing['admin_type'] ?? 'none'),
                        'average_cost' => $shouldUpdateCost ? $sapCost : (float)($existing['average_cost'] ?? 0),
                        'last_cost' => $shouldUpdateCost ? $sapCost : (float)($existing['last_cost'] ?? 0),
                        'min_stock' => (float)($existing['min_stock'] ?? 0),
                        'max_stock' => (float)($existing['max_stock'] ?? 0),
                        'active' => $active,
                    ];
                    if ($itemsRepo->update((int)$existing['id'], $payload)) {
                        $stats['updated']++;
                    }
                    $stats['synced']++;
                    continue;
                }

                $created = $itemsRepo->create([
                    'code' => $erpCode,
                    'erp_code' => $erpCode,
                    'description' => $description,
                    'inv_unit_id' => (int)$unitId,
                    'inv_category_id' => (int)$categoryId,
                    'admin_type' => 'none',
                    'average_cost' => $sapCost,
                    'last_cost' => $sapCost,
                    'min_stock' => 0,
                    'max_stock' => 0,
                    'active' => $active,
                ]);
                if (is_int($created) && $created > 0) {
                    $stats['created']++;
                    $stats['synced']++;
                }
            }

            // Recalcular PA e PI após atualização de custos de comprados
            $recalcIds = $itemsRepo->getIdsByCategoryNameKeywords(['PROD. ACABADO', 'PROD. INTERMED', 'ACABADO', 'INTERMED']);
            foreach ($recalcIds as $itemId) {
                if ($itemId <= 0) {
                    continue;
                }
                InventoryCostService::recalculateStandardCost($itemId);
                $stats['recalculated']++;
            }

            $stats['success'] = true;
            $stats['message'] = "Sincronização concluída. Itens: {$stats['synced']} (novos: {$stats['created']}, atualizados: {$stats['updated']}) | PA/PI recalculados: {$stats['recalculated']}.";
            return $stats;
        } catch (Throwable $e) {
            $stats['message'] = 'Falha na sincronização SAP: ' . $this->formatSyncErrorMessage($e->getMessage());
            return $stats;
        }
    }

    /**
     * Sincroniza estrutura completa (lista + rota) de um item específico.
     *
     * @return array{success: bool, message: string, lines: int, routes: int}
     */
    public function syncItemStructureById(int $invItemId): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'lines' => 0,
            'routes' => 0,
        ];

        try {
            $itemsRepo = new InvItemsRepository();
            $item = $itemsRepo->getOne($invItemId);
            if (!is_array($item)) {
                $result['message'] = 'Item não encontrado para sincronizar estrutura.';
                return $result;
            }

            $parentErpCode = trim((string)($item['erp_code'] ?? ''));
            if ($parentErpCode === '') {
                $result['message'] = 'Item sem Código ERP. Informe o Código ERP antes de sincronizar estrutura.';
                return $result;
            }

            $sap = new SapReportApiService();
            $rows = $this->fetchStructureRowsFromSap($sap, $parentErpCode);

            $categoriesRepo = new InvCategoriesRepository();
            $unitsRepo = new InvUnitsRepository();
            $operationsRepo = new InvOperationsRepository();
            $resourcesRepo = new InvProductionResourcesRepository();
            $defaultUnitId = $unitsRepo->findIdByCode('UN');
            if ($defaultUnitId === null) {
                $firstUnit = $unitsRepo->getAll(1, 1);
                $defaultUnitId = (int)($firstUnit[0]['id'] ?? 0);
            }

            $bomLines = [];
            $routeLines = [];
            foreach ($rows as $row) {
                $tipo = strtoupper(trim((string)($row['tipo'] ?? $row['TIPO'] ?? '')));
                if ($tipo === '') {
                    continue;
                }

                if ($tipo === 'MATERIAIS') {
                    $componentErpCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
                    if ($componentErpCode === '') {
                        continue;
                    }

                    $component = $itemsRepo->findByErpCode($componentErpCode);
                    $sapComponentCost = $this->extractComponentCostFromRow($row);
                    if ($component === null) {
                        $componentId = $this->createLocalItemFromSapMaterialRow(
                            $row,
                            $itemsRepo,
                            $categoriesRepo,
                            $unitsRepo,
                            $defaultUnitId
                        );
                        if ($componentId <= 0) {
                            continue;
                        }
                        $component = $itemsRepo->getOne($componentId);
                    } elseif ($sapComponentCost > 0) {
                        $this->updateItemCostFromSap($itemsRepo, $component, $sapComponentCost);
                        $component = $itemsRepo->getOne((int)($component['id'] ?? 0)) ?: $component;
                    }

                    if (!is_array($component)) {
                        continue;
                    }

                    $qty = $this->toFloat($row['quantidade'] ?? $row['QUANTIDADE'] ?? 0);
                    if ($qty <= 0) {
                        $qty = $this->toFloat($row['menge_verbrauch'] ?? $row['MENGE_VERBRAUCH'] ?? 0);
                    }
                    if ($qty <= 0) {
                        continue;
                    }

                    $bomLines[] = [
                        'component_item_id' => (int)$component['id'],
                        'quantity_per_batch' => $qty,
                        'scrap_percent' => 0.0,
                    ];
                    continue;
                }

                if ($tipo === 'ROTA') {
                    $operationCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
                    $operationName = trim((string)($row['descricao'] ?? $row['DESCRICAO'] ?? ''));
                    if ($operationCode === '' && $operationName === '') {
                        continue;
                    }
                    if ($operationName === '') {
                        $operationName = $operationCode;
                    }
                    if ($operationCode === '') {
                        $operationCode = preg_replace('/\s+/', '_', strtoupper($operationName)) ?? $operationName;
                    }

                    $operation = $operationsRepo->findByCode($operationCode);
                    if ($operation === null) {
                        $operation = $operationsRepo->findByName($operationName);
                    }
                    if ($operation === null) {
                        $newId = $operationsRepo->create([
                            'code' => $operationCode,
                            'name' => $operationName,
                            'description' => 'Importado da rota SAP',
                            'default_cost_per_hour' => 0,
                            'active' => 1,
                        ]);
                        if (!is_int($newId) || $newId <= 0) {
                            continue;
                        }
                        $operation = $operationsRepo->getOne($newId);
                    }
                    if (!is_array($operation)) {
                        continue;
                    }

                    $sequence = (int)$this->toFloat($row['pos_id'] ?? $row['POS_ID'] ?? 0);
                    if ($sequence <= 0) {
                        $sequence = count($routeLines) + 1;
                    }

                    $tempo = $this->resolveRouteTimeMinutes($row);
                    $resource = trim((string)($row['recurso'] ?? $row['RECURSO'] ?? ''));
                    $routeCosts = $resourcesRepo->resolveRouteCosts($resource, $operationName);
                    $routeLines[] = [
                        'inv_operation_id' => (int)($operation['id'] ?? 0),
                        'sequence' => $sequence,
                        'time_per_batch_hours' => max(0, $tempo),
                        'time_unit' => 'MIN',
                        'operators_qty' => 1,
                        'labor_cost_per_min' => $routeCosts['labor_cost_per_min'],
                        'machine_cost_per_min' => $routeCosts['machine_cost_per_min'],
                        'energy_cost_per_min' => $routeCosts['energy_cost_per_min'],
                        'notes' => $resource !== '' ? ('Recurso SAP: ' . $resource) : null,
                    ];
                }
            }

            $bomRepo = new InvItemBomRepository();
            $ok = $bomRepo->replaceForItem($invItemId, $bomLines);
            if (!$ok) {
                $result['message'] = 'Falha ao gravar lista de materiais no banco local.';
                return $result;
            }
            $opsRepo = new InvItemOperationsRepository();
            $okRoute = $opsRepo->replaceForItem($invItemId, $routeLines);
            if (!$okRoute) {
                $result['message'] = 'Falha ao gravar rota no banco local.';
                return $result;
            }

            InventoryCostService::recalculateStandardCost($invItemId);
            $result['success'] = true;
            $result['lines'] = count($bomLines);
            $result['routes'] = count($routeLines);
            $result['message'] = "Estrutura sincronizada com sucesso. Componentes: {$result['lines']} | Operações: {$result['routes']}.";
            return $result;
        } catch (Throwable $e) {
            $result['message'] = 'Falha na sincronização da estrutura SAP: ' . $this->formatSyncErrorMessage($e->getMessage());
            return $result;
        }
    }

    private function formatSyncErrorMessage(string $rawMessage): string
    {
        $message = trim($rawMessage);
        $lower = mb_strtolower($message, 'UTF-8');

        if (
            str_contains($lower, 'could not resolve host') ||
            str_contains($lower, 'getaddrinfo failed') ||
            str_contains($lower, 'name or service not known')
        ) {
            return 'Host da API SAP não foi resolvido. Verifique se o túnel (ngrok/trycloudflare) expirou e atualize a URL em Configurações -> Configuração SAP API.';
        }

        if (
            str_contains($lower, 'failed to connect') ||
            str_contains($lower, 'connection refused') ||
            str_contains($lower, 'timed out')
        ) {
            return 'Não foi possível conectar na API SAP. Confirme se a API está online e se a URL/porta em Configurações -> Configuração SAP API está correta.';
        }

        if (
            str_contains($lower, 'apenas queries select') ||
            str_contains($lower, 'sql deve começar com select')
        ) {
            return 'A API SAP recusou a consulta de estrutura. Atualize o sistema para a versão mais recente e tente novamente. Se o erro persistir, verifique se a API intermediária aceita consultas com UNION ALL.';
        }

        if (
            str_contains($lower, 'invalid number') ||
            str_contains($lower, '__typecast__')
        ) {
            return 'A API SAP não conseguiu converter um valor numérico vazio na estrutura do item. Tente sincronizar novamente; se persistir, revise os tempos/quantidades no BEAS para o item.';
        }

        return $message !== '' ? $message : 'Erro desconhecido ao sincronizar com SAP.';
    }

    private function getSapItemsQuery(): string
    {
        $costSelect = $this->sapCostSelectExpression('T0."ItemCode"', 'T0."DfltWH"');
        $costJoins = $this->sapCostJoinsSql('T0."ItemCode"', 'T0."DfltWH"');

        return 'SELECT '
            . 'T0."ItemCode", '
            . 'T0."ItemName", '
            . 'T0."InvntryUom", '
            . $costSelect . ' AS "AvgPrice", '
            . 'T0."validFor", '
            . 'T1."ItmsGrpNam" AS "ItemGroupName" '
            . 'FROM OITM T0 '
            . 'LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
            . $costJoins
            . 'WHERE T0."frozenFor" = \'N\'';
    }

    private function sapWarehouseCaseExpression(string $defaultWhColumn): string
    {
        return 'CASE '
            . 'WHEN ' . $defaultWhColumn . " = 'TJQP' THEN 'TJQR' "
            . 'WHEN ' . $defaultWhColumn . " = 'APQP' THEN 'APQR' "
            . 'ELSE ' . $defaultWhColumn . ' '
            . 'END';
    }

    private function sapLastCostJoinSql(string $itemCodeColumn): string
    {
        return 'LEFT JOIN ('
            . 'SELECT X."ItemCode", X."LastCost" '
            . 'FROM ('
            . 'SELECT N."ItemCode", '
            . 'CASE WHEN N."InQty" <> 0 THEN N."TransValue" / N."InQty" END AS "LastCost", '
            . 'ROW_NUMBER() OVER (PARTITION BY N."ItemCode" ORDER BY N."DocDate" DESC, N."TransNum" DESC) AS "RN" '
            . 'FROM OINM N '
            . 'WHERE N."InQty" > 0'
            . ') X '
            . 'WHERE X."RN" = 1'
            . ') LC ON LC."ItemCode" = ' . $itemCodeColumn . ' ';
    }

    private function sapOitwJoinSql(string $itemCodeColumn, string $defaultWhColumn): string
    {
        $warehouseCase = $this->sapWarehouseCaseExpression($defaultWhColumn);

        return 'LEFT JOIN OITW W '
            . 'ON W."ItemCode" = ' . $itemCodeColumn . ' '
            . 'AND W."WhsCode" = ' . $warehouseCase;
    }

    private function sapCostJoinsSql(string $itemCodeColumn, string $defaultWhColumn): string
    {
        return $this->sapOitwJoinSql($itemCodeColumn, $defaultWhColumn) . ' '
            . $this->sapLastCostJoinSql($itemCodeColumn);
    }

    /**
     * @param string $itemCodeColumn ex.: S."ART1_ID"
     * @param string $fallbackAvgPriceColumn ex.: T2."AvgPrice"
     */
    private function sapCostSelectExpression(
        string $itemCodeColumn,
        string $defaultWhColumn,
        string $fallbackAvgPriceColumn = ''
    ): string {
        $fallback = $fallbackAvgPriceColumn !== ''
            ? ", {$fallbackAvgPriceColumn}"
            : '';

        return 'TO_DECIMAL(COALESCE(LC."LastCost", W."AvgPrice"' . $fallback . '), 19, 4)';
    }

    /**
     * Busca estrutura em duas consultas simples (BOM + rota), evitando CAST/UNION no HANA.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchStructureRowsFromSap(SapReportApiService $sap, string $parentErpCode): array
    {
        $materialRows = $this->extractSapRows($sap->execute($this->getSapMaterialsQueryByParentErpCode($parentErpCode)));
        $routeRows = $this->extractSapRows($sap->execute($this->getSapRouteQueryByParentErpCode($parentErpCode)));

        $rows = [];
        foreach ($materialRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['tipo'] = 'MATERIAIS';
            $rows[] = $row;
        }
        foreach ($routeRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['tipo'] = 'ROTA';
            $rows[] = $row;
        }

        return $this->sortStructureRows($rows);
    }

    private function getSapMaterialsQueryByParentErpCode(string $parentErpCode): string
    {
        $safeCode = str_replace("'", "''", trim($parentErpCode));
        $costSelect = $this->sapCostSelectExpression('S."ART1_ID"', 'I."DfltWH"', 'T2."AvgPrice"');
        $costJoins = $this->sapCostJoinsSql('S."ART1_ID"', 'I."DfltWH"');

        return 'SELECT '
            . 'S."POS_ID" AS "pos_id", '
            . 'S."ART1_ID" AS "codigo", '
            . 'S."DESCRIPTION" AS "descricao", '
            . 'S."INPUT_QTY" AS "quantidade", '
            . 'S."MENGE_VERBRAUCH" AS "menge_verbrauch", '
            . 'S."INPUT_UNIT" AS "unidade_medida", '
            . 'T2."ItemName" AS "component_item_name", '
            . 'T2."InvntryUom" AS "component_uom", '
            . $costSelect . ' AS "component_avg_price", '
            . 'T3."ItmsGrpNam" AS "component_group_name" '
            . 'FROM BEAS_STL S '
            . 'INNER JOIN BEAS_ITEM_VERSION V ON S."ItemCode" = V."StlItemCode" '
            . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
            . 'AND V."Version" = I."U_beas_ver" '
            . "AND I.\"ItemCode\" = '{$safeCode}' "
            . "AND I.\"validFor\" = 'Y' "
            . 'LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID" '
            . 'LEFT JOIN OITB T3 ON T3."ItmsGrpCod" = T2."ItmsGrpCod" '
            . $costJoins
            . 'WHERE UPPER(S."DESCRIPTION") NOT LIKE \'%GERADOR DE LOTE%\'';
    }

    private function getSapRouteQueryByParentErpCode(string $parentErpCode): string
    {
        $safeCode = str_replace("'", "''", trim($parentErpCode));

        return 'SELECT '
            . 'A."POS_ID" AS "pos_id", '
            . 'A."AG_ID" AS "codigo", '
            . 'A."BEZ" AS "descricao", '
            . 'A."APLATZ_ID" AS "recurso", '
            . 'A."THAPLATZ" AS "tempo_th", '
            . 'A."TNAPLATZ" AS "tempo_tn", '
            . 'A."TEAPLATZ" AS "tempo_te" '
            . 'FROM BEAS_APL A '
            . 'INNER JOIN BEAS_ITEM_VERSION V ON A."ItemCode" = V."RoutingId" '
            . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
            . 'AND V."Version" = I."U_beas_ver" '
            . "AND I.\"ItemCode\" = '{$safeCode}' "
            . "AND I.\"validFor\" = 'Y'";
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveRouteTimeMinutes(array $row): float
    {
        $total = $this->toFloat($row['tempo'] ?? $row['TEMPO'] ?? 0);
        if ($total > 0) {
            return $total;
        }

        return $this->toFloat($row['tempo_th'] ?? $row['THAPLATZ'] ?? 0)
            + $this->toFloat($row['tempo_tn'] ?? $row['TNAPLATZ'] ?? 0)
            + $this->toFloat($row['tempo_te'] ?? $row['TEAPLATZ'] ?? 0);
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

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function sortStructureRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $tipoA = strtoupper(trim((string)($a['tipo'] ?? $a['TIPO'] ?? '')));
            $tipoB = strtoupper(trim((string)($b['tipo'] ?? $b['TIPO'] ?? '')));
            $orderA = $tipoA === 'MATERIAIS' ? 1 : 2;
            $orderB = $tipoB === 'MATERIAIS' ? 1 : 2;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            $posA = (int)preg_replace('/\D+/', '', (string)($a['pos_id'] ?? $a['POS_ID'] ?? '0'));
            $posB = (int)preg_replace('/\D+/', '', (string)($b['pos_id'] ?? $b['POS_ID'] ?? '0'));

            return $posA <=> $posB;
        });

        return $rows;
    }

    private function createLocalItemFromSapMaterialRow(
        array $row,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        int $defaultUnitId
    ): int {
        $erpCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
        if ($erpCode === '') {
            return 0;
        }

        $existing = $itemsRepo->findByErpCode($erpCode);
        if ($existing !== null) {
            return (int)($existing['id'] ?? 0);
        }

        $description = trim((string)($row['component_item_name'] ?? $row['COMPONENT_ITEM_NAME'] ?? $erpCode));
        $categoryName = $this->normalizeCategoryName((string)($row['component_group_name'] ?? $row['COMPONENT_GROUP_NAME'] ?? 'Geral'));
        $categoryId = $categoriesRepo->findOrCreateByName($categoryName);
        if ($categoryId === null) {
            return 0;
        }

        $uomCode = strtoupper(trim((string)($row['unidade_medida'] ?? $row['UNIDADE_MEDIDA'] ?? $row['component_uom'] ?? $row['COMPONENT_UOM'] ?? '')));
        $unitId = $unitsRepo->findIdByCode($uomCode) ?? $defaultUnitId;
        if ($unitId <= 0) {
            return 0;
        }

        $sapCost = $this->extractComponentCostFromRow($row);
        $created = $itemsRepo->create([
            'code' => $erpCode,
            'erp_code' => $erpCode,
            'description' => $description,
            'inv_unit_id' => $unitId,
            'inv_category_id' => $categoryId,
            'admin_type' => 'none',
            'average_cost' => $sapCost,
            'last_cost' => $sapCost,
            'min_stock' => 0,
            'max_stock' => 0,
            'active' => 1,
        ]);

        return is_int($created) && $created > 0 ? $created : 0;
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float)$value, 6);
        }

        $text = trim((string)$value);
        if ($text === '') {
            return 0.0;
        }

        // Suporta formatos com separador decimal em vírgula ou ponto
        // e remove separadores de milhar preservando até 6 casas decimais.
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');
            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    // 1.234,567890 -> 1234.567890
                    $text = str_replace('.', '', $text);
                    $text = str_replace(',', '.', $text);
                } else {
                    // 1,234.567890 -> 1234.567890
                    $text = str_replace(',', '', $text);
                }
            }
        } elseif (str_contains($text, ',')) {
            // 1234,567890 -> 1234.567890
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        if (!is_numeric($text)) {
            return 0.0;
        }

        return round((float)$text, 6);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractComponentCostFromRow(array $row): float
    {
        $cost = $this->toFloat(
            $row['component_avg_price']
                ?? $row['COMPONENT_AVG_PRICE']
                ?? $row['Custo Médio']
                ?? $row['Custo Medio']
                ?? 0
        );

        return $cost > 0 ? $cost : 0.0;
    }

    private function extractSapCost(array $row): float
    {
        $cost = $this->toFloat(
            $row['AvgPrice']
                ?? $row['AVGPRICE']
                ?? $row['Custo Médio']
                ?? $row['Custo Medio']
                ?? 0
        );

        return $cost > 0 ? $cost : 0.0;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function updateItemCostFromSap(InvItemsRepository $itemsRepo, array $item, float $sapCost): void
    {
        $itemId = (int)($item['id'] ?? 0);
        if ($itemId <= 0 || $sapCost <= 0) {
            return;
        }

        $currentCost = (float)($item['average_cost'] ?? 0);
        $categoryName = (string)($item['category_name'] ?? '');
        $itemType = $this->inferItemTypeFromCategory($categoryName);
        $shouldUpdate = $currentCost <= 0 || in_array($itemType, ['MP', 'EMB'], true);
        if (!$shouldUpdate) {
            return;
        }

        $itemsRepo->update($itemId, [
            'code' => (string)($item['code'] ?? ''),
            'erp_code' => $item['erp_code'] ?? null,
            'description' => (string)($item['description'] ?? ''),
            'inv_unit_id' => (int)($item['inv_unit_id'] ?? 0),
            'inv_category_id' => $item['inv_category_id'] ?? null,
            'admin_type' => (string)($item['admin_type'] ?? 'none'),
            'average_cost' => $sapCost,
            'last_cost' => $sapCost,
            'min_stock' => (float)($item['min_stock'] ?? 0),
            'max_stock' => (float)($item['max_stock'] ?? 0),
            'active' => (int)($item['active'] ?? 1),
        ]);
    }

    private function normalizeCategoryName(string $sapGroupName): string
    {
        $name = trim($sapGroupName);
        if ($name === '') {
            return 'Geral';
        }
        return mb_strtoupper($name, 'UTF-8');
    }

    private function inferItemTypeFromCategory(string $categoryName): string
    {
        $name = mb_strtoupper($categoryName, 'UTF-8');
        if (str_contains($name, 'MATER') || str_contains($name, 'MATÉRIA')) {
            return 'MP';
        }
        if (str_contains($name, 'EMBAL')) {
            return 'EMB';
        }
        if (str_contains($name, 'INTERMED')) {
            return 'PI';
        }
        if (str_contains($name, 'ACABADO') || str_contains($name, 'PROD')) {
            return 'PA';
        }
        return 'OTHER';
    }
}

