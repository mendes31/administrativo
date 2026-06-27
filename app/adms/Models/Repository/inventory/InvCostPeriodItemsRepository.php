<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostPeriodItemsRepository extends DbConnection
{
    /**
     * @return array<int, array<string, mixed>> keyed by inv_item_id
     */
    public function getMapByPeriod(int $periodId): array
    {
        $sql = 'SELECT * FROM inv_cost_period_items WHERE inv_cost_period_id = :period_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['inv_item_id']] = $row;
        }

        return $map;
    }

    public function getOne(int $periodId, int $itemId): array|false
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM inv_cost_period_items WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id LIMIT 1'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertOverrides(int $periodId, int $itemId, array $data): void
    {
        if ($periodId <= 0 || $itemId <= 0) {
            return;
        }

        $existing = $this->getOne($periodId, $itemId);
        $now = date('Y-m-d H:i:s');

        $fields = [
            'batch_size_theoretical' => $this->nullableDecimal($data['batch_size_theoretical'] ?? null),
            'batch_size_adopted' => $this->nullableDecimal($data['batch_size_adopted'] ?? null),
            'efficiency_pct' => $this->nullableDecimal($data['efficiency_pct'] ?? null),
            'production_line' => $this->nullableString($data['production_line'] ?? null),
            'energy_class' => $this->nullableString($data['energy_class'] ?? null),
            'complexity_level' => $this->nullableString($data['complexity_level'] ?? null) ?? 'media',
            'analysis_count' => max(0, (int)($data['analysis_count'] ?? 0)),
            'sale_price_net' => $this->nullableDecimal($data['sale_price_net'] ?? null),
            'target_margin_pct' => $this->nullableDecimal($data['target_margin_pct'] ?? null),
        ];

        if ($existing !== false) {
            $sql = 'UPDATE inv_cost_period_items SET
                    batch_size_theoretical = :batch_size_theoretical,
                    batch_size_adopted = :batch_size_adopted,
                    efficiency_pct = COALESCE(:efficiency_pct, efficiency_pct),
                    production_line = :production_line,
                    energy_class = :energy_class,
                    complexity_level = :complexity_level,
                    analysis_count = :analysis_count,
                    sale_price_net = :sale_price_net,
                    target_margin_pct = :target_margin_pct,
                    updated_at = :updated_at
                WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id';
            $stmt = $this->getConnection()->prepare($sql);
        } else {
            $sql = 'INSERT INTO inv_cost_period_items
                    (inv_cost_period_id, inv_item_id, batch_size_theoretical, batch_size_adopted, efficiency_pct,
                     production_line, energy_class, complexity_level, analysis_count, sale_price_net, target_margin_pct,
                     created_at, updated_at)
                    VALUES (:period_id, :item_id, :batch_size_theoretical, :batch_size_adopted, :efficiency_pct,
                     :production_line, :energy_class, :complexity_level, :analysis_count, :sale_price_net, :target_margin_pct,
                     :created_at, :updated_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':created_at', $now);
        }

        $stmt->bindValue(':batch_size_theoretical', $fields['batch_size_theoretical'], $fields['batch_size_theoretical'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':batch_size_adopted', $fields['batch_size_adopted'], $fields['batch_size_adopted'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':efficiency_pct', $fields['efficiency_pct'], $fields['efficiency_pct'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':production_line', $fields['production_line'], $fields['production_line'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':energy_class', $fields['energy_class'], $fields['energy_class'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':complexity_level', $fields['complexity_level']);
        $stmt->bindValue(':analysis_count', $fields['analysis_count'], PDO::PARAM_INT);
        $stmt->bindValue(':sale_price_net', $fields['sale_price_net'], $fields['sale_price_net'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':target_margin_pct', $fields['target_margin_pct'], $fields['target_margin_pct'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':updated_at', $now);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function upsertAnalysisCount(int $periodId, int $itemId, int $analysisCount): void
    {
        if ($periodId <= 0 || $itemId <= 0) {
            return;
        }

        $existing = $this->getOne($periodId, $itemId);
        $now = date('Y-m-d H:i:s');
        $analysisCount = max(0, $analysisCount);

        if ($existing !== false) {
            $stmt = $this->getConnection()->prepare(
                'UPDATE inv_cost_period_items SET analysis_count = :analysis_count, updated_at = :updated_at
                 WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id'
            );
        } else {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO inv_cost_period_items
                    (inv_cost_period_id, inv_item_id, analysis_count, created_at, updated_at)
                 VALUES (:period_id, :item_id, :analysis_count, :created_at, :updated_at)'
            );
            $stmt->bindValue(':created_at', $now);
        }

        $stmt->bindValue(':analysis_count', $analysisCount, PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', $now);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function nullableString(mixed $value): ?string
    {
        $s = trim((string)($value ?? ''));

        return $s !== '' ? $s : null;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace(['.', ' '], ['', ''], $value);
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return null;
        }
        $f = (float)$value;

        return $f !== 0.0 || $value === '0' || $value === 0 ? round($f, 6) : null;
    }

    public function upsertCalculatedEfficiency(
        int $periodId,
        int $itemId,
        float $efficiencyRatio,
        ?float $batchSizeAdopted = null
    ): void {
        if ($periodId <= 0 || $itemId <= 0 || $efficiencyRatio <= 0) {
            return;
        }

        $existing = $this->getOne($periodId, $itemId);
        $now = date('Y-m-d H:i:s');
        $efficiencyPct = round($efficiencyRatio * 100, 4);

        if ($existing !== false) {
            $sql = 'UPDATE inv_cost_period_items
                    SET efficiency_pct = :efficiency_pct,
                        batch_size_adopted = COALESCE(batch_size_adopted, :batch_size_adopted),
                        updated_at = :updated_at
                    WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':efficiency_pct', $efficiencyPct);
            $stmt->bindValue(
                ':batch_size_adopted',
                $batchSizeAdopted,
                $batchSizeAdopted !== null && $batchSizeAdopted > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':updated_at', $now);
            $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $stmt->execute();

            return;
        }

        $sql = 'INSERT INTO inv_cost_period_items
                (inv_cost_period_id, inv_item_id, batch_size_adopted, efficiency_pct, analysis_count, created_at, updated_at)
                VALUES (:period_id, :item_id, :batch_size_adopted, :efficiency_pct, 0, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(
            ':batch_size_adopted',
            $batchSizeAdopted,
            $batchSizeAdopted !== null && $batchSizeAdopted > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':efficiency_pct', $efficiencyPct);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();
    }
}
