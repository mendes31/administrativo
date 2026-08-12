<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$visitantes = $this->data['visitantes'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0">Visitantes</h1>
        <?php if (in_array('PortariaVisitantesCreate', $perms, true)): ?>
            <a class="btn btn-success" href="<?= $url ?>portaria-visitantes-create">Cadastrar visitante</a>
        <?php endif; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                    <input class="form-control" name="busca" value="<?= htmlspecialchars((string) ($this->data['filters']['busca'] ?? '')) ?>" placeholder="Nome, documento, empresa ou telefone">
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select" name="ativo">
                        <option value="">Todos</option>
                        <option value="1" <?= ($this->data['filters']['ativo'] ?? '') === '1' ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= ($this->data['filters']['ativo'] ?? '') === '0' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>

            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Documento</th>
                            <th>Empresa</th>
                            <th>Termo</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($visitantes === []): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhum visitante encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($visitantes as $v): ?>
                            <?php $status = (string) ($v['termo_status'] ?? 'ausente'); ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $v['nome']) ?></td>
                                <td><?= htmlspecialchars((string) ($v['documento'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($v['empresa'] ?? '—')) ?></td>
                                <td>
                                    <span class="badge <?= $status === 'vigente' ? 'bg-success' : ($status === 'vencido' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>
                                <td><?= !empty($v['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                                <td class="text-center text-nowrap">
                                    <?php if (in_array('PortariaVisitantesView', $perms, true)): ?>
                                        <a class="btn btn-primary btn-sm me-1 mb-1" href="<?= $url ?>portaria-visitantes-view/<?= (int) $v['id'] ?>">Ver</a>
                                    <?php endif; ?>
                                    <?php if (in_array('PortariaVisitantesUpdate', $perms, true)): ?>
                                        <a class="btn btn-warning btn-sm mb-1" href="<?= $url ?>portaria-visitantes-update/<?= (int) $v['id'] ?>">Editar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none list-mobile">
                <?php if ($visitantes === []): ?>
                    <p class="text-muted text-center mb-0">Nenhum visitante encontrado.</p>
                <?php endif; ?>
                <?php foreach ($visitantes as $v): ?>
                    <?php $status = (string) ($v['termo_status'] ?? 'ausente'); ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars((string) $v['nome']) ?></h5>
                                <span class="badge <?= $status === 'vigente' ? 'bg-success' : ($status === 'vencido' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </div>
                            <div class="small mb-1"><b>Documento:</b> <?= htmlspecialchars((string) ($v['documento'] ?? '—')) ?></div>
                            <div class="small mb-1"><b>Empresa:</b> <?= htmlspecialchars((string) ($v['empresa'] ?? '—')) ?></div>
                            <div class="small mb-2"><b>Status:</b> <?= !empty($v['ativo']) ? 'Ativo' : 'Inativo' ?></div>
                            <div class="d-grid gap-2">
                                <?php if (in_array('PortariaVisitantesView', $perms, true)): ?>
                                    <a class="btn btn-primary btn-sm" href="<?= $url ?>portaria-visitantes-view/<?= (int) $v['id'] ?>">Ver</a>
                                <?php endif; ?>
                                <?php if (in_array('PortariaVisitantesUpdate', $perms, true)): ?>
                                    <a class="btn btn-warning btn-sm" href="<?= $url ?>portaria-visitantes-update/<?= (int) $v['id'] ?>">Editar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
