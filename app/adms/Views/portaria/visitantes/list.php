<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$visitantes = $this->data['visitantes'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3"><h1 class="h3 mb-0">Visitantes</h1><?php if (in_array('PortariaVisitantesCreate', $perms, true)): ?><a class="btn btn-success" href="<?= $url ?>portaria-visitantes-create">Cadastrar visitante</a><?php endif; ?></div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-6"><input class="form-control" name="busca" value="<?= htmlspecialchars((string) ($this->data['filters']['busca'] ?? '')) ?>" placeholder="Nome, documento, empresa ou telefone"></div>
                <div class="col-md-2"><select class="form-select" name="ativo"><option value="">Todos</option><option value="1" <?= ($this->data['filters']['ativo'] ?? '') === '1' ? 'selected' : '' ?>>Ativos</option><option value="0" <?= ($this->data['filters']['ativo'] ?? '') === '0' ? 'selected' : '' ?>>Inativos</option></select></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
            </form>
            <div class="table-responsive"><table class="table table-striped table-hover align-middle"><thead><tr><th>Nome</th><th>Documento</th><th>Empresa</th><th>Termo</th><th>Status</th><th>Ações</th></tr></thead><tbody>
            <?php foreach ($visitantes as $v): $status = (string) ($v['termo_status'] ?? 'ausente'); ?>
                <tr><td><?= htmlspecialchars((string) $v['nome']) ?></td><td><?= htmlspecialchars((string) ($v['documento'] ?? '—')) ?></td><td><?= htmlspecialchars((string) ($v['empresa'] ?? '—')) ?></td><td><span class="badge <?= $status === 'vigente' ? 'bg-success' : ($status === 'vencido' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= htmlspecialchars($status) ?></span></td><td><?= !empty($v['ativo']) ? 'Ativo' : 'Inativo' ?></td><td><?php if (in_array('PortariaVisitantesView', $perms, true)): ?><a class="btn btn-primary btn-sm" href="<?= $url ?>portaria-visitantes-view/<?= (int) $v['id'] ?>">Ver</a><?php endif; ?> <?php if (in_array('PortariaVisitantesUpdate', $perms, true)): ?><a class="btn btn-warning btn-sm" href="<?= $url ?>portaria-visitantes-update/<?= (int) $v['id'] ?>">Editar</a><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if ($visitantes === []): ?><tr><td colspan="6" class="text-center text-muted">Nenhum visitante encontrado.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
    </div>
</div>
