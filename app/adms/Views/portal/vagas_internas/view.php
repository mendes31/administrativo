<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\PositionDisplayHelper;

$v = $this->data['vaga'] ?? [];
$form = $this->data['form'] ?? [];
$termo = $this->data['lgpd_termo'] ?? null;
$ja = !empty($this->data['ja_candidatou']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$id = (int) ($v['id'] ?? 0);

$titulo = (string) ($v['titulo'] ?? 'Vaga');
$area = (string) ($v['area_nome'] ?? '—');
$cargo = PositionDisplayHelper::formatForDisplay((string) ($v['cargo_nome'] ?? '')) ?: '—';
$contrato = (string) ($v['tipo_contrato'] ?? '—');
$local = (string) ($v['local_trabalho'] ?? '—');
$prazo = FormatHelper::formatDateTime($v['data_limite_inscricao'] ?? null) ?: 'Sem prazo';
$abertura = FormatHelper::formatDateTime($v['data_abertura'] ?? null);

$salarioLabel = null;
if (!empty($v['mostrar_salario']) && (!empty($v['salario_min']) || !empty($v['salario_max']))) {
    $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float) $v['salario_min'], 2, ',', '.') : '';
    $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float) $v['salario_max'], 2, ',', '.') : '';
    $salarioLabel = trim($salMin . ($salMin && $salMax ? ' – ' : '') . $salMax);
}

$metaCards = [
    ['icon' => 'fa-building', 'label' => 'Área', 'value' => $area, 'tone' => 'primary'],
    ['icon' => 'fa-user-tie', 'label' => 'Cargo', 'value' => $cargo, 'tone' => 'success'],
    ['icon' => 'fa-file-contract', 'label' => 'Contrato', 'value' => $contrato, 'tone' => 'warning'],
    ['icon' => 'fa-map-marker-alt', 'label' => 'Local', 'value' => $local, 'tone' => 'info'],
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 text-truncate" title="<?= htmlspecialchars($titulo) ?>"><?= htmlspecialchars($titulo) ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto flex-shrink-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>vagas-internas" class="text-decoration-none">Vagas internas</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4"
             style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 45%, #f8fafc 100%);">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:3rem;height:3rem;">
                        <i class="fas fa-briefcase fa-lg"></i>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Aberta</span>
                            <?php if ($ja): ?>
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="fas fa-check me-1"></i>Candidatura enviada
                                </span>
                            <?php endif; ?>
                            <span class="badge rounded-pill text-bg-light border text-muted">Vaga #<?= $id ?></span>
                        </div>
                        <h5 class="mb-1 fw-semibold"><?= htmlspecialchars($titulo) ?></h5>
                        <p class="mb-0 text-muted small">
                            <?php if ($abertura): ?>
                                Abertura: <?= htmlspecialchars($abertura) ?>
                            <?php endif; ?>
                            <?php if ($prazo !== 'Sem prazo'): ?>
                                <?= $abertura ? ' · ' : '' ?>Prazo: <?= htmlspecialchars($prazo) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($base) ?>vagas-internas" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($metaCards as $card): ?>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2 text-<?= htmlspecialchars($card['tone']) ?>">
                            <span class="rounded-2 bg-<?= htmlspecialchars($card['tone']) ?> bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                                  style="width:2rem;height:2rem;">
                                <i class="fas <?= htmlspecialchars($card['icon']) ?> fa-sm"></i>
                            </span>
                            <span class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;"><?= htmlspecialchars($card['label']) ?></span>
                        </div>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($card['value']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($prazo !== 'Sem prazo' || $salarioLabel !== null): ?>
        <div class="row g-3 mb-4">
            <div class="<?= $salarioLabel !== null ? 'col-md-6' : 'col-12' ?>">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="rounded-2 bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center flex-shrink-0"
                              style="width:2.5rem;height:2.5rem;">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                        <div>
                            <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Prazo de inscrição</div>
                            <div class="fw-semibold"><?= htmlspecialchars($prazo) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($salarioLabel !== null): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="rounded-2 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width:2.5rem;height:2.5rem;">
                                <i class="fas fa-money-bill-wave"></i>
                            </span>
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Faixa salarial</div>
                                <div class="fw-semibold"><?= htmlspecialchars($salarioLabel) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <?php if (!empty($v['descricao'])): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-align-left text-success me-2"></i>Descrição
                        </h6>
                        <div class="text-body" style="white-space:pre-wrap;"><?= htmlspecialchars((string) $v['descricao']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($v['requisitos'])): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-3">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-clipboard-check text-warning me-2"></i>Requisitos
                        </h6>
                        <div class="text-body" style="white-space:pre-wrap;"><?= htmlspecialchars((string) $v['requisitos']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($v['beneficios'])): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-3">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-gift text-info me-2"></i>Benefícios
                        </h6>
                        <div class="text-body" style="white-space:pre-wrap;"><?= htmlspecialchars((string) $v['beneficios']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card mb-4 border-0 shadow-sm" id="candidatar">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:2.75rem;height:2.75rem;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-semibold">Candidatar-se</h5>
                    <p class="mb-0 small text-muted">Envie sua candidatura com o usuário logado</p>
                </div>
            </div>

            <?php if ($ja): ?>
                <div class="alert alert-success d-flex align-items-start gap-3 mb-0 border-0 shadow-sm">
                    <span class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center flex-shrink-0"
                          style="width:2.25rem;height:2.25rem;">
                        <i class="fas fa-check"></i>
                    </span>
                    <div>
                        <strong class="d-block mb-1">Candidatura já enviada</strong>
                        <span class="small">Você já se candidatou a esta vaga. O RH acompanhará o processo.</span>
                    </div>
                </div>
            <?php elseif ($termo === null): ?>
                <div class="alert alert-warning mb-0 border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Candidatura temporariamente indisponível (termo LGPD não configurado).
                </div>
            <?php else: ?>
                <p class="small text-muted mb-3">
                    Usaremos nome e e-mail do seu usuário logado. Não há upload de currículo neste formulário.
                </p>
                <form method="post" action="<?= htmlspecialchars($base) ?>vagas-internas/<?= $id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <div class="mb-3">
                        <label for="mensagem" class="form-label">Mensagem (opcional)</label>
                        <textarea name="mensagem" id="mensagem" class="form-control" rows="3"
                                  maxlength="1000"
                                  placeholder="Conte brevemente por que tem interesse nesta oportunidade..."><?= htmlspecialchars((string) ($form['mensagem'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="lgpd_consent" name="lgpd_consent" required
                            <?= (($form['lgpd_consent'] ?? '') === '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lgpd_consent">
                            Li e aceito o termo LGPD
                            <?php if (!empty($termo['titulo'])): ?>
                                (<em><?= htmlspecialchars((string) $termo['titulo']) ?></em>
                            <?php endif; ?>
                            (versão <?= htmlspecialchars((string) ($termo['versao'] ?? '1.0')) ?>).
                        </label>
                    </div>
                    <?php if (!empty($termo['conteudo'])): ?>
                        <details class="mb-3">
                            <summary class="small text-muted">Ver texto do termo</summary>
                            <div class="border rounded-3 p-3 small bg-light mt-2" style="max-height: 200px; overflow: auto;">
                                <?= nl2br(htmlspecialchars((string) $termo['conteudo'])) ?>
                            </div>
                        </details>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i>Enviar candidatura
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
