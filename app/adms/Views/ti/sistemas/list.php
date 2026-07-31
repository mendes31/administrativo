<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$sistemas = $this->data['sistemas'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Sistemas (TI)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'dashboard', ENT_QUOTES, 'UTF-8') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">TI / Acessos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Catálogo de sistemas e equipamentos</span>
            <span class="ms-auto">
                <?php if (in_array('TiSistemasCreate', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas-create', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
                        <i class="fa-regular fa-square-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="nome" class="form-label mb-1">Busca</label>
                    <input type="text" name="nome" id="nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($this->data['filter_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome, tag, código, filial ou localização">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ativo" <?= ($this->data['filter_status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= ($this->data['filter_status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="per_page" class="form-label mb-1">Por página</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (int) ($this->data['per_page'] ?? 20) === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpar</a>
                </div>
            </form>

            <?php if ($sistemas === []): ?>
                <p class="text-muted mb-0">Nenhum sistema cadastrado. Cadastre CLPs, IHMs, portais e demais sistemas com conta própria.</p>
            <?php else: ?>
                <div class="table-responsive">
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
                                        <?php if (in_array('TiSistemasView', $this->data['buttonPermission'] ?? [], true)): ?>
                                            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url . 'ti-sistemas-view/' . (int) $s['id'], ENT_QUOTES, 'UTF-8') ?>" title="Visualizar"><i class="fa-regular fa-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('TiSistemasUpdate', $this->data['buttonPermission'] ?? [], true)): ?>
                                            <a class="btn btn-warning btn-sm" href="<?= htmlspecialchars($url . 'ti-sistemas-update/' . (int) $s['id'], ENT_QUOTES, 'UTF-8') ?>" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= $this->data['pagination']['html'] ?? '' ?>
            <?php endif; ?>
        </div>
    </div>
</div>
