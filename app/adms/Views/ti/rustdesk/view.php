<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$r = $this->data['registro'] ?? [];
$rid = (int) ($r['id'] ?? 0);
$perms = $this->data['buttonPermission'] ?? [];
$canUpdate = in_array('TiRustdeskUpdate', $perms, true);
$canReveal = in_array('TiRustdeskReveal', $perms, true);
$hasSenha = !empty($r['has_senha']);
$idFmt = (string) ($r['rustdesk_id_fmt'] ?? $r['rustdesk_id'] ?? '');
$ativo = ($r['status'] ?? '') === 'ativo';
$log = $this->data['log_resumo'] ?? null;
?>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">RustDesk</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-rustdesk', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">RustDesk</a></li>
            <li class="breadcrumb-item active">#<?= $rid ?></li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <span><?= htmlspecialchars((string) ($r['alias'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canUpdate): ?>
                    <a href="<?= htmlspecialchars($url . 'ti-rustdesk-update/' . $rid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm">
                        <i class="fa-regular fa-pen-to-square"></i> Editar
                    </a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($url . 'ti-rustdesk', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <article class="ti-rd-preview rounded-3 overflow-hidden" style="background: <?= htmlspecialchars((string) ($r['card_color'] ?? '#8fa8d4'), ENT_QUOTES, 'UTF-8') ?>; color:#fff; min-height:140px; padding:1.2rem;">
                        <div class="text-center"><i class="fab fa-windows fa-2x"></i></div>
                        <div class="text-center mt-2 small"><?= htmlspecialchars((string) ($r['alias'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </article>
                </div>
                <div class="col-md-8">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID RustDesk</dt>
                        <dd class="col-sm-8">
                            <code><?= htmlspecialchars($idFmt, ENT_QUOTES, 'UTF-8') ?></code>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" title="Copiar ID"
                                    onclick="tiRustdeskCopyId(<?= htmlspecialchars(json_encode($idFmt), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </dd>
                        <dt class="col-sm-4">Senha</dt>
                        <dd class="col-sm-8">
                            <?php if ($hasSenha): ?>
                                <span class="text-muted">••••••••</span>
                                <?php if ($canReveal): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-1" title="Visualizar senha"
                                            onclick="tiRustdeskAskSecret(<?= $rid ?>, 'view')">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" title="Copiar senha"
                                            onclick="tiRustdeskAskSecret(<?= $rid ?>, 'copy')">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Não cadastrada</span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Colaborador</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($r['colaborador_nome'])): ?>
                                <?= htmlspecialchars((string) $r['colaborador_nome'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($r['colaborador_email'])): ?>
                                    <span class="text-muted small">(<?= htmlspecialchars((string) $r['colaborador_email'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Departamento</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($r['departamento_nome'])): ?>
                                <?= htmlspecialchars((string) $r['departamento_nome'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?= $ativo ? 'bg-success' : 'bg-secondary' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
                        </dd>
                        <dt class="col-sm-4">Observações</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars((string) ($r['observacoes'] ?? '—'), ENT_QUOTES, 'UTF-8')) ?></dd>
                    </dl>
                    <?php if (is_array($log) && !empty($log['has_logs'])): ?>
                        <a class="btn btn-outline-secondary btn-sm mt-2" href="<?= htmlspecialchars((string) $log['list_url'], ENT_QUOTES, 'UTF-8') ?>">
                            Log de alterações (<?= (int) ($log['count'] ?? 0) ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/_secret_modal.php'; ?>
