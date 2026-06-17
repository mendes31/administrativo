<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrf = CSRFHelper::generateCSRFToken('sst_esocial_actions');
?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-cloud-upload-alt me-2"></i>Eventos eSocial SST</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <p class="text-muted small">Fila de eventos S-2210 (CAT), S-2220 (ASO) e S-2240 (EPI). Geração de payload JSON para transmissão externa — sem envio automático ao governo.</p>
    <div class="mb-3">
        <?php if (in_array('SstSyncEsocialPendentes', $perms, true)): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-sync-esocial-pendentes" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync"></i> Gerar eventos pendentes em lote</button>
            </form>
        <?php endif; ?>
        <a href="<?= $_ENV['URL_ADM']; ?>sst-report-conformidade" class="btn btn-outline-secondary btn-sm">Painel de conformidade</a>
    </div>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-2"><label class="form-label small">Evento</label><select name="tipo_evento" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach (['S-2210', 'S-2220', 'S-2240'] as $t): ?><option value="<?= $t ?>" <?= ($this->data['filters']['tipo_evento'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach (['Pendente', 'Gerado', 'Enviado', 'Erro', 'Cancelado'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3"><label class="form-label small">Colaborador</label><select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped">
            <thead><tr><th>ID</th><th>Evento</th><th>Colaborador</th><th>Origem</th><th>Status</th><th>Gerado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($this->data['items'] ?? [] as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= htmlspecialchars($r['tipo_evento'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars($r['origem_tabela'] ?? '') ?> #<?= (int)($r['origem_id'] ?? 0) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['status'] ?? '') ?></span></td>
                    <td><?= !empty($r['data_geracao']) ? date('d/m/Y H:i', strtotime($r['data_geracao'])) : '-' ?></td>
                    <td><?php if (in_array('SstViewEsocialEvento', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-esocial-evento/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
    <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
</div>
