<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$perms = $this->data['buttonPermission'] ?? [];
$autorizacoes = $this->data['autorizacoes'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0">Autorizações</h1>
        <?php if (in_array('PortariaAutorizacoesCreate', $perms, true)): ?>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-success" href="<?= $url ?>portaria-autorizacoes-create">Agendar visita</a>
                <a class="btn btn-success" href="<?= $url ?>portaria-autorizacoes-create?modo=rapido">
                    <i class="fas fa-bolt me-1"></i>Liberação rápida
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">
                <strong>Agendar</strong> = visita prevista pelo anfitrião.
                <strong>Liberação rápida</strong> = chegada sem aviso (cadastra visitante na hora, se precisar).
                Busque por <strong>documento</strong> ou <strong>nome</strong> do visitante.
            </p>
            <form method="get" class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                    <input class="form-control" name="busca" value="<?= htmlspecialchars((string) ($this->data['filters']['busca'] ?? '')) ?>" placeholder="Documento ou nome do visitante">
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Todos os status</option>
                        <?php foreach (['aguardando', 'autorizada', 'recusada', 'cancelada'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>

            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                            <th>Visitante</th>
                            <th>Anfitrião</th>
                            <th>Período</th>
                            <th>Origem</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($autorizacoes === []): ?>
                            <tr><td colspan="7" class="text-center text-muted">Nenhuma autorização encontrada.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($autorizacoes as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $a['protocolo']) ?></td>
                                <td><?= htmlspecialchars((string) $a['visitante_nome']) ?></td>
                                <td><?= htmlspecialchars((string) ($a['anfitriao_nome'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) $a['data_inicio']) ?> a <?= htmlspecialchars((string) $a['data_fim']) ?></td>
                                <td><?= htmlspecialchars((string) $a['origem']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span></td>
                                <td class="text-center">
                                    <?php if (in_array('PortariaAutorizacoesView', $perms, true)): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= $url ?>portaria-autorizacoes-view/<?= (int) $a['id'] ?>">Ver</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none list-mobile">
                <?php if ($autorizacoes === []): ?>
                    <p class="text-muted text-center mb-0">Nenhuma autorização encontrada.</p>
                <?php endif; ?>
                <?php foreach ($autorizacoes as $a): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars((string) $a['protocolo']) ?></h5>
                                <span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span>
                            </div>
                            <div class="small mb-1"><b>Visitante:</b> <?= htmlspecialchars((string) $a['visitante_nome']) ?></div>
                            <div class="small mb-1"><b>Anfitrião:</b> <?= htmlspecialchars((string) ($a['anfitriao_nome'] ?? '—')) ?></div>
                            <div class="small mb-1"><b>Período:</b> <?= htmlspecialchars((string) $a['data_inicio']) ?> a <?= htmlspecialchars((string) $a['data_fim']) ?></div>
                            <div class="small mb-2"><b>Origem:</b> <?= htmlspecialchars((string) $a['origem']) ?></div>
                            <?php if (in_array('PortariaAutorizacoesView', $perms, true)): ?>
                                <a class="btn btn-primary btn-sm w-100" href="<?= $url ?>portaria-autorizacoes-view/<?= (int) $a['id'] ?>">Ver</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
