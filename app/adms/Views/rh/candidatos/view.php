<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\PositionDisplayHelper;
use App\adms\Helpers\RhCandidatoOrigemHelper;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Services\RhCandidaturaMotivoCatalog;

$csrfVincular = CSRFHelper::generateCSRFToken('form_rh_vincular_candidato_vaga');
$c = $this->data['candidato'] ?? [];
$candidatoId = (int) ($c['id'] ?? 0);
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$vagas = $this->data['vagas'] ?? [];
$anexos = $this->data['anexos'] ?? [];
$entrevistas = $this->data['entrevistas'] ?? [];
$historico = $this->data['historico_candidatura'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$canCreateOferta = in_array('RhOfertasCreate', $perms, true);
$canViewOferta = in_array('RhOfertasView', $perms, true);

$statusProcesso = (string) ($c['status_processo'] ?? '');
$statusProcessoClass = match ($statusProcesso) {
    'aprovado', 'contratado' => 'bg-success-subtle text-success border border-success-subtle',
    'banco_talentos' => 'bg-primary-subtle text-primary border border-primary-subtle',
    'reprovado', 'desistiu' => 'bg-danger-subtle text-danger border border-danger-subtle',
    'em_entrevista', 'em_analise' => 'bg-warning-subtle text-dark border border-warning-subtle',
    default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
};
$lgpdStatus = (string) ($c['lgpd_status'] ?? '');
$lgpdClass = strcasecmp($lgpdStatus, 'Ativo') === 0 || strcasecmp($lgpdStatus, 'ativo') === 0
    ? 'bg-primary-subtle text-primary border border-primary-subtle'
    : 'bg-secondary-subtle text-secondary border border-secondary-subtle';

$cidadeUf = trim((string) ($c['cidade'] ?? ''));
if (!empty($c['estado'])) {
    $cidadeUf .= ($cidadeUf !== '' ? ' / ' : '') . (string) $c['estado'];
}

$proximasOfertas = [];
foreach ($vagas as $vagaCheck) {
    if (($vagaCheck['status'] ?? '') !== 'aprovado') {
        continue;
    }
    $cidCheck = (int) ($vagaCheck['id'] ?? 0);
    $ofertaCheck = $this->data['ofertas_por_candidatura'][$cidCheck] ?? null;
    $ofertaStatusCheck = (string) ($ofertaCheck['status'] ?? '');
    $ativaCheck = $ofertaCheck && in_array($ofertaStatusCheck, RhOfertasRepository::ATIVOS, true);
    if (!$ativaCheck && $canCreateOferta) {
        $proximasOfertas[] = $vagaCheck;
    }
}

$vagaStatusBadge = static function (string $status): string {
    return match ($status) {
        'candidatado' => 'bg-info-subtle text-info border border-info-subtle',
        'em_entrevista', 'em_analise' => 'bg-warning-subtle text-dark border border-warning-subtle',
        'aprovado' => 'bg-success-subtle text-success border border-success-subtle',
        'banco_talentos' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'reprovado' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'desistiu' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    };
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3 mb-0 text-truncate" title="<?= htmlspecialchars((string) ($c['nome'] ?? 'Candidato')) ?>">
            <?= htmlspecialchars((string) ($c['nome'] ?? 'Detalhes do Candidato')) ?>
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto flex-shrink-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>rh-candidatos" class="text-decoration-none">Currículos</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0f9ff 45%, #f8fafc 100%);">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:3.25rem;height:3.25rem;">
                        <i class="fas fa-user-tie fa-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                            <span class="badge rounded-pill text-bg-light border text-muted">Candidato #<?= $candidatoId ?></span>
                            <?php if ($statusProcesso !== ''): ?>
                                <span class="badge rounded-pill <?= $statusProcessoClass ?>"><?= htmlspecialchars($statusProcesso) ?></span>
                            <?php endif; ?>
                            <?php if ($lgpdStatus !== ''): ?>
                                <span class="badge rounded-pill <?= $lgpdClass ?>">LGPD: <?= htmlspecialchars($lgpdStatus) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($c['score'])): ?>
                                <?php
                                $score = (int) $c['score'];
                                $scoreClass = $score >= 80 ? 'bg-success' : ($score >= 60 ? 'bg-info' : ($score >= 40 ? 'bg-warning text-dark' : 'bg-danger'));
                                ?>
                                <span class="badge rounded-pill <?= $scoreClass ?>">Score <?= $score ?>/100</span>
                            <?php endif; ?>
                            <?php if (!empty($c['classificacao'])): ?>
                                <span class="badge rounded-pill bg-primary"><?= htmlspecialchars((string) $c['classificacao']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-1 fw-semibold"><?= htmlspecialchars((string) ($c['nome'] ?? '')) ?></h5>
                        <p class="mb-0 small text-muted">
                            <?= htmlspecialchars(RhCandidatoOrigemHelper::label((string) ($c['origem'] ?? ''))) ?>
                            <?php if (!empty($c['area_interesse'])): ?>
                                · Interesse: <?= htmlspecialchars((string) $c['area_interesse']) ?>
                            <?php endif; ?>
                            <?php if ($cidadeUf !== ''): ?>
                                · <?= htmlspecialchars($cidadeUf) ?>
                            <?php endif; ?>
                        </p>
                        <div class="d-flex flex-wrap gap-3 mt-2 small">
                            <?php if (!empty($c['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars((string) $c['email']) ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars((string) $c['email']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($c['telefone'])): ?>
                                <span class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars((string) $c['telefone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($base) ?>rh-candidatos-edit/<?= $candidatoId ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <a href="<?= htmlspecialchars($base) ?>rh-candidatos" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                    <?php
                    $log_resumo = $this->data['log_resumo'] ?? [];
                    $log_btn_class = 'btn btn-outline-info btn-sm';
                    include __DIR__ . '/../../partials/button_log_alteracoes.php';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($proximasOfertas !== []): ?>
        <div class="card mb-4 border-0 shadow-sm border-start border-success border-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                <div>
                    <h6 class="mb-1 fw-semibold"><i class="fas fa-handshake text-success me-2"></i>Pronto para oferta</h6>
                    <p class="mb-0 small text-muted">
                        Controle interno do RH (salário, contrato, validade). Não envia link ao candidato — o aceite/recusa é registrado pelo recrutador.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($proximasOfertas as $po): ?>
                        <a href="<?= htmlspecialchars($base) ?>rh-ofertas-create/<?= (int) ($po['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i>Criar oferta
                            <?php if (count($proximasOfertas) > 1): ?>
                                <span class="opacity-75">— <?= htmlspecialchars(mb_strimwidth((string) ($po['vaga_titulo'] ?? ''), 0, 28, '…')) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing:.03em;">Status do processo</div>
                    <span class="badge rounded-pill <?= $statusProcessoClass ?>"><?= htmlspecialchars($statusProcesso !== '' ? $statusProcesso : '—') ?></span>
                    <div class="form-text small mb-0 mt-1">Projeção dos vínculos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing:.03em;">LGPD</div>
                    <span class="badge rounded-pill <?= $lgpdClass ?>"><?= htmlspecialchars($lgpdStatus !== '' ? $lgpdStatus : '—') ?></span>
                    <div class="small text-muted mt-1">
                        Consent.: <?= FormatHelper::formatDateTime($c['lgpd_data_consentimento'] ?? null) ?: '—' ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing:.03em;">Área de interesse</div>
                    <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($c['area_interesse'] ?? '—')) ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing:.03em;">Local</div>
                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($cidadeUf !== '' ? $cidadeUf : '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <?php if (!empty($c['graduacao'])): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-graduation-cap text-primary me-2"></i>Formação</h6>
                        <div style="white-space:pre-wrap;"><?= htmlspecialchars((string) $c['graduacao']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($c['ultima_experiencia'])): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-3">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-briefcase text-info me-2"></i>Última experiência</h6>
                        <div style="white-space:pre-wrap;"><?= htmlspecialchars((string) $c['ultima_experiencia']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($c['classificacao_observacoes'])): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-star text-warning me-2"></i>Obs. classificação</h6>
                        <div style="white-space:pre-wrap;"><?= htmlspecialchars((string) $c['classificacao_observacoes']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-sticky-note text-secondary me-2"></i>Observações</h6>
                    <?php if (trim((string) ($c['observacoes'] ?? '')) !== ''): ?>
                        <div style="white-space:pre-wrap;"><?= htmlspecialchars((string) $c['observacoes']) ?></div>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">Nenhuma observação registrada.</p>
                    <?php endif; ?>
                    <?php if (!empty($c['lgpd_motivo_anonimizacao'])): ?>
                        <hr>
                        <div class="small text-muted">Motivo anonimização: <?= htmlspecialchars((string) $c['lgpd_motivo_anonimizacao']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($c['lgpd_data_expiracao'])): ?>
                        <div class="small text-muted">LGPD expira: <?= FormatHelper::formatDateTime($c['lgpd_data_expiracao']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-briefcase me-2 text-success"></i>Vagas vinculadas (<?= count($vagas) ?>)</h6>
            <?php if (!empty($this->data['vagas_disponiveis'])): ?>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalVincularVaga">
                    <i class="fas fa-link me-1"></i>Vincular em vaga
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <?php if ($vagas === []): ?>
                <p class="text-muted mb-0">Nenhuma vaga vinculada.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($vagas as $vaga):
                        $cid = (int) ($vaga['id'] ?? 0);
                        $st = (string) ($vaga['status'] ?? '');
                        $ofertaAtiva = $this->data['ofertas_por_candidatura'][$cid] ?? null;
                        $ofertaStatus = (string) ($ofertaAtiva['status'] ?? '');
                        $ofertaEhAtiva = $ofertaAtiva && in_array($ofertaStatus, RhOfertasRepository::ATIVOS, true);
                        $precisaOferta = $st === 'aprovado' && !$ofertaEhAtiva && $canCreateOferta;
                        ?>
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 border shadow-sm <?= $precisaOferta ? 'border-success' : '' ?>">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                        <h6 class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($vaga['vaga_titulo'] ?? '')) ?></h6>
                                        <span class="badge rounded-pill <?= $vagaStatusBadge($st) ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $st))) ?>
                                        </span>
                                    </div>
                                    <p class="small text-muted mb-3">
                                        <?= htmlspecialchars((string) ($vaga['area_nome'] ?? '—')) ?>
                                        · <?= htmlspecialchars(PositionDisplayHelper::formatForDisplay((string) ($vaga['cargo_nome'] ?? '')) ?: '—') ?>
                                        · <?= htmlspecialchars(RhCandidatoOrigemHelper::label((string) ($vaga['canal_origem'] ?? ''))) ?>
                                        <br>Candidatura: <?= FormatHelper::formatDateTime($vaga['data_candidatura'] ?? null) ?: '—' ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= htmlspecialchars($base) ?>rh-vagas-view/<?= (int) ($vaga['rh_vaga_id'] ?? 0) ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye me-1"></i>Ver vaga
                                        </a>
                                        <?php if ($ofertaEhAtiva && $canViewOferta): ?>
                                            <a href="<?= htmlspecialchars($base) ?>rh-ofertas-view/<?= (int) $ofertaAtiva['id'] ?>"
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-file-signature me-1"></i>Ver oferta
                                                <span class="opacity-75">(<?= htmlspecialchars($ofertaStatus) ?>)</span>
                                            </a>
                                        <?php elseif ($precisaOferta && $proximasOfertas === []): ?>
                                            <a href="<?= htmlspecialchars($base) ?>rh-ofertas-create/<?= $cid ?>"
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-handshake me-1"></i>Criar oferta
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-paperclip me-2 text-primary"></i>Currículos / anexos (<?= count($anexos) ?>)</h6>
                </div>
                <div class="card-body px-4 pt-0">
                    <?php if ($anexos === []): ?>
                        <p class="text-muted mb-0 small">Nenhum anexo enviado.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($anexos as $anexo): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">
                                            <?php if (!empty($anexo['arquivo_caminho'])): ?>
                                                <a href="<?= htmlspecialchars($base) ?>rh-candidatos-download-anexo/<?= (int) $anexo['id'] ?>"
                                                   target="_blank" class="text-decoration-none">
                                                    <i class="fas fa-download me-1"></i>
                                                    <?= htmlspecialchars((string) ($anexo['nome_original'] ?? basename((string) $anexo['arquivo_caminho']))) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($anexo['tipo'] ?? '')) ?>
                                            · <?= FormatHelper::formatDateTime($anexo['created_at'] ?? null) ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-calendar-alt me-2 text-warning"></i>Entrevistas (<?= count($entrevistas) ?>)</h6>
                    <a href="<?= htmlspecialchars($base) ?>rh-entrevistas-create?candidato_id=<?= $candidatoId ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-plus me-1"></i>Agendar
                    </a>
                </div>
                <div class="card-body px-4 pt-0">
                    <?php if ($entrevistas === []): ?>
                        <p class="text-muted mb-0 small">Nenhuma entrevista registrada.</p>
                    <?php else: ?>
                        <div class="vstack gap-2">
                            <?php foreach ($entrevistas as $ent):
                                $res = (string) ($ent['resultado'] ?? '');
                                $resClass = $res === 'aprovado' ? 'bg-success' : ($res === 'reprovado' ? 'bg-danger' : 'bg-warning text-dark');
                                ?>
                                <div class="border rounded-3 px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold small"><?= FormatHelper::formatDateTime($ent['data_hora'] ?? '') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars(ucfirst((string) ($ent['tipo'] ?? '—'))) ?></div>
                                    </div>
                                    <?php if ($res !== ''): ?>
                                        <span class="badge <?= $resClass ?>"><?= htmlspecialchars(ucfirst($res)) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($base) ?>rh-entrevistas-view/<?= (int) $ent['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 px-4">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-history me-2 text-secondary"></i>Linha do tempo (<?= count($historico) ?>)</h6>
        </div>
        <div class="card-body px-4 pt-0">
            <?php if ($historico === []): ?>
                <p class="text-muted mb-0 small">Nenhum evento de candidatura registrado ainda.</p>
            <?php else: ?>
                <div class="d-md-none vstack gap-2">
                    <?php foreach ($historico as $evento):
                        $tipoLabel = match ($evento['tipo_evento'] ?? '') {
                            'vinculada' => 'Vinculada',
                            'movimentada' => 'Movimentada',
                            'desvinculada' => 'Desvinculada',
                            'backfill' => 'Estado inicial',
                            default => ucfirst((string) ($evento['tipo_evento'] ?? '—')),
                        };
                        $de = $evento['status_anterior'] ?? null;
                        $para = $evento['status_novo'] ?? null;
                        $statusTxt = '—';
                        if ($de && $para) {
                            $statusTxt = ucfirst(str_replace('_', ' ', (string) $de))
                                . ' → '
                                . ucfirst(str_replace('_', ' ', (string) $para));
                        } elseif ($para) {
                            $statusTxt = ucfirst(str_replace('_', ' ', (string) $para));
                        } elseif ($de) {
                            $statusTxt = ucfirst(str_replace('_', ' ', (string) $de)) . ' → (encerrado)';
                        }
                        $motivoLabel = RhCandidaturaMotivoCatalog::label($evento['motivo_codigo'] ?? null);
                        ?>
                        <div class="border rounded-3 p-3">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <span class="fw-semibold small"><?= htmlspecialchars($tipoLabel) ?></span>
                                <span class="small text-muted text-nowrap"><?= FormatHelper::formatDateTime($evento['ocorrido_em'] ?? null) ?></span>
                            </div>
                            <div class="small text-break mb-1">
                                <?php if (!empty($evento['rh_vaga_id'])): ?>
                                    <a href="<?= htmlspecialchars($base) ?>rh-vagas-view/<?= (int) $evento['rh_vaga_id'] ?>">
                                        <?= htmlspecialchars((string) ($evento['vaga_titulo'] ?? ('Vaga #' . (int) $evento['rh_vaga_id']))) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Sem vaga</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted"><?= htmlspecialchars($statusTxt) ?></div>
                            <div class="small text-muted">
                                <?= htmlspecialchars(ucfirst((string) ($evento['origem'] ?? '—'))) ?>
                                · <?= htmlspecialchars($motivoLabel) ?>
                                · <?= htmlspecialchars((string) ($evento['alterado_por_nome'] ?? '—')) ?>
                            </div>
                            <?php if (!empty($evento['observacoes'])): ?>
                                <div class="small mt-1 text-break"><?= htmlspecialchars((string) $evento['observacoes']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-none d-md-block table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Quando</th>
                                <th>Evento</th>
                                <th>Vaga</th>
                                <th>Status</th>
                                <th>Origem</th>
                                <th>Motivo</th>
                                <th>Por</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $evento):
                                $tipoLabel = match ($evento['tipo_evento'] ?? '') {
                                    'vinculada' => 'Vinculada',
                                    'movimentada' => 'Movimentada',
                                    'desvinculada' => 'Desvinculada',
                                    'backfill' => 'Estado inicial',
                                    default => ucfirst((string) ($evento['tipo_evento'] ?? '—')),
                                };
                                $de = $evento['status_anterior'] ?? null;
                                $para = $evento['status_novo'] ?? null;
                                $statusTxt = '—';
                                if ($de && $para) {
                                    $statusTxt = ucfirst(str_replace('_', ' ', (string) $de))
                                        . ' → '
                                        . ucfirst(str_replace('_', ' ', (string) $para));
                                } elseif ($para) {
                                    $statusTxt = ucfirst(str_replace('_', ' ', (string) $para));
                                } elseif ($de) {
                                    $statusTxt = ucfirst(str_replace('_', ' ', (string) $de)) . ' → (encerrado)';
                                }
                                $motivoLabel = RhCandidaturaMotivoCatalog::label($evento['motivo_codigo'] ?? null);
                                ?>
                                <tr>
                                    <td class="small text-nowrap"><?= FormatHelper::formatDateTime($evento['ocorrido_em'] ?? null) ?></td>
                                    <td class="small"><?= htmlspecialchars($tipoLabel) ?></td>
                                    <td class="small">
                                        <?php if (!empty($evento['rh_vaga_id'])): ?>
                                            <a href="<?= htmlspecialchars($base) ?>rh-vagas-view/<?= (int) $evento['rh_vaga_id'] ?>">
                                                <?= htmlspecialchars((string) ($evento['vaga_titulo'] ?? ('Vaga #' . (int) $evento['rh_vaga_id']))) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($statusTxt) ?></td>
                                    <td class="small"><?= htmlspecialchars(ucfirst((string) ($evento['origem'] ?? '—'))) ?></td>
                                    <td class="small"><?= htmlspecialchars($motivoLabel) ?></td>
                                    <td class="small"><?= htmlspecialchars((string) ($evento['alterado_por_nome'] ?? '—')) ?></td>
                                    <td class="small"><?= htmlspecialchars(mb_strimwidth((string) ($evento['observacoes'] ?? ''), 0, 80, '…')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($this->data['vagas_disponiveis'])): ?>
        <div class="modal fade" id="modalVincularVaga" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vincular candidato em vaga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="formVincularVaga">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfVincular) ?>">
                        <div class="modal-body">
                            <input type="hidden" name="candidato_id" value="<?= $candidatoId ?>">
                            <div class="mb-3">
                                <label for="vaga_id" class="form-label">Selecione as vagas *</label>
                                <select name="vaga_id[]" id="vaga_id" class="form-select" multiple size="8" required>
                                    <?php foreach ($this->data['vagas_disponiveis'] as $v): ?>
                                        <option value="<?= (int) $v['id'] ?>">
                                            <?= htmlspecialchars((string) ($v['titulo'] ?? '')) ?>
                                            <?php if (!empty($v['area_nome'])): ?> | <?= htmlspecialchars((string) $v['area_nome']) ?><?php endif; ?>
                                            <?php if (!empty($v['cargo_nome'])): ?> | <?= htmlspecialchars(PositionDisplayHelper::formatForDisplay((string) $v['cargo_nome'])) ?><?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Ctrl/Cmd para selecionar várias.</small>
                            </div>
                            <div class="mb-0">
                                <label for="observacoes_vaga" class="form-label">Observações</label>
                                <textarea name="observacoes" id="observacoes_vaga" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Vincular</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('formVincularVaga')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const vagaSelect = document.getElementById('vaga_id');
            const vagasSelecionadas = Array.from(vagaSelect.selectedOptions).map(opt => opt.value);
            formData.delete('vaga_id[]');
            vagasSelecionadas.forEach(vagaId => formData.append('vaga_id[]', vagaId));
            fetch('<?= htmlspecialchars($base) ?>rh-vincular-candidato-vaga', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao vincular em vaga.'));
        });
        </script>
    <?php endif; ?>
</div>
