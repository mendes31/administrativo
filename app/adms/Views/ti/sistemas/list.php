<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$sistemas = $this->data['sistemas'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$canView = in_array('TiSistemasView', $perms, true);
$canUpdate = in_array('TiSistemasUpdate', $perms, true);
$canCreate = in_array('TiSistemasCreate', $perms, true);
?>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Sistemas (TI)</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'dashboard', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">TI / Acessos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <span>Catálogo de sistemas e equipamentos</span>
            <?php if ($canCreate): ?>
                <a href="<?= htmlspecialchars($url . 'ti-sistemas-create', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
                    <i class="fa-regular fa-square-plus"></i> Cadastrar
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="nome" class="form-label mb-1">Busca</label>
                    <input type="text" name="nome" id="nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($this->data['filter_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome, tag, código, filial ou localização">
                </div>
                <div class="col-6 col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ativo" <?= ($this->data['filter_status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= ($this->data['filter_status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="per_page" class="form-label mb-1">Por página</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (int) ($this->data['per_page'] ?? 20) === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 flex-md-grow-0"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm flex-grow-1 flex-md-grow-0">Limpar</a>
                </div>
            </form>

            <?php if ($sistemas === []): ?>
                <p class="text-muted mb-0">Nenhum sistema cadastrado. Cadastre CLPs, IHMs, portais e demais sistemas com conta própria.</p>
            <?php else: ?>
                <div class="table-responsive list-desktop">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Nome / equipamento</th>
                                <th>Tipo</th>
                                <th>Filial</th>
                                <th>Localização</th>
                                <th>Status</th>
                                <th class="text-center">Acessos ativos</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sistemas as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) ($s['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if (!empty($s['equipamento_tag'])): ?>
                                            <div class="small"><span class="badge text-bg-light border"><?= htmlspecialchars((string) $s['equipamento_tag'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                        <?php endif; ?>
                                        <?php if (!empty($s['codigo'])): ?>
                                            <div class="small text-muted">Cód.: <?= htmlspecialchars((string) $s['codigo'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <?php
                                        $fab = trim((string) ($s['fabricante'] ?? ''));
                                        $mod = trim((string) ($s['modelo'] ?? ''));
                                        if ($fab !== '' || $mod !== ''):
                                        ?>
                                            <div class="small text-muted"><?= htmlspecialchars(trim($fab . ($fab && $mod ? ' / ' : '') . $mod), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($s['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($s['filial_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($s['localizacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge <?= ($s['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars((string) ($s['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= (int) ($s['acessos_ativos'] ?? 0) ?></td>
                                    <td class="text-center">
                                        <?php if ($canView): ?>
                                            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url . 'ti-sistemas-view/' . (int) $s['id'], ENT_QUOTES, 'UTF-8') ?>" title="Visualizar"><i class="fa-regular fa-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if ($canUpdate): ?>
                                            <a class="btn btn-warning btn-sm" href="<?= htmlspecialchars($url . 'ti-sistemas-update/' . (int) $s['id'], ENT_QUOTES, 'UTF-8') ?>" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="list-mobile">
                    <?php foreach ($sistemas as $s): ?>
                        <?php
                        $sid = (int) ($s['id'] ?? 0);
                        $fab = trim((string) ($s['fabricante'] ?? ''));
                        $mod = trim((string) ($s['modelo'] ?? ''));
                        $fabMod = trim($fab . ($fab !== '' && $mod !== '' ? ' / ' : '') . $mod);
                        ?>
                        <article class="user-view-list-card mb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="user-view-list-card-title mb-0">
                                    <?= htmlspecialchars((string) ($s['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($s['equipamento_tag'])): ?>
                                        <div class="fw-normal mt-1"><span class="badge text-bg-light border"><?= htmlspecialchars((string) $s['equipamento_tag'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge flex-shrink-0 <?= ($s['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars((string) ($s['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <dl class="user-view-list-card-dl mb-0 mt-2">
                                <?php if (!empty($s['codigo'])): ?>
                                    <div><dt>Código</dt><dd><?= htmlspecialchars((string) $s['codigo'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                                <div><dt>Tipo</dt><dd><?= htmlspecialchars((string) ($s['tipo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Filial</dt><dd class="text-break"><?= htmlspecialchars((string) ($s['filial_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Local</dt><dd class="text-break"><?= htmlspecialchars((string) ($s['localizacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php if ($fabMod !== ''): ?>
                                    <div><dt>Equip.</dt><dd class="text-break"><?= htmlspecialchars($fabMod, ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                                <div><dt>Acessos</dt><dd><?= (int) ($s['acessos_ativos'] ?? 0) ?> ativo(s)</dd></div>
                            </dl>
                            <?php if ($canView || $canUpdate): ?>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php if ($canView): ?>
                                        <a class="btn btn-primary btn-sm flex-fill" href="<?= htmlspecialchars($url . 'ti-sistemas-view/' . $sid, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-regular fa-eye me-1"></i>Visualizar
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canUpdate): ?>
                                        <a class="btn btn-warning btn-sm flex-fill" href="<?= htmlspecialchars($url . 'ti-sistemas-update/' . $sid, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-regular fa-pen-to-square me-1"></i>Editar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
