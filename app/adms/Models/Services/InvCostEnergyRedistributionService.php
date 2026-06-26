<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostAllocationRulesRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

/**
 * Redistribui a conta de Energia Elétrica (DRE) em HVAC / área comum / direto,
 * alinhado à planilha Tiaraju (CUSTEIO FABRIL R2363–R2366).
 */
class InvCostEnergyRedistributionService
{
    /** Pesos de referência Tiaraju 2025 (R$) — proporcionais ao split 47,27% / 51,90% / 0,83%. */
    private const REF_WEIGHT_HVAC = 951823.33468452201;
    private const REF_WEIGHT_COMUM = 1045115.5279128583;
    private const REF_WEIGHT_DIRETO = 16785.98740261991;

    /** @var array{hvac: int, comum: int, direto: int} */
    private const SLICE_CRITERION = [
        'hvac' => 8,
        'comum' => 3,
        'direto' => 7,
    ];

    /**
     * @param array<string, mixed> $period
     * @return array{
     *   hvac: array{amount: float, pct: float, weight: float, criterion: int},
     *   comum: array{amount: float, pct: float, weight: float, criterion: int},
     *   direto: array{amount: float, pct: float, weight: float, criterion: int},
     *   total: float,
     *   weight_source: string
     * }
     */
    public function previewSplit(float $totalAmount, array $period, ?int $periodId = null): array
    {
        $totalAmount = round(max(0, $totalAmount), 4);
        [$weights, $source] = $this->resolveWeights($period, $periodId);
        $sumW = $weights['hvac'] + $weights['comum'] + $weights['direct'];
        if ($totalAmount <= 0 || $sumW <= 0) {
            return $this->emptyPreview($totalAmount, $source);
        }

        $amounts = [
            'hvac' => round($totalAmount * ($weights['hvac'] / $sumW), 4),
            'comum' => round($totalAmount * ($weights['comum'] / $sumW), 4),
            'direct' => round($totalAmount * ($weights['direct'] / $sumW), 4),
        ];
        $amounts = $this->adjustRounding($amounts, $totalAmount);

        return $this->buildPreview($amounts, $weights, $totalAmount, $source);
    }

