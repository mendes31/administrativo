<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Reparte um valor total entre pesos proporcionais sem perder centavos por arredondamento.
 */
final class InvCostAllocationSplitHelper
{
    /**
     * @param array<int|string, float> $weights pesos positivos (ex.: % por SKU)
     * @return array<int|string, float> mesmas chaves; soma = $totalAmount (4 casas)
     */
    public static function split(float $totalAmount, array $weights): array
    {
        if ($totalAmount <= 0) {
            return [];
        }

        $positive = [];
        foreach ($weights as $key => $weight) {
            if ($weight > 0) {
                $positive[$key] = $weight;
            }
        }

        if ($positive === []) {
            return [];
        }

        $weightSum = array_sum($positive);
        if ($weightSum <= 0) {
            return [];
        }

        $floored = [];
        $remainders = [];
        foreach ($positive as $key => $weight) {
            $exact = $totalAmount * ($weight / $weightSum);
            $floor = floor($exact * 10000 + 1e-9) / 10000;
            $floored[$key] = $floor;
            $remainders[$key] = $exact - $floor;
        }

        $assigned = array_sum($floored);
        $units = (int) round(($totalAmount - $assigned) * 10000);
        if ($units > 0) {
            arsort($remainders);
            $keys = array_keys($remainders);
            $count = count($keys);
            for ($i = 0; $i < $units; $i++) {
                $key = $keys[$i % $count];
                $floored[$key] = round($floored[$key] + 0.0001, 4);
            }
        }

        return array_filter($floored, static fn(float $v): bool => $v > 0);
    }
}
