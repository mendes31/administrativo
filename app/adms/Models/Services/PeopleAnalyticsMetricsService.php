<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\UserFormHelper;
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

        $refDemo = $periodEnd;
        $activeDemo = $this->buildActiveDemographics($users, $refDemo);
        $termDemo = $this->buildTerminationDemographics($users, $historyByUserId, $periodStart, $periodEnd);
        $activeDeptSegments = $this->buildActiveDepartmentSegments($users);
        $activePosSegments = $this->buildActivePositionTopSegments($users, 10);

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
            'active_headcount_by_sex' => $activeDemo['by_sex'],
            'active_headcount_by_age_band' => $activeDemo['by_age_band'],
            'active_headcount_by_filhos' => $activeDemo['by_filhos'],
            'terminations_in_period_by_impact' => $termDemo['by_impact'],
            'terminations_in_period_by_sex' => $termDemo['by_sex'],
            'terminations_in_period_by_age_band' => $termDemo['by_age_band'],
            'terminations_in_period_by_filhos' => $termDemo['by_filhos'],
            'active_headcount_by_estado_civil' => $activeDemo['by_estado_civil'],
            'active_headcount_by_pais' => $activeDemo['by_pais'],
            'terminations_in_period_by_estado_civil' => $termDemo['by_estado_civil'],
            'terminations_in_period_by_pais' => $termDemo['by_pais'],
            'terminations_in_period_by_impact_code' => $termDemo['by_impact_code'],
            'active_department_segments' => $activeDeptSegments,
            'active_position_top_segments' => $activePosSegments,
            'estado_civil_labels' => UserFormHelper::estadoCivilOptions(),
            'demographics_ref_date' => $refDemo,
            'demographics_note' => 'Indicadores por sexo, idade, filhos, estado civil e país são agregados (LGPD). Ativos: snapshot na data final do período filtrado. Desligamentos: somente no período. Classificação regrettable/non: cadastro ou histórico de vínculo. País: lista alinhada ao cadastro (ISO2).',
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
     * @return array<string, array{department_id: int|null, total: int, terminated: int, active: int, turnover_rate: float}>
     */
    private function buildTurnoverByDepartment(array $users, string $periodStart, string $periodEnd): array
    {
        $byDept = [];
        foreach ($users as $u) {
            $dept = $u['name_dep'] ?? 'Sem Departamento';
            $deptIdInit = (int) ($u['user_department_id'] ?? 0);
            if (!isset($byDept[$dept])) {
                $byDept[$dept] = [
                    'uids' => [],
                    'department_id' => $deptIdInit > 0 ? $deptIdInit : null,
                ];
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
            $deptId = (int) ($pack['department_id'] ?? 0);

            $out[$dept] = [
                'department_id' => $deptId > 0 ? $deptId : null,
                'total' => count($list),
                'terminated' => $terminated,
                'active' => count(array_filter($list, static fn (array $x): bool => ($x['status'] ?? '') === 'Ativo' && empty($x['data_desligamento']))),
                'turnover_rate' => $rate,
            ];
        }

        return $out;
    }

    /**
     * Departamentos com ativos (para gráfico e drill por id).
     *
     * @param array<int, array<string, mixed>> $users
     * @return list<array{department_id: int, name: string, count: int}>
     */
    private function buildActiveDepartmentSegments(array $users): array
    {
        $map = [];
        foreach ($users as $u) {
            if (($u['status'] ?? '') !== 'Ativo' || !empty($u['data_desligamento'])) {
                continue;
            }
            $id = (int) ($u['user_department_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = [
                    'department_id' => $id,
                    'name' => (string) ($u['name_dep'] ?? 'Departamento'),
                    'count' => 0,
                ];
            }
            $map[$id]['count']++;
        }
        $list = array_values($map);
        usort($list, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $list;
    }

    /**
     * Cargos com mais ativos (top N) para gráfico e drill por id.
     *
     * @param array<int, array<string, mixed>> $users
     * @return list<array{position_id: int, name: string, count: int}>
     */
    private function buildActivePositionTopSegments(array $users, int $limit): array
    {
        $map = [];
        foreach ($users as $u) {
            if (($u['status'] ?? '') !== 'Ativo' || !empty($u['data_desligamento'])) {
                continue;
            }
            $id = (int) ($u['user_position_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = [
                    'position_id' => $id,
                    'name' => (string) ($u['name_pos'] ?? 'Cargo'),
                    'count' => 0,
                ];
            }
            $map[$id]['count']++;
        }
        $list = array_values($map);
        usort($list, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($list, 0, max(1, $limit));
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

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array{
     *     by_sex: array<string, int>,
     *     by_age_band: array<string, int>,
     *     by_filhos: array<string, int>,
     *     by_estado_civil: array<string, int>,
     *     by_pais: array<string, int>
     * }
     */
    private function buildActiveDemographics(array $users, string $refDateYmd): array
    {
        $bySex = [];
        $byAge = [];
        $byFilhos = [];
        $byEstado = [];
        $byPais = [];
        foreach ($users as $u) {
            if (!$this->isActiveOnRefDate($u, $refDateYmd)) {
                continue;
            }
            $this->bucketIncr($bySex, $this->sexLabel($u['sexo'] ?? null));
            $this->bucketIncr($byAge, $this->ageBand($u['data_nascimento'] ?? null, $refDateYmd));
            $this->bucketIncr($byFilhos, $this->filhosLabelBucket($u['filhos'] ?? null));
            $ec = $u['estado_civil'] ?? null;
            $ecKey = is_string($ec) && $ec !== '' ? $ec : '_empty';
            $this->bucketIncr($byEstado, $ecKey);
            $pi = $u['pais_residencia_iso'] ?? null;
            $piKey = is_string($pi) && $pi !== '' ? strtoupper($pi) : '_empty';
            $this->bucketIncr($byPais, $piKey);
        }

        return [
            'by_sex' => $bySex,
            'by_age_band' => $byAge,
            'by_filhos' => $byFilhos,
            'by_estado_civil' => $byEstado,
            'by_pais' => $byPais,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @param array<int, list<array<string, mixed>>> $historyByUserId
     * @return array{
     *     by_impact: array<string, int>,
     *     by_impact_code: array<string, int>,
     *     by_sex: array<string, int>,
     *     by_age_band: array<string, int>,
     *     by_filhos: array<string, int>,
     *     by_estado_civil: array<string, int>,
     *     by_pais: array<string, int>
     * }
     */
    private function buildTerminationDemographics(
        array $users,
        array $historyByUserId,
        string $periodStart,
        string $periodEnd
    ): array {
        $byImpact = [];
        $byImpactCode = [];
        $bySex = [];
        $byAge = [];
        $byFilhos = [];
        $byEstado = [];
        $byPais = [];
        foreach ($users as $u) {
            $td = $u['data_desligamento'] ?? null;
            if (empty($td) || $td < $periodStart || $td > $periodEnd) {
                continue;
            }
            $impact = $this->resolveTerminationImpact($u, $historyByUserId);
            $this->bucketIncr($byImpact, $this->impactLabel($impact));
            $this->bucketIncr($byImpactCode, $impact);
            $this->bucketIncr($bySex, $this->sexLabel($u['sexo'] ?? null));
            $this->bucketIncr($byAge, $this->ageBand($u['data_nascimento'] ?? null, $td));
            $this->bucketIncr($byFilhos, $this->filhosLabelBucket($u['filhos'] ?? null));
            $ec = $u['estado_civil'] ?? null;
            $ecKey = is_string($ec) && $ec !== '' ? $ec : '_empty';
            $this->bucketIncr($byEstado, $ecKey);
            $pi = $u['pais_residencia_iso'] ?? null;
            $piKey = is_string($pi) && $pi !== '' ? strtoupper($pi) : '_empty';
            $this->bucketIncr($byPais, $piKey);
        }

        return [
            'by_impact' => $byImpact,
            'by_impact_code' => $byImpactCode,
            'by_sex' => $bySex,
            'by_age_band' => $byAge,
            'by_filhos' => $byFilhos,
            'by_estado_civil' => $byEstado,
            'by_pais' => $byPais,
        ];
    }

    private function isActiveOnRefDate(array $u, string $refYmd): bool
    {
        if (($u['status'] ?? '') !== 'Ativo') {
            return false;
        }
        $ad = $u['data_admissao'] ?? '';
        if ($ad !== '' && $ad > $refYmd) {
            return false;
        }
        $td = $u['data_desligamento'] ?? '';
        if ($td !== '' && $td <= $refYmd) {
            return false;
        }

        return true;
    }

    /**
     * Prioridade: cadastro do usuário; fallback linha do histórico com mesma data de desligamento.
     *
     * @param array<int, list<array<string, mixed>>> $historyByUserId
     */
    private function resolveTerminationImpact(array $u, array $historyByUserId): string
    {
        $fromUser = $u['tipo_impacto_desligamento'] ?? null;
        if ($fromUser !== null && $fromUser !== '') {
            return (string) $fromUser;
        }
        $uid = (int) ($u['id'] ?? 0);
        $td = (string) ($u['data_desligamento'] ?? '');
        foreach ($historyByUserId[$uid] ?? [] as $row) {
            if (empty($row['data_desligamento'])) {
                continue;
            }
            if ((string) $row['data_desligamento'] !== $td) {
                continue;
            }
            $h = $row['tipo_impacto_desligamento'] ?? null;
            if ($h !== null && $h !== '') {
                return (string) $h;
            }
            break;
        }

        return 'nao_classificado';
    }

    private function impactLabel(string $code): string
    {
        return match ($code) {
            'regrettable' => 'Regrettable',
            'non_regrettable' => 'Non-regrettable',
            default => 'Não classificado',
        };
    }

    private function sexLabel(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            'O' => 'Outros',
            default => 'Sexo não informado',
        };
    }

    private function filhosLabelBucket(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            'S' => 'Com filhos (cadastro)',
            'N' => 'Sem filhos (cadastro)',
            default => 'Filhos não informado',
        };
    }

    private function ageBand(?string $birthYmd, string $refYmd): string
    {
        $y = $this->ageYearsAt($birthYmd, $refYmd);
        if ($y === null) {
            return 'Sem data de nascimento';
        }
        if ($y < 25) {
            return 'Até 24 anos';
        }
        if ($y <= 40) {
            return '25 a 40 anos';
        }

        return '41 anos ou mais';
    }

    private function ageYearsAt(?string $birthYmd, string $refYmd): ?int
    {
        if ($birthYmd === null || $birthYmd === '') {
            return null;
        }
        try {
            $b = new DateTimeImmutable($birthYmd);
            $r = new DateTimeImmutable($refYmd);
            if ($b > $r) {
                return null;
            }

            return $b->diff($r)->y;
        } catch (\Exception) {
            return null;
        }
    }

    /** @param array<string, int> $map */
    private function bucketIncr(array &$map, string $key): void
    {
        $map[$key] = ($map[$key] ?? 0) + 1;
    }
}