    /**
     * @return array{success: bool, message: string, pools_split?: int, preview?: array<string, mixed>}
     */
    public function applyForPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return ['success' => false, 'message' => 'Período inválido.'];
        }

        $periodRepo = new InvCostPeriodsRepository();
        $period = $periodRepo->getOne($periodId);
        if ($period === false) {
            return ['success' => false, 'message' => 'Período não encontrado.'];
        }
        if ($periodRepo->isClosed($periodId)) {
            return ['success' => false, 'message' => 'Período fechado: redistribuição bloqueada.'];
        }

        $poolsRepo = new InvCostExpensePoolsRepository();
        $parents = $poolsRepo->findEnergyAccountsForSplit($periodId);
        if ($parents === []) {
            return [
                'success' => true,
                'message' => 'Nenhuma conta de energia elétrica encontrada para redistribuir (código 29 ou descrição equivalente).',
                'pools_split' => 0,
            ];
        }

        $rulesRepo = new InvCostAllocationRulesRepository();
        $splitCount = 0;
        $messages = [];

        foreach ($parents as $parent) {
            $poolId = (int)($parent['id'] ?? 0);
            $total = (float)($parent['amount'] ?? 0);
            if ($poolId <= 0 || $total <= 0) {
                continue;
            }

            $preview = $this->previewSplit($total, $period, $periodId);
            $importId = $parent['dre_import_id'] !== null ? (int)$parent['dre_import_id'] : null;
            $baseCode = trim((string)($parent['account_code'] ?? '29')) ?: '29';
            $baseDesc = trim((string)($parent['description'] ?? 'Energia Elétrica'));

            $slices = [
                'direct' => [
                    'code' => $baseCode . '-D',
                    'desc' => $baseDesc . ' — direto (CFIX crit. 7)',
                    'group' => 'EE_DIRETO',
                    'amount' => $preview['direto']['amount'],
                    'criterion' => self::SLICE_CRITERION['direto'],
                ],
                'comum' => [
                    'code' => $baseCode . '-C',
                    'desc' => $baseDesc . ' — área comum (CFIX crit. 3)',
                    'group' => 'EE_COMUM',
                    'amount' => $preview['comum']['amount'],
                    'criterion' => self::SLICE_CRITERION['comum'],
                ],
                'hvac' => [
                    'code' => $baseCode . '-H',
                    'desc' => $baseDesc . ' — HVAC (CFIX crit. 8)',
                    'group' => 'EE_HVAC',
                    'amount' => $preview['hvac']['amount'],
                    'criterion' => self::SLICE_CRITERION['hvac'],
                ],
            ];

            $poolsRepo->deleteEnergySlicesForBase($periodId, $baseCode);
            $poolsRepo->deleteById($poolId);

            foreach ($slices as $slice) {
                if ((float)$slice['amount'] <= 0) {
                    continue;
                }
                $newPoolId = $poolsRepo->insertOne($periodId, $importId, [
                    'source' => (string)($parent['source'] ?? 'DRE'),
                    'account_code' => $slice['code'],
                    'description' => $slice['desc'],
                    'amount' => $slice['amount'],
                    'area' => null,
                    'redistribution_group' => $slice['group'],
                ]);
                $rulesRepo->replaceRulesForPool($newPoolId, [
                    ['criterion' => $slice['criterion'], 'weight_pct' => 100.0],
                ]);
            }

            $splitCount++;
            $messages[] = sprintf(
                '%s: R$ %s → direto R$ %s (%.2f%%) | comum R$ %s (%.2f%%) | HVAC R$ %s (%.2f%%)',
                $baseCode,
                number_format($total, 2, ',', '.'),
                number_format($preview['direto']['amount'], 2, ',', '.'),
                $preview['direto']['pct'],
                number_format($preview['comum']['amount'], 2, ',', '.'),
                $preview['comum']['pct'],
                number_format($preview['hvac']['amount'], 2, ',', '.'),
                $preview['hvac']['pct']
            );
        }

        if ($splitCount === 0) {
            return ['success' => true, 'message' => 'Contas de energia sem valor para redistribuir.', 'pools_split' => 0];
        }

        return [
            'success' => true,
            'message' => 'Energia redistribuída em ' . $splitCount . ' conta(s). ' . implode(' ', $messages),
            'pools_split' => $splitCount,
        ];
    }

    /**
     * @param array<string, mixed> $period
     * @return array{0: array{hvac: float, comum: float, direct: float}, 1: string}
     */
    private function resolveWeights(array $period, ?int $periodId): array
    {
        $hvac = $this->positiveFloat($period['energy_kwh_hvac'] ?? null);
        $comum = $this->positiveFloat($period['energy_kwh_production_common'] ?? null);
        $direct = $this->positiveFloat($period['energy_kwh_direct_cfix'] ?? null);

        if ($direct === null && $periodId !== null && $periodId > 0) {
            $computed = (new InvCostEnergyDriversService())->sumDirectKwhByPeriod($periodId);
            if ($computed > 0) {
                $direct = $computed;
            }
        }

        $hasCustom = ($hvac !== null && $hvac > 0)
            || ($comum !== null && $comum > 0)
            || ($direct !== null && $direct > 0);

        if (!$hasCustom) {
            return [
                [
                    'hvac' => self::REF_WEIGHT_HVAC,
                    'comum' => self::REF_WEIGHT_COMUM,
                    'direct' => self::REF_WEIGHT_DIRETO,
                ],
                'referência Tiaraju (pesos R$ 2025)',
            ];
        }

        return [
            [
                'hvac' => $hvac ?? 0.0,
                'comum' => $comum ?? 0.0,
                'direct' => $direct ?? 0.0,
            ],
            'kWh cadastrados no período' . ($direct !== null && ($period['energy_kwh_direct_cfix'] ?? null) === null ? ' (direto calculado do cadastro)' : ''),
        ];
    }

    /**
     * @param array{hvac: float, comum: float, direct: float} $amounts
     * @param array{hvac: float, comum: float, direct: float} $weights
     * @return array<string, mixed>
     */
    private function buildPreview(array $amounts, array $weights, float $total, string $source): array
    {
        $keys = [
            'hvac' => 'hvac',
            'comum' => 'comum',
            'direct' => 'direto',
        ];
        $out = ['total' => $total, 'weight_source' => $source];
        foreach ($keys as $amtKey => $label) {
            $amt = (float)($amounts[$amtKey] ?? 0);
            $out[$label] = [
                'amount' => $amt,
                'pct' => $total > 0 ? round(($amt / $total) * 100, 2) : 0.0,
                'weight' => (float)($weights[$amtKey] ?? 0),
                'criterion' => self::SLICE_CRITERION[$label],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPreview(float $total, string $source): array
    {
        $zero = ['amount' => 0.0, 'pct' => 0.0, 'weight' => 0.0, 'criterion' => 0];

        return [
            'hvac' => array_merge($zero, ['criterion' => 8]),
            'comum' => array_merge($zero, ['criterion' => 3]),
            'direto' => array_merge($zero, ['criterion' => 7]),
            'total' => $total,
            'weight_source' => $source,
        ];
    }

    /**
     * @param array{hvac: float, comum: float, direct: float} $amounts
     * @return array{hvac: float, comum: float, direct: float}
     */
    private function adjustRounding(array $amounts, float $total): array
    {
        $sum = round($amounts['hvac'] + $amounts['comum'] + $amounts['direct'], 4);
        $diff = round($total - $sum, 4);
        if (abs($diff) >= 0.0001) {
            $amounts['comum'] = round($amounts['comum'] + $diff, 4);
        }

        return $amounts;
    }

    private function positiveFloat(mixed $value): ?float
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

        return $f > 0 ? round($f, 4) : null;
    }

    public static function isEnergyAccountCode(string $code): bool
    {
        $code = trim($code);

        return $code === '29' || preg_match('/^29[\s\-]/', $code) === 1;
    }

    public static function isAlreadySplitAccount(string $code): bool
    {
        return preg_match('/-(D|C|H)$/i', trim($code)) === 1;
    }
}
