<?php
/**
 * Seções paginadas: Departamento → Cargo → Colaborador
 *
 * Incluído por complianceDashboard.php — variáveis definidas no escopo pai.
 */
/** @var callable(int, int): float $percentRealizados */
/** @var callable(array<string, mixed>): array<string, mixed> $buildComplianceQuery */
/** @var callable(string): string $buildSectionExportSuffix */
/** @var bool $hasFilters */
/** @var array<int, array<string, mixed>> $departmentStats */
/** @var array{total: int, total_pages: int, current_page: int, per_page: int} $departmentPagination */
/** @var array<int, array<string, mixed>> $positionStats */
/** @var array{total: int, total_pages: int, current_page: int, per_page: int} $positionPagination */
/** @var array<int, array<string, mixed>> $userStats */
/** @var array{total: int, total_pages: int, current_page: int, per_page: int} $userPagination */

use App\adms\Helpers\PositionDisplayHelper;
$renderGroupTableRows = static function (array $rows, bool $formatPosition) use ($percentRealizados, $hasFilters): void {
    $rendered = false;

    foreach ($rows as $row) {
        $realizados = (int)($row['realizados'] ?? 0);
        $pendentes = (int)($row['pendentes'] ?? 0);
        $totalObrig = (int)($row['total_obrigacoes'] ?? 0);
        if ($totalObrig <= 0 && $hasFilters) {
            continue;
        }
        $rendered = true;
        $pct = $percentRealizados($totalObrig, $realizados);
        $label = (string)($row['group_name'] ?? '');
        if ($formatPosition) {
            $label = PositionDisplayHelper::formatForDisplay($label);
        }
        echo '<tr>';
        echo '<td>' . htmlspecialchars($label) . '</td>';
        echo '<td class="text-center">' . number_format($totalObrig) . '</td>';
        echo '<td class="text-center"><span class="badge bg-success">' . number_format($realizados) . '</span></td>';
        echo '<td class="text-center">';
        if ($pendentes > 0) {
            echo '<span class="badge bg-danger">' . number_format($pendentes) . '</span>';
        } else {
            echo '<span class="badge bg-secondary">0</span>';
        }
        echo '</td>';
        echo '<td class="text-center">' . $pct . '%</td>';
        echo '</tr>';
    }

    if (!$rendered) {
        echo '<tr><td colspan="5" class="text-center text-muted">Nenhum dado encontrado.</td></tr>';
    }
};

$renderPagination = static function (string $pageParam, array $pagination) use ($buildComplianceQuery): void {
    if (empty($pagination['total_pages']) || (int)$pagination['total_pages'] <= 1) {
        return;
    }

    $currentPage = (int)($pagination['current_page'] ?? 1);
    $totalPages = (int)$pagination['total_pages'];
    $perPage = (int)($pagination['per_page'] ?? 10);
    $total = (int)($pagination['total'] ?? 0);

    $link = static function (int $page) use ($buildComplianceQuery, $pageParam): string {
        return '?' . http_build_query($buildComplianceQuery([$pageParam => $page]));
    };
    ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center mb-1">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $link($currentPage - 1) ?>"><i class="fas fa-chevron-left"></i></a>
            </li>
            <?php
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= $link(1) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= $link($totalPages) ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $link($currentPage + 1) ?>"><i class="fas fa-chevron-right"></i></a>
            </li>
        </ul>
        <p class="text-center text-muted small mb-0">
            Mostrando <?= min(($currentPage - 1) * $perPage + 1, $total) ?>
            a <?= min($currentPage * $perPage, $total) ?>
            de <?= number_format($total) ?> registros
        </p>
    </nav>
    <?php
};
?>

<style>
    .compliance-cargo-scroll {
        max-height: 320px;
        overflow-y: auto;
        padding-right: .25rem;
    }
    .compliance-cargo-row + .compliance-cargo-row {
        margin-top: .85rem;
    }
    .compliance-cargo-label {
        font-size: .8rem;
        line-height: 1.2;
    }
    .compliance-cargo-meta {
        font-size: .75rem;
        color: #6c757d;
    }
    .compliance-stacked-legend span {
        font-size: .75rem;
        margin-right: .75rem;
    }
</style>

<!-- 1. Departamento -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-building me-2"></i>Estatísticas por Departamento
                </h6>
                <div class="d-flex flex-wrap gap-1">
                    <?php $deptExport = $buildSectionExportSuffix('departamento'); ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $deptExport ?>export=excel" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $deptExport ?>export=pdf" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Departamento</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Realizados</th>
                                <th class="text-center">Pendentes</th>
                                <th class="text-center">Realizados %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $renderGroupTableRows($departmentStats, false); ?>
                        </tbody>
                    </table>
                </div>
                <?php $renderPagination('page_depto', $departmentPagination); ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. Cargo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-briefcase me-2"></i>Estatísticas por Cargo
                </h6>
                <div class="d-flex flex-wrap gap-1">
                    <?php $cargoExport = $buildSectionExportSuffix('cargo'); ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $cargoExport ?>export=excel" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $cargoExport ?>export=pdf" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cargo</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Realizados</th>
                                <th class="text-center">Pendentes</th>
                                <th class="text-center">Realizados %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $renderGroupTableRows($positionStats, true); ?>
                        </tbody>
                    </table>
                </div>
                <?php $renderPagination('page_cargo', $positionPagination); ?>
            </div>
        </div>
    </div>
</div>

<!-- 3. Colaborador -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users me-2"></i>Estatísticas por Colaborador
                </h6>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if (!empty($userPagination['total'])): ?>
                        <small class="text-muted"><?= number_format((int)$userPagination['total']) ?> colaborador(es)</small>
                    <?php endif; ?>
                    <?php $colabExport = $buildSectionExportSuffix('colaborador'); ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $colabExport ?>export=excel" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $colabExport ?>export=pdf" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cargo</th>
                                <th>Colaborador</th>
                                <th>Departamento</th>
                                <th class="text-center">Nº Treinamentos</th>
                                <th class="text-center">Realizados</th>
                                <th class="text-center">Realizados %</th>
                                <th class="text-center">Pendentes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($userStats !== []): ?>
                                <?php foreach ($userStats as $row): ?>
                                    <?php
                                    $realizados = (int)($row['realizados'] ?? 0);
                                    $pendentes = (int)($row['pendentes'] ?? 0);
                                    $totalObrig = (int)($row['total_obrigacoes'] ?? 0);
                                    $pct = $percentRealizados($totalObrig, $realizados);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(PositionDisplayHelper::formatForDisplay((string)($row['position_name'] ?? ''))) ?></td>
                                        <td><?= htmlspecialchars((string)($row['user_name'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['department_name'] ?? '')) ?></td>
                                        <td class="text-center"><?= number_format($totalObrig) ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?= number_format($realizados) ?></span></td>
                                        <td class="text-center"><?= $pct ?>%</td>
                                        <td class="text-center">
                                            <?php if ($pendentes > 0): ?>
                                                <span class="badge bg-danger"><?= number_format($pendentes) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Nenhum colaborador com vínculos na matriz.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php $renderPagination('page_colab', $userPagination); ?>
            </div>
        </div>
    </div>
</div>
