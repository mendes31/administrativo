<?php
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;

$perms = $this->data['buttonPermission'] ?? [];
$filtersId = 'sstFiltersEquipamentos';
$items = $this->data['items'] ?? [];
$filters = $this->data['filters'] ?? [];
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-fire-extinguisher me-2"></i>Equipamentos de segurança</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $urlAdm ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Equipamentos</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
            <span>Cadastro</span>
            <div class="d-flex flex-wrap gap-1 ms-md-auto">
                <?php if (in_array('SstScanEquipamento', $perms, true)): ?>
                <a href="<?= $urlAdm ?>sst-scan-equipamento" class="btn btn-success btn-sm flex-fill flex-md-grow-0"><i class="fas fa-qrcode"></i> <span class="d-none d-sm-inline">Ler </span>QR</a>
                <?php endif; ?>
                <a href="<?= $urlAdm ?>sst-minhas-equipamento-vistorias" class="btn btn-outline-primary btn-sm flex-fill flex-md-grow-0"><i class="fas fa-tasks"></i> Vistorias</a>
                <?php if (in_array('SstExportEquipamentoAuditoriaPdf', $perms, true)): ?>
                <button type="button" class="btn btn-outline-danger btn-sm flex-fill flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#modalAuditoriaConsolidado">
                    <i class="fas fa-file-pdf"></i> Relatório
                </button>
                <?php endif; ?>
                <?php if (in_array('SstCreateEquipamento', $perms, true)): ?>
                <a href="<?= $urlAdm ?>sst-create-equipamento" class="btn btn-success btn-sm flex-fill flex-md-grow-0"><i class="fa-regular fa-square-plus"></i> Novo</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $filtersId ?>">
                    <i class="fa fa-filter me-1"></i> Filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="<?= $filtersId ?>">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="search">Busca</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                            placeholder="Código, série, patrimônio, fabricante…">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Grupo</label>
                        <select name="tipo_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['tipos'] ?? [] as $t): ?>
                            <option value="<?= (int) $t['id'] ?>" <?= (string) ($filters['adms_sst_equipamento_tipo_id'] ?? '') === (string) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Empresa (site)</label>
                        <select name="empresa_contratante" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($this->data['empresas_contratantes'] ?? [] as $slug => $empLabel): ?>
                            <option value="<?= htmlspecialchars((string) $slug) ?>" <?= (string) ($filters['empresa_contratante'] ?? '') === (string) $slug ? 'selected' : '' ?>><?= htmlspecialchars((string) $empLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach (['Ativo', 'Inativo', 'Baixado', 'Bloqueado'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Recarga</label>
                        <select name="recarga_alerta" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <option value="1" <?= !empty($filters['recarga_alerta']) ? 'selected' : '' ?>>Vencida / a vencer (30d)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $urlAdm ?>sst-list-equipamentos" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>

            <?php if ($items === []): ?>
            <div class="alert alert-warning mb-0">Nenhum equipamento encontrado.</div>
            <?php else: ?>
            <div class="d-none d-md-block list-desktop">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead><tr><th>Código</th><th>Nº série</th><th>Grupo</th><th>Empresa (site)</th><th>Localização</th><th>Periodicidade</th><th>Próx. recarga</th><th>Pend.</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $r):
                            $st = SstEquipamentoRecargaHelper::status(
                                $r['data_proxima_recarga'] ?? null,
                                !empty($r['controla_recarga'])
                            );
                        ?>
                            <tr class="<?= $st === 'vencido' ? 'table-danger' : ($st === 'a_vencer' ? 'table-warning' : '') ?>">
                                <td><strong><?= htmlspecialchars($r['codigo'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars(trim((string) ($r['numero_serie'] ?? '')) !== '' ? (string) $r['numero_serie'] : '—') ?></td>
                                <td><?= htmlspecialchars($r['tipo_nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars(SstEquipamentoSiteHelper::label($r['empresa_contratante'] ?? null)) ?></td>
                                <td><?= htmlspecialchars(($r['localizacao'] ?? '') !== '' ? (string) $r['localizacao'] : '—') ?></td>
                                <td><?= htmlspecialchars(SstEquipamentoPeriodicidadeHelper::label((int) ($r['periodicidade_meses'] ?? 1))) ?></td>
                                <td>
                                    <?php if ($st === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php elseif ($st === 'sem_data'): ?>
                                        <span class="badge bg-secondary">Sem data</span>
                                    <?php else: ?>
                                        <?= !empty($r['data_proxima_recarga']) ? date('d/m/Y', strtotime($r['data_proxima_recarga'])) : '—' ?>
                                        <span class="badge <?= SstEquipamentoRecargaHelper::statusBadgeClass($st) ?>"><?= htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($st)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) ($r['vistorias_pendentes'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                                <td class="text-nowrap">
                                    <?php if (in_array('SstViewEquipamento', $perms, true)): ?><a href="<?= $urlAdm ?>sst-view-equipamento/<?= (int) $r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                    <?php if (in_array('SstUpdateEquipamento', $perms, true)): ?><a href="<?= $urlAdm ?>sst-update-equipamento/<?= (int) $r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-block d-md-none list-mobile user-view-stack">
                <?php foreach ($items as $r):
                    $st = SstEquipamentoRecargaHelper::status(
                        $r['data_proxima_recarga'] ?? null,
                        !empty($r['controla_recarga'])
                    );
                    $idEq = (int) ($r['id'] ?? 0);
                    $viewUrl = $urlAdm . 'sst-view-equipamento/' . $idEq;
                    $serie = trim((string) ($r['numero_serie'] ?? ''));
                ?>
                    <article class="user-view-list-card <?= $st === 'vencido' ? 'border-danger' : ($st === 'a_vencer' ? 'border-warning' : '') ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <a class="user-view-list-card-title mb-0 text-decoration-none" href="<?= htmlspecialchars($viewUrl) ?>">
                                <?= htmlspecialchars($r['codigo'] ?? '') ?>
                            </a>
                            <span class="badge bg-secondary flex-shrink-0"><?= htmlspecialchars($r['status'] ?? '') ?></span>
                        </div>
                        <div class="user-view-list-card-meta">
                            <?php if ($st !== null && $st !== 'sem_data'): ?>
                            <span class="badge <?= SstEquipamentoRecargaHelper::statusBadgeClass($st) ?>"><?= htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($st)) ?></span>
                            <?php endif; ?>
                            <?php if ((int) ($r['vistorias_pendentes'] ?? 0) > 0): ?>
                            <span class="badge bg-primary"><?= (int) $r['vistorias_pendentes'] ?> pend.</span>
                            <?php endif; ?>
                        </div>
                        <dl class="user-view-list-card-dl mb-2">
                            <div><dt>Nº série</dt><dd><?= htmlspecialchars($serie !== '' ? $serie : '—') ?></dd></div>
                            <div><dt>Site</dt><dd><?= htmlspecialchars(SstEquipamentoSiteHelper::label($r['empresa_contratante'] ?? null)) ?></dd></div>
                            <div><dt>Local</dt><dd><?= htmlspecialchars(($r['localizacao'] ?? '') !== '' ? (string) $r['localizacao'] : '—') ?></dd></div>
                            <div><dt>Grupo</dt><dd><?= htmlspecialchars($r['tipo_nome'] ?? '—') ?></dd></div>
                        </dl>
                        <div class="d-flex gap-2">
                            <?php if (in_array('SstViewEquipamento', $perms, true)): ?>
                            <a href="<?= htmlspecialchars($viewUrl) ?>" class="btn btn-info btn-sm flex-fill"><i class="fa-regular fa-eye"></i> Ver</a>
                            <?php endif; ?>
                            <?php if (in_array('SstUpdateEquipamento', $perms, true)): ?>
                            <a href="<?= $urlAdm ?>sst-update-equipamento/<?= $idEq ?>" class="btn btn-warning btn-sm flex-fill"><i class="fa-regular fa-pen-to-square"></i> Editar</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="d-none d-md-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <div class="d-flex d-md-none justify-content-center mt-2">
                <?php
                $paginationHtml = $this->data['pagination']['html'] ?? '';
                if ($paginationHtml !== '') {
                    $paginationHtml = str_replace(
                        ['>Primeiro<', '>Anterior<', '>Próximo<', '>Último<'],
                        ['>&laquo;<', '>&lsaquo;<', '>&rsaquo;<', '>&raquo;<'],
                        $paginationHtml
                    );
                    $paginationHtml = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1) ?? $paginationHtml;
                    echo $paginationHtml;
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (in_array('SstExportEquipamentoAuditoriaPdf', $perms, true)): ?>
<?php
$dePadrao = date('Y-01-01');
$atePadrao = date('Y-m-d');
$fEmpresa = (string) ($filters['empresa_contratante'] ?? '');
$fTipo = (string) ($filters['adms_sst_equipamento_tipo_id'] ?? '');
?>
<div class="modal fade" id="modalAuditoriaConsolidado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="<?= $urlAdm ?>sst-export-equipamento-auditoria-pdf" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-pdf me-2 text-danger"></i>Relatório consolidado por período</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">PDF institucional com vistorias completas (checklist, fotos, NC) e recargas de todos os equipamentos (respeita filtros de site/grupo abaixo).</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="c_data_inicio">Data início *</label>
                            <input type="date" name="data_inicio" id="c_data_inicio" class="form-control" required value="<?= htmlspecialchars($dePadrao) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="c_data_fim">Data fim *</label>
                            <input type="date" name="data_fim" id="c_data_fim" class="form-control" required value="<?= htmlspecialchars($atePadrao) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="c_empresa">Empresa (site)</label>
                            <select name="empresa_contratante" id="c_empresa" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($this->data['empresas_contratantes'] ?? [] as $slug => $empLabel): ?>
                                <option value="<?= htmlspecialchars((string) $slug) ?>" <?= $fEmpresa === (string) $slug ? 'selected' : '' ?>><?= htmlspecialchars((string) $empLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="c_tipo">Grupo</label>
                            <select name="tipo_id" id="c_tipo" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($this->data['tipos'] ?? [] as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= $fTipo === (string) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-print me-1"></i>Gerar PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
