<?php

namespace App\adms\Models\Services;

use DateInterval;
use DateTime;
use DateTimeImmutable;

/**
 * Agrega KPIs de People Analytics a partir de listas já filtradas (usuários + histórico).
 */
final class PeopleAnalyticsMetricsService
{
    /**
     * @param array<int, array<string, mixed>> $users
     * @param array<int, list<array<string, mixed>>> $historyByUserId
     * @return array<string, mixed>
     */
    public function compute(array $users, array $historyByUserId, string $periodStart, string $periodEnd): array
    {
        $periodStart = $this->clampPeriodStart($periodStart, $periodEnd);

        $terminatedInPeriod = 0;
        $admissionsInPeriod = 0;
        foreach ($users as $u) {
            $td = $u['data_desligamento'] ?? null;
            if (!empty($td) && $td >= $periodStart && $td <= $periodEnd) {
                $terminatedInPeriod++;
            }
            $ad = $u['data_admissao'] ?? null;
            if (!empty($ad) && $ad >= $periodStart && $ad <= $periodEnd) {
                $admissionsInPeriod++;
            }
        }

        $activeAtStart = $this->countActiveAtSnapshot($users, $periodStart);
        $activeAtEnd = $this->countActiveAtSnapshot($users, $periodEnd);
        $averageEmployees = ($activeAtStart + $activeAtEnd) / 2.0;
        $turnoverRate = $averageEmployees > 0
            ? round(($terminatedInPeriod / $averageEmployees) * 100, 2)
            : 0.0;

        $activeNow = count(array_filter($users, static function (array $u): bool {
            return ($u['status'] ?? '') === 'Ativo' && empty($u['data_desligamento']);
        }));

        $monthlyHeadcount = $this->buildMonthlyHeadcount($users, $periodStart, $periodEnd);
        $turnoverByDept = $this->buildTurnoverByDepartment($users, $periodStart, $periodEnd);
        $departmentDistribution = $this->buildDepartmentDistribution($users);
        $positionDistribution = $this->buildPositionDistribution($users);

        [$avgTenureLabel, $totalRehires] = $this->historyAggregates($historyByUserId);

        return [
            'total_employees' => count($users),
            'active_employees' => $activeNow,
            'admissions_in_period' => $admissionsInPeriod,
            'terminations_in_period' => $terminatedInPeriod,
            'net_movement' => $admissionsInPeriod - $terminatedInPeriod,
            'turnover_rate' => number_format($turnoverRate, 2, '.', ''),
            'terminated_last_year' => $terminatedInPeriod,
            'avg_tenure' => $avgTenureLabel,
            'total_rehires' => $totalRehires,
            'monthly_headcount' => $monthlyHeadcount,
            'turnover_by_department' => $turnoverByDept,
            'department_distribution' => $departmentDistribution,
            'position_distribution' => $positionDistribution,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'active_at_period_start' => $activeAtStart,
            'active_at_period_end' => $activeAtEnd,
            'turnover_formula' => 'Desligamentos no período ÷ média entre efetivo no início e no fim do período × 100.',
        ];
    }

    private function clampPeriodStart(string $periodStart, string $periodEnd): string
    {
        if ($periodStart <= $periodEnd) {
            return $periodStart;
        }

        return $periodEnd;
    }

