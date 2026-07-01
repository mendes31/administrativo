<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\InvItemBomExplosionHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvItemBomRepository extends DbConnection
{
    /**
     * Retorna a lista de materiais (BOM) de um item.
     *
     * @return list<array<string, mixed>>
     */
    public function getByItem(int $invItemId): array
    {
        $sql = 'SELECT b.id,
                       b.line_source,
                       b.component_item_id,
                       b.quantity_per_batch,
                       b.scrap_percent,
                       b.manual_description,
                       b.manual_component_type,
                       b.manual_unit,
                       b.manual_unit_cost,
                       i.code AS component_code,
                       i.description AS component_description,
                       c.name AS component_category,
                       u.name AS unit_name,
                       i.average_cost AS component_cost
                FROM inv_item_bom b
                LEFT JOIN inv_items i ON i.id = b.component_item_id
                LEFT JOIN inv_categories c ON c.id = i.inv_category_id
                LEFT JOIN inv_units u ON u.id = i.inv_unit_id
                WHERE b.inv_item_id = :inv_item_id
                ORDER BY b.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row = $this->normalizeBomRow($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * BOM para exibição com MPs/MAEs dos PIs explodidas e identificadas.
     *
     * @return list<array<string, mixed>>
     */
    public function getDisplayRowsByItem(int $invItemId): array
    {
        $rows = InvItemBomExplosionHelper::buildDisplayRows($this->getByItem($invItemId));

        return $this->refreshDisplayRowCatalogCosts($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function refreshDisplayRowCatalogCosts(array $rows): array
    {
        $itemsRepo = new InvItemsRepository();
        foreach ($rows as &$row) {
            $componentId = (int)($row['component_item_id'] ?? 0);
            if ($componentId <= 0 || (string)($row['line_source'] ?? 'catalog') === 'manual') {
                continue;
            }
            $item = $itemsRepo->getOne($componentId);
            if (!is_array($item)) {
                continue;
            }
            $row['component_cost'] = (float)($item['average_cost'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * BOM com campos para custeio (MP/MAE/PI + custo médio).
     *
     * @return list<array<string, mixed>>
     */
    public function getCostingRowsByItem(int $invItemId): array
    {
        if ($invItemId <= 0) {
            return [];
        }

        $sql = 'SELECT b.line_source,
                       b.component_item_id,
                       b.quantity_per_batch,
                       b.scrap_percent,
                       b.manual_description,
                       b.manual_component_type,
                       b.manual_unit,
                       b.manual_unit_cost,
                       i.code AS component_code,
                       i.description AS component_description,
                       u.name AS unit_name,
                       i.average_cost AS component_cost,
                       c.name AS component_category
                FROM inv_item_bom b
                LEFT JOIN inv_items i ON i.id = b.component_item_id
                LEFT JOIN inv_units u ON u.id = i.inv_unit_id
                LEFT JOIN inv_categories c ON c.id = i.inv_category_id
                WHERE b.inv_item_id = :inv_item_id
                ORDER BY b.line_source ASC, c.name ASC, i.description ASC, b.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Custo unitário efetivo da linha (catálogo ou manual).
     *
     * @param array<string, mixed> $row
     */
    public static function resolveLineUnitCost(array $row): float
    {
        $source = (string)($row['line_source'] ?? 'catalog');
        if ($source === 'manual') {
            return max(0.0, (float)($row['manual_unit_cost'] ?? 0));
        }

        return max(0.0, (float)($row['component_cost'] ?? 0));
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function computeLineMaterialCost(array $row): float
    {
        $qty = (float)($row['quantity_per_batch'] ?? 0);
        $scrap = (float)($row['scrap_percent'] ?? 0);
        $cost = self::resolveLineUnitCost($row);
        if ($qty <= 0 || $cost <= 0) {
            return 0.0;
        }

        $effectiveQty = $qty * (1 + $scrap / 100.0);

        return round($effectiveQty * $cost, 6);
    }

    /**
     * Verifica se as linhas informadas são equivalentes à BOM já gravada no item.
     *
     * @param list<array<string, mixed>> $incomingLines
     */
    public function structureLinesMatch(int $invItemId, array $incomingLines): bool
    {
        $current = $this->normalizeComparableBomLines($this->getByItem($invItemId));
        $incoming = $this->normalizeComparableBomLines($incomingLines);

        return json_encode($current, JSON_UNESCAPED_UNICODE) === json_encode($incoming, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private function normalizeComparableBomLines(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $line) {
            $source = (string)($line['line_source'] ?? 'catalog');
            if (!in_array($source, ['catalog', 'manual'], true)) {
                $source = 'catalog';
            }

            $componentId = isset($line['component_item_id']) ? (int)$line['component_item_id'] : 0;
            if ($source === 'catalog' && $componentId <= 0) {
                continue;
            }

            $entry = [
                'line_source' => $source,
                'component_item_id' => $source === 'catalog' ? $componentId : null,
                'quantity_per_batch' => round((float)($line['quantity_per_batch'] ?? 0), 6),
                'scrap_percent' => round((float)($line['scrap_percent'] ?? 0), 6),
            ];

            if ($source === 'manual') {
                $entry['manual_description'] = (string)($line['manual_description'] ?? '');
                $entry['manual_component_type'] = (string)($line['manual_component_type'] ?? '');
                $entry['manual_unit'] = (string)($line['manual_unit'] ?? '');
                $entry['manual_unit_cost'] = round((float)($line['manual_unit_cost'] ?? 0), 6);
            }

            $normalized[] = $entry;
        }

        usort($normalized, static function (array $a, array $b): int {
            $sourceCmp = strcmp((string)$a['line_source'], (string)$b['line_source']);
            if ($sourceCmp !== 0) {
                return $sourceCmp;
            }

            if (($a['line_source'] ?? '') === 'manual') {
                return strcmp((string)($a['manual_description'] ?? ''), (string)($b['manual_description'] ?? ''));
            }

            $idCmp = ((int)($a['component_item_id'] ?? 0)) <=> ((int)($b['component_item_id'] ?? 0));
            if ($idCmp !== 0) {
                return $idCmp;
            }

            return ((float)($a['quantity_per_batch'] ?? 0)) <=> ((float)($b['quantity_per_batch'] ?? 0));
        });

        return $normalized;
    }

    /**
     * Substitui completamente a BOM de um item pelas linhas informadas.
     *
     * @param list<array<string, mixed>> $lines
     */
    public function replaceForItem(int $invItemId, array $lines): bool
    {
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();

            $oldSnapshot = $this->snapshotBomJson($conn, $invItemId);

            $stmtDelete = $conn->prepare('DELETE FROM inv_item_bom WHERE inv_item_id = :inv_item_id');
            $stmtDelete->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
            $stmtDelete->execute();

            if ($lines !== []) {
                $sql = 'INSERT INTO inv_item_bom (
                            inv_item_id, line_source, component_item_id,
                            quantity_per_batch, scrap_percent,
                            manual_description, manual_component_type, manual_unit, manual_unit_cost,
                            created_at, updated_at
                        ) VALUES (
                            :inv_item_id, :line_source, :component_item_id,
                            :quantity_per_batch, :scrap_percent,
                            :manual_description, :manual_component_type, :manual_unit, :manual_unit_cost,
                            :created_at, :updated_at
                        )';
                $stmtInsert = $conn->prepare($sql);
                $now = date('Y-m-d H:i:s');

                foreach ($lines as $line) {
                    $source = (string)($line['line_source'] ?? 'catalog');
                    if (!in_array($source, ['catalog', 'manual'], true)) {
                        $source = 'catalog';
                    }

                    $componentId = isset($line['component_item_id']) ? (int)$line['component_item_id'] : null;
                    if ($source === 'catalog' && ($componentId === null || $componentId <= 0)) {
                        continue;
                    }

                    $stmtInsert->bindValue(':inv_item_id', $invItemId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':line_source', $source);
                    if ($source === 'manual' || $componentId === null || $componentId <= 0) {
                        $stmtInsert->bindValue(':component_item_id', null, PDO::PARAM_NULL);
                    } else {
                        $stmtInsert->bindValue(':component_item_id', $componentId, PDO::PARAM_INT);
                    }
                    $stmtInsert->bindValue(':quantity_per_batch', (float)($line['quantity_per_batch'] ?? 0));
                    $stmtInsert->bindValue(':scrap_percent', (float)($line['scrap_percent'] ?? 0));
                    $stmtInsert->bindValue(':manual_description', $line['manual_description'] ?? null);
                    $stmtInsert->bindValue(':manual_component_type', $line['manual_component_type'] ?? null);
                    $stmtInsert->bindValue(':manual_unit', $line['manual_unit'] ?? null);
                    $stmtInsert->bindValue(':manual_unit_cost', isset($line['manual_unit_cost']) ? (float)$line['manual_unit_cost'] : null);
                    $stmtInsert->bindValue(':created_at', $now);
                    $stmtInsert->bindValue(':updated_at', $now);
                    $stmtInsert->execute();
                }
            }

            $conn->commit();

            $newSnapshot = $this->snapshotBomJson($conn, $invItemId);
            if ($oldSnapshot !== $newSnapshot) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_item_bom',
                    $invItemId,
                    $usuarioId,
                    'UPDATE',
                    ['snapshot' => $oldSnapshot],
                    ['snapshot' => $newSnapshot]
                );
            }

            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao salvar BOM do item', [
                'inv_item_id' => $invItemId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeBomRow(array $row): array
    {
        $source = (string)($row['line_source'] ?? 'catalog');
        if ($source !== 'manual') {
            $row['line_source'] = 'catalog';

            return $row;
        }

        $row['line_source'] = 'manual';
        $row['component_code'] = 'MANUAL';
        $row['component_description'] = (string)($row['manual_description'] ?? '');
        $row['unit_name'] = (string)($row['manual_unit'] ?? '');
        $row['component_cost'] = (float)($row['manual_unit_cost'] ?? 0);

        return $row;
    }

    private function snapshotBomJson(\PDO $conn, int $invItemId): string
    {
        $stmt = $conn->prepare('SELECT * FROM inv_item_bom WHERE inv_item_id = :id ORDER BY id ASC');
        $stmt->bindValue(':id', $invItemId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }
}
