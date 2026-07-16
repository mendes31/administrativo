<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_equipamento_tipos');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-layer-group me-2"></i>Tipos de equipamento</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Tipos de equipamento</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Catálogo de tipos</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateEquipamentoTipo', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-equipamento-tipo" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Novo tipo</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4"><label class="form-label small">Busca</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option><?php foreach (['Ativo','Inativo'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Nome</th><th>Código</th><th>Prefixo</th><th>Recarga</th><th>Checklist</th><th>Equipamentos</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($r['codigo'] ?? '') ?></code></td>
                            <td><code><?= htmlspecialchars($r['prefixo'] ?? '—') ?></code></td>
                            <td><?= !empty($r['controla_recarga']) ? 'Sim (' . (int)($r['validade_recarga_meses'] ?? 12) . 'm)' : 'Não' ?></td>
                            <td><?= (int)($r['total_checklist'] ?? 0) ?> itens</td>
                            <td><?= (int)($r['total_equipamentos'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewEquipamentoTipo', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-equipamento-tipo/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstUpdateEquipamentoTipo', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-equipamento-tipo/<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                <?php if (in_array('SstDeleteEquipamentoTipo', $perms, true)): ?>
                                <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-equipamento-tipo" method="POST" class="d-inline" onsubmit="return confirm('Excluir tipo?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfDelete ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>
