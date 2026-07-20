<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CriticalPositionsRepository;
use App\adms\Models\Repository\HeadcountPlansRepository;
use App\adms\Models\Repository\PdiActionsRepository;
use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Models\Repository\SuccessionSuccessorsRepository;
use App\adms\Models\Repository\TalentNominationsRepository;
use Throwable;

/**
 * KPIs integrados das Fases 5/6 no People Analytics — 1º incremento.
 */
class IntegratedPeopleIndicatorsService
{
    /**
     * @return array{
     *   cycle_completion: array<string, mixed>,
     *   pdi_progress: array<string, mixed>,
     *   enps_latest: array<string, mixed>,
     *   headcount_gap: array<string, mixed>,
     *   talent_nominations: array<string, mixed>,
     *   succession: array<string, mixed>
     * }
     */
    public function collect(): array
    {
        return [
            'cycle_completion' => $this->safe(fn () => $this->cycleCompletion()),
            'pdi_progress' => $this->safe(fn () => $this->pdiProgress()),
            'enps_latest' => $this->safe(fn () => $this->enpsLatest()),
            'headcount_gap' => $this->safe(fn () => $this->headcountGap()),
            'talent_nominations' => $this->safe(fn () => $this->talentNominations()),
            'succession' => $this->safe(fn () => $this->succession()),
        ];
    }

    /**
     * @param callable(): array $fn
     * @return array<string, mixed>
     */
    private function safe(callable $fn): array
    {
        try {
            return $fn();
        } catch (Throwable) {
            return ['available' => false];
        }
    }

    /** @return array<string, mixed> */
    private function cycleCompletion(): array
    {
        $cycles = new PerformanceCyclesRepository();
        $open = $cycles->getAll(['status' => 'open'], 1, 1);
        $cycle = $open[0] ?? null;
        if (!$cycle) {
            return [
                'available' => true,
                'cycle_id' => null,
                'cycle_name' => null,
                'reviews_total' => 0,
                'reviews_completed' => 0,
                'pct' => null,
            ];
        }
        $cycleId = (int) $cycle['id'];
        $total = $cycles->countReviews($cycleId);
        $completed = $cycles->countCompletedReviews($cycleId);
        $pct = $total > 0 ? round(($completed / $total) * 100, 1) : null;

        return [
            'available' => true,
            'cycle_id' => $cycleId,
            'cycle_name' => $cycle['name'] ?? null,
            'reviews_total' => $total,
            'reviews_completed' => $completed,
            'pct' => $pct,
        ];
    }

    /** @return array<string, mixed> */
    private function pdiProgress(): array
    {
        $active = (new PdiPlansRepository())->count(['status' => 'active']);
        $avg = (new PdiActionsRepository())->avgProgressForActivePlans();

        return [
            'available' => true,
            'active_plans' => $active,
            'actions_avg_pct' => $avg,
        ];
    }

    /** @return array<string, mixed> */
    private function enpsLatest(): array
    {
        $campaigns = new PulseCampaignsRepository();
        $rows = $campaigns->getAll(['campaign_type' => 'enps'], 1, 1);
        $campaign = $rows[0] ?? null;
        if (!$campaign) {
            return [
                'available' => true,
                'campaign_id' => null,
                'name' => null,
                'status' => null,
                'enps' => null,
                'total' => 0,
            ];
        }
        $id = (int) $campaign['id'];
        $stats = (new PulseCampaignService($campaigns))->computeEnps($id);

        return [
            'available' => true,
            'campaign_id' => $id,
            'name' => $campaign['name'] ?? null,
            'status' => $campaign['status'] ?? null,
            'enps' => $stats['enps'],
            'total' => $stats['total'],
        ];
    }

    /** @return array<string, mixed> */
    private function headcountGap(): array
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        $repo = new HeadcountPlansRepository();
        $service = new HeadcountPlanService($repo);
        $rows = $repo->getAll([
            'status' => 'active',
            'period_year' => $year,
            'period_month' => $month,
        ], 1, 500);

        $gapSum = 0;
        $plannedSum = 0;
        $actualSum = 0;
        foreach ($rows as $row) {
            $m = $service->withActual($row);
            $gapSum += $m['gap'];
            $plannedSum += (int) ($row['planned_count'] ?? 0);
            $actualSum += $m['actual_count'];
        }

        return [
            'available' => true,
            'period_year' => $year,
            'period_month' => $month,
            'lines' => count($rows),
            'gap_sum' => $gapSum,
            'planned_sum' => $plannedSum,
            'actual_sum' => $actualSum,
        ];
    }

    /** @return array<string, mixed> */
    private function talentNominations(): array
    {
        return [
            'available' => true,
            'active_count' => (new TalentNominationsRepository())->count(['status' => 'active']),
        ];
    }

    /** @return array<string, mixed> */
    private function succession(): array
    {
        $criticalActive = (new CriticalPositionsRepository())->count(['status' => 'active']);
        $successors = new SuccessionSuccessorsRepository();

        return [
            'available' => true,
            'critical_active' => $criticalActive,
            'with_successor' => $successors->countDistinctCriticalWithSuccessor(),
            'ready_now' => $successors->countByReadiness('ready_now'),
        ];
    }
}
