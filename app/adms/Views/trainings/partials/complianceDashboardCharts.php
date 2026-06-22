<?php
/**
 * 4 gráficos alinhados aos KPIs (col-lg-3 cada).
 *
 * Incluído por complianceDashboard.php — variáveis definidas no escopo pai.
 */
/** @var callable(int, int): float $percentRealizados */
/** @var array<int, string> $filterBadges */
/** @var callable(int): int $summaryBarPct */
/** @var string $summaryVisualTitle */
/** @var string $summaryVisualSubtitle */
/** @var bool $hasFilters */
/** @var int $totalObrigacoes */
/** @var int $totalRealizados */
/** @var int $totalPendentes */
/** @var float $percentRealizadosGlobal */
/** @var array<int, array<string, mixed>> $departmentStatsAll */
/** @var array<int, array<string, mixed>> $positionStatsAll */
/** @var array<int, array<string, mixed>> $userStatsAll */

use App\adms\Helpers\PositionDisplayHelper;

$renderStackedChart = static function (
    array $rows,
    callable $labelResolver
) use ($percentRealizados): void {
    if ($rows === []) {
        echo '<p class="text-muted small mb-0 text-center py-3">Nenhum dado no recorte.</p>';
        return;
    }

    foreach ($rows as $row) {
        $totalObrig = (int)($row['total_obrigacoes'] ?? 0);
        if ($totalObrig <= 0) {
            continue;
        }
        $realizados = (int)($row['realizados'] ?? 0);
        $pendentes = (int)($row['pendentes'] ?? 0);
        $pctReal = $percentRealizados($totalObrig, $realizados);
        $pctPend = max(0, round(100 - $pctReal, 1));
        $label = (string)$labelResolver($row);
        if (mb_strlen($label) > 28) {
            $label = mb_substr($label, 0, 26) . '…';
        }
        ?>
        <div class="compliance-stack-row">
            <div class="d-flex justify-content-between gap-1 mb-1">
                <span class="compliance-stack-label" title="<?= htmlspecialchars((string)$labelResolver($row)) ?>"><?= htmlspecialchars($label) ?></span>
                <span class="compliance-stack-meta text-nowrap">
                    <span class="text-success"><?= number_format($realizados) ?></span>/<span class="text-danger"><?= number_format($pendentes) ?></span>
                </span>
            </div>
            <div class="progress compliance-stack-bar">
                <?php if ($realizados > 0): ?>
                    <div class="progress-bar bg-success" style="width: <?= min(100, $pctReal) ?>%;"></div>
                <?php endif; ?>
                <?php if ($pendentes > 0): ?>
                    <div class="progress-bar bg-danger" style="width: <?= min(100, $pctPend) ?>%;"></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
};
?>

<style>
    .compliance-charts-row {
        align-items: stretch;
    }
    .compliance-charts-row > [class*="col-"] {
        display: flex;
    }
    .compliance-chart-card {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    .compliance-chart-card .card-header {
        min-height: 88px;
        flex-shrink: 0;
    }
    .compliance-chart-card .card-body {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: .65rem .75rem;
        min-height: 196px;
    }
    .compliance-chart-body-fill {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        width: 100%;
        min-height: 168px;
    }
    .compliance-mini-bars {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: .25rem;
        height: 88px;
        width: 100%;
        overflow: hidden;
    }
    .compliance-mini-bar-col {
        flex: 1 1 0;
        min-width: 0;
        max-width: 33.333%;
        text-align: center;
    }
    .compliance-mini-bar-track {
        height: 58px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        overflow: hidden;
    }
    .compliance-mini-bar {
        width: 70%;
        max-width: 28px;
        border-radius: 3px 3px 0 0;
        min-height: 4px;
    }
    .compliance-mini-bar-col .small {
        font-size: .7rem;
    }
    .compliance-mini-bar-col .fw-semibold {
        font-size: .8rem;
        line-height: 1.1;
    }
    .compliance-stack-scroll {
        flex: 1 1 auto;
        min-height: 168px;
        max-height: 168px;
        overflow-x: hidden;
        overflow-y: auto;
    }
    .compliance-stack-row + .compliance-stack-row {
        margin-top: .65rem;
    }
    .compliance-stack-label {
        font-size: .72rem;
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1 1 auto;
        min-width: 0;
    }
    .compliance-stack-meta {
        font-size: .68rem;
        color: #6c757d;
    }
    .compliance-stack-bar {
        height: 8px;
    }
    .compliance-filter-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
        margin-top: .35rem;
    }
    .compliance-filter-badges .badge {
        font-weight: 500;
        font-size: .65rem;
    }
    .compliance-chart-legend {
        font-size: .65rem;
        white-space: nowrap;
    }
</style>

<div class="row mb-4 g-3 compliance-charts-row">
    <!-- 1. Resumo visual -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow compliance-chart-card">
            <div class="card-header py-2">
                <h6 class="m-0 small font-weight-bold text-primary">
                    <i class="fas fa-chart-column me-1"></i><?= htmlspecialchars($summaryVisualTitle) ?>
                </h6>
                <div class="small text-muted mt-1 text-truncate" title="<?= htmlspecialchars($summaryVisualSubtitle) ?>">
                    <?= htmlspecialchars($summaryVisualSubtitle) ?>
                </div>
                <?php if ($hasFilters && $filterBadges !== []): ?>
                    <div class="compliance-filter-badges">
                        <?php foreach ($filterBadges as $badge): ?>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($badge) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="compliance-chart-body-fill">
                    <?php if ($totalObrigacoes > 0): ?>
                        <div class="compliance-mini-bars">
                        <div class="compliance-mini-bar-col">
                            <div class="compliance-mini-bar-track">
                                <div class="compliance-mini-bar bg-primary" style="height: <?= $summaryBarPct($totalObrigacoes) ?>%;"></div>
                            </div>
                            <div class="small text-muted">Total</div>
                            <div class="fw-semibold"><?= number_format($totalObrigacoes) ?></div>
                        </div>
                        <div class="compliance-mini-bar-col">
                            <div class="compliance-mini-bar-track">
                                <div class="compliance-mini-bar bg-success" style="height: <?= $summaryBarPct($totalRealizados) ?>%;"></div>
                            </div>
                            <div class="small text-muted">Realiz.</div>
                            <div class="fw-semibold text-success"><?= number_format($totalRealizados) ?></div>
                        </div>
                        <div class="compliance-mini-bar-col">
                            <div class="compliance-mini-bar-track">
                                <div class="compliance-mini-bar bg-danger" style="height: <?= $summaryBarPct($totalPendentes) ?>%;"></div>
                            </div>
                            <div class="small text-muted">Pend.</div>
                            <div class="fw-semibold text-danger"><?= number_format($totalPendentes) ?></div>
                        </div>
                    </div>
                        <div class="text-center small text-muted mt-2">
                            <?= $hasFilters ? 'Recorte: ' : 'Geral: ' ?><strong><?= $percentRealizadosGlobal ?>%</strong>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0 text-center">Sem obrigações.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Departamento -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow compliance-chart-card">
            <div class="card-header py-2 d-flex justify-content-between align-items-start gap-1">
                <h6 class="m-0 small font-weight-bold text-primary">
                    <i class="fas fa-building me-1"></i>Cumprimento por Depto.
                </h6>
                <span class="compliance-chart-legend text-muted">
                    <span class="badge bg-success p-1">&nbsp;</span>/<span class="badge bg-danger p-1">&nbsp;</span>
                </span>
            </div>
            <div class="card-body">
                <div class="compliance-chart-body-fill">
                    <div class="compliance-stack-scroll">
                    <?php $renderStackedChart(
                        $departmentStatsAll ?? [],
                        static fn(array $row): string => (string)($row['group_name'] ?? '')
                    ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Cargo -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow compliance-chart-card">
            <div class="card-header py-2 d-flex justify-content-between align-items-start gap-1">
                <h6 class="m-0 small font-weight-bold text-primary">
                    <i class="fas fa-briefcase me-1"></i>Cumprimento por Cargo
                </h6>
                <span class="compliance-chart-legend text-muted">
                    <span class="badge bg-success p-1">&nbsp;</span>/<span class="badge bg-danger p-1">&nbsp;</span>
                </span>
            </div>
            <div class="card-body">
                <div class="compliance-chart-body-fill">
                    <div class="compliance-stack-scroll">
                    <?php $renderStackedChart(
                        $positionStatsAll ?? [],
                        static fn(array $row): string => PositionDisplayHelper::formatForDisplay((string)($row['group_name'] ?? ''))
                    ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Colaborador -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow compliance-chart-card">
            <div class="card-header py-2 d-flex justify-content-between align-items-start gap-1">
                <h6 class="m-0 small font-weight-bold text-primary">
                    <i class="fas fa-user me-1"></i>Cumprimento por Colab.
                </h6>
                <span class="compliance-chart-legend text-muted">
                    <span class="badge bg-success p-1">&nbsp;</span>/<span class="badge bg-danger p-1">&nbsp;</span>
                </span>
            </div>
            <div class="card-body">
                <div class="compliance-chart-body-fill">
                    <div class="compliance-stack-scroll">
                    <?php $renderStackedChart(
                        $userStatsAll ?? [],
                        static fn(array $row): string => (string)($row['user_name'] ?? '')
                    ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