    /**
     * @param array<int, array<string, mixed>> $users
     */
    private function countActiveAtSnapshot(array $users, string $dateYmd): int
    {
        return count(array_filter($users, static function (array $u) use ($dateYmd): bool {
            if (($u['status'] ?? '') !== 'Ativo') {
                return false;
            }
            $ad = $u['data_admissao'] ?? '';
            if ($ad !== '' && $ad > $dateYmd) {
                return false;
            }
            $td = $u['data_desligamento'] ?? '';
            if ($td !== '' && $td <= $dateYmd) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<string, int>
     */
    private function buildMonthlyHeadcount(array $users, string $periodStart, string $periodEnd): array
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $periodStart) ?: new DateTimeImmutable($periodStart);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $periodEnd) ?: new DateTimeImmutable($periodEnd);
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $firstOfStart = $start->modify('first day of this month');
        $firstOfEnd = $end->modify('first day of this month');

        $maxMonths = 36;
        $months = [];
        $cursor = $firstOfStart;
        while ($cursor <= $firstOfEnd && count($months) < $maxMonths) {
            $months[] = $cursor;
            $cursor = $cursor->add(new DateInterval('P1M'));
        }

        $out = [];
        foreach ($months as $monthDate) {
            $monthStart = $monthDate->format('Y-m-01');
            $monthEnd = $monthDate->format('Y-m-t');
            $label = $monthDate->format('M/Y');
            $out[$label] = $this->countActiveInCalendarMonth($users, $monthStart, $monthEnd);
        }

        return $out;
    }

    /**
     * Mesma regra da view legada: admitido até o fim do mês e (sem desligamento ou desligamento >= início do mês).
     *
     * @param array<int, array<string, mixed>> $users
     */
    private function countActiveInCalendarMonth(array $users, string $monthStart, string $monthEnd): int
    {
        $n = 0;
        foreach ($users as $u) {
            $ad = $u['data_admissao'] ?? '';
            if ($ad === '') {
                continue;
            }
            if ($ad > $monthEnd) {
                continue;
            }
            $td = $u['data_desligamento'] ?? '';
            if ($td !== '' && $td < $monthStart) {
                continue;
            }
            $n++;
        }

        return $n;
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<string, array{total: int, terminated: int, active: int, turnover_rate: float}>
     */
    private function buildTurnoverByDepartment(array $users, string $periodStart, string $periodEnd): array
    {
        $byDept = [];
        foreach ($users as $u) {
            $dept = $u['name_dep'] ?? 'Sem Departamento';
            if (!isset($byDept[$dept])) {
                $byDept[$dept] = ['uids' => []];
            }
            $byDept[$dept]['uids'][] = $u;
        }

        $out = [];
        foreach ($byDept as $dept => $pack) {
            $list = $pack['uids'];
            $terminated = 0;
            foreach ($list as $u) {
                $td = $u['data_desligamento'] ?? null;
                if (!empty($td) && $td >= $periodStart && $td <= $periodEnd) {
                    $terminated++;
                }
            }
            $start = $this->countActiveAtSnapshot($list, $periodStart);
            $end = $this->countActiveAtSnapshot($list, $periodEnd);
            $avg = ($start + $end) / 2.0;
            $rate = $avg > 0 ? round(($terminated / $avg) * 100, 2) : 0.0;
            $out[$dept] = [
                'total' => count($list),
                'terminated' => $terminated,
                'active' => count(array_filter($list, static fn (array $x): bool => ($x['status'] ?? '') === 'Ativo' && empty($x['data_desligamento']))),
                'turnover_rate' => $rate,
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<string, int>
     */
    private function buildDepartmentDistribution(array $users): array
    {
        $dist = [];
        foreach ($users as $u) {
            if (($u['status'] ?? '') !== 'Ativo' || !empty($u['data_desligamento'])) {
                continue;
            }
            $d = $u['name_dep'] ?? 'Sem Departamento';
            $dist[$d] = ($dist[$d] ?? 0) + 1;
        }

        return $dist;
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<string, int>
     */
    private function buildPositionDistribution(array $users): array
    {
        $dist = [];
        foreach ($users as $u) {
            if (($u['status'] ?? '') !== 'Ativo' || !empty($u['data_desligamento'])) {
                continue;
            }
            $p = $u['name_pos'] ?? 'Sem Cargo';
            $dist[$p] = ($dist[$p] ?? 0) + 1;
        }

        return $dist;
    }

    /**
     * @param array<int, list<array<string, mixed>>> $historyByUserId
     * @return array{0: string, 1: int}
     */
    private function historyAggregates(array $historyByUserId): array
    {
        $terminatedPeriods = [];
        $rehires = 0;
        foreach ($historyByUserId as $periods) {
            foreach ($periods as $period) {
                if (($period['tipo_periodo'] ?? '') === 'Recontratação') {
                    $rehires++;
                }
                if (!empty($period['data_desligamento'])) {
                    $terminatedPeriods[] = $period;
                }
            }
        }

        $totalTenureDays = 0;
        $countWithTenure = count($terminatedPeriods);
        foreach ($terminatedPeriods as $period) {
            try {
                $admission = new DateTime($period['data_admissao']);
                $termination = new DateTime($period['data_desligamento']);
                $totalTenureDays += max(0, $admission->diff($termination)->days);
            } catch (\Exception) {
                $countWithTenure--;
            }
        }

        if ($countWithTenure < 1) {
            return ['N/A', $rehires];
        }

        $avgTenureDays = $totalTenureDays / $countWithTenure;
        $avgTenureYears = (int) floor($avgTenureDays / 365);
        $avgTenureMonths = (int) floor(fmod($avgTenureDays, 365) / 30);

        $label = $avgTenureYears > 0
            ? "{$avgTenureYears} ano(s) e {$avgTenureMonths} mês(es)"
            : "{$avgTenureMonths} mês(es)";

        return [$label, $rehires];
    }
}
