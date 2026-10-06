<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$registros = $this->data['registros'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$canView = in_array('TiRustdeskView', $perms, true);
$canUpdate = in_array('TiRustdeskUpdate', $perms, true);
$canCreate = in_array('TiRustdeskCreate', $perms, true);
$canReveal = in_array('TiRustdeskReveal', $perms, true);
$canImport = in_array('ImportCenterTi', $perms, true);
$encryptionOk = !empty($this->data['encryption_ok']);
?>
<style>
.ti-rd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
.ti-rd-card { border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); background: #fff; }
.ti-rd-card-body { color: #fff; min-height: 118px; padding: 1rem .9rem .7rem; position: relative; }
.ti-rd-card-body .ti-rd-win { font-size: 2.1rem; opacity: .92; display: block; text-align: center; margin-bottom: .45rem; }
.ti-rd-alias { font-size: .78rem; text-align: center; word-break: break-all; line-height: 1.25; opacity: .95; }
.ti-rd-card-foot { display: flex; align-items: center; gap: .35rem; padding: .4rem .55rem; background: #f4f5f7; font-size: .8rem; }
.ti-rd-dot { width: .55rem; height: .55rem; border-radius: 50%; flex-shrink: 0; }
.ti-rd-dot.on { background: #22c55e; }
.ti-rd-dot.off { background: #9ca3af; }
.ti-rd-id { font-variant-numeric: tabular-nums; flex-grow: 1; color: #374151; }
.ti-rd-card-foot .btn { padding: .1rem .35rem; }
</style>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">RustDesk (TI)</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'dashboard', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">TI / Acessos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <span>Inventário de IDs e senhas RustDesk</span>
            <div class="d-flex flex-wrap gap-2">
            <?php if ($canImport): ?>
                <a href="<?= htmlspecialchars($url . 'import-center-create?profile=ti_rustdesk', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-file-import"></i> Importar
                </a>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <a href="<?= htmlspecialchars($url . 'ti-rustdesk-create', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
                    <i class="fa-regular fa-square-plus"></i> Cadastrar
                </a>
            <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (!$encryptionOk): ?>
                <div class="alert alert-warning">
                    Não foi possível preparar a criptografia das senhas (pasta <code>storage/private/secrets</code> sem permissão de escrita).
                </div>
            <?php endif; ?>
            <p class="small text-muted">
                A senha do RustDesk fica só para consulta e cópia (criptografada). Para vê-la ou copiá-la, confirme a senha da sua conta neste sistema.
                A conexão remota de dentro deste portal fica para uma próxima versão.
            </p>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label mb-1">Busca</label>
                    <input type="text" name="q" id="q" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($this->data['filter_q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Apelido, ID ou colaborador">
                </div>
                <div class="col-6 col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <?php $fs = (string) ($this->data['filter_status'] ?? 'ativo'); ?>
                        <option value="" <?= $fs === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="ativo" <?= $fs === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $fs === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="per_page" class="form-label mb-1">Por página</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm">
                        <?php foreach ([12, 24, 48, 96] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (int) ($this->data['per_page'] ?? 24) === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 flex-md-grow-0"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-rustdesk', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm flex-grow-1 flex-md-grow-0">Limpar</a>
                </div>
            </form>

            <?php if ($registros === []): ?>
                <p class="text-muted mb-0">Nenhum RustDesk cadastrado.</p>
            <?php else: ?>
                <div class="ti-rd-grid">
                    <?php foreach ($registros as $r):
                        $rid = (int) ($r['id'] ?? 0);
                        $idFmt = (string) ($r['rustdesk_id_fmt'] ?? $r['rustdesk_id'] ?? '');
                        $ativo = ($r['status'] ?? '') === 'ativo';
                        $hasSenha = !empty($r['has_senha']);
                        ?>
                        <article class="ti-rd-card">
                            <div class="ti-rd-card-body" style="background: <?= htmlspecialchars((string) ($r['card_color'] ?? '#8fa8d4'), ENT_QUOTES, 'UTF-8') ?>;">
                                <i class="fab fa-windows ti-rd-win" aria-hidden="true"></i>
                                <div class="ti-rd-alias"><?= htmlspecialchars((string) ($r['alias'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="ti-rd-card-foot">
                                <span class="ti-rd-dot <?= $ativo ? 'on' : 'off' ?>" title="<?= $ativo ? 'Ativo' : 'Inativo' ?>"></span>
                                <span class="ti-rd-id"><?= htmlspecialchars($idFmt, ENT_QUOTES, 'UTF-8') ?></span>
                                <button type="button" class="btn btn-link btn-sm text-secondary p-0" title="Copiar ID"
                                        onclick="tiRustdeskCopyId(<?= htmlspecialchars(json_encode($idFmt), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                                <?php if ($canReveal && $hasSenha): ?>
                                    <button type="button" class="btn btn-link btn-sm text-secondary p-0" title="Visualizar senha (exige a sua senha)"
                                            onclick="tiRustdeskAskSecret(<?= $rid ?>, 'view')">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm text-secondary p-0" title="Copiar senha (exige a sua senha)"
                                            onclick="tiRustdeskAskSecret(<?= $rid ?>, 'copy')">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                <?php elseif (!$hasSenha): ?>
                                    <span class="text-muted" title="Sem senha cadastrada"><i class="fa-regular fa-eye-slash"></i></span>
                                <?php endif; ?>
                                <?php if ($canView): ?>
                                    <a class="btn btn-link btn-sm text-secondary p-0" href="<?= htmlspecialchars($url . 'ti-rustdesk-view/' . $rid, ENT_QUOTES, 'UTF-8') ?>" title="Ficha">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </a>
                                <?php elseif ($canUpdate): ?>
                                    <a class="btn btn-link btn-sm text-secondary p-0" href="<?= htmlspecialchars($url . 'ti-rustdesk-update/' . $rid, ENT_QUOTES, 'UTF-8') ?>" title="Editar">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/_secret_modal.php'; ?>
