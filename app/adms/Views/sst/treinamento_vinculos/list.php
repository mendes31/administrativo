<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoStatusHelper;

$perms = $this->data['buttonPermission'] ?? [];
$csrfSync = CSRFHelper::generateCSRFToken('sst_sync_treinamento_vinculos');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-user-graduate me-2"></i>Status Treinamentos SST</h2>
        <span class="ms-auto">
            <?php if (in_array('SstSyncTreinamentoVinculos', $perms)): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-sync-treinamento-vinculos" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrfSync ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm" onclick="return confirm('Sincronizar vínculos para todos os colaboradores ativos?');"><i class="fas fa-sync me-1"></i>Sincronizar matriz</button>
            </form>
            <?php endif; ?>
            <?php if (in_array('SstApplyTreinamento', $perms)): ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-apply-treinamento" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Aplicar treinamento</a>
            <?php endif; ?>
        </span>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Colaborador</label>
                    <select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Treinamento</label>
                    <select name="adms_sst_treinamento_id" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach ($this->data['treinamentos'] ?? [] as $t): ?><option value="<?= (int)$t['id'] ?>" <?= ((string)($this->data['filters']['adms_sst_treinamento_id'] ?? '') === (string)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['nome'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach ($this->data['statusOptions'] ?? [] as $k => $lbl): ?><option value="<?= htmlspecialchars($k) ?>" <?= ($this->data['filters']['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr><th>Colaborador</th><th>Treinamento</th><th>Status</th><th>Realização</th><th>Validade</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $row): $id = (int)($row['id'] ?? 0); $st = (string)($row['status'] ?? ''); ?>
                            <tr>
                                <td><?= htmlspecialchars($row['colaborador_nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['treinamento_nome'] ?? '') ?></td>
                                <td><span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span></td>
                                <td><?= !empty($row['data_realizacao']) ? date('d/m/Y', strtotime($row['data_realizacao'])) : '-' ?></td>
                                <td><?= !empty($row['data_validade']) ? date('d/m/Y', strtotime($row['data_validade'])) : '-' ?></td>
                                <td class="text-nowrap">
                                    <?php if (in_array('SstViewTreinamentoVinculo', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-treinamento-vinculo/<?= $id ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                    <?php if (in_array('SstApplyTreinamento', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-apply-treinamento/<?= $id ?>" class="btn btn-success btn-sm" title="Aplicar"><i class="fas fa-check"></i></a><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['items'] as $row): $id = (int)($row['id'] ?? 0); $st = (string)($row['status'] ?? ''); ?>
                        <div class="card mb-2 shadow-sm" onclick="window.location.href='<?= $_ENV['URL_ADM']; ?>sst-view-treinamento-vinculo/<?= $id ?>';" style="cursor:pointer;">
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= htmlspecialchars($row['colaborador_nome'] ?? '') ?></div>
                                <div class="small"><?= htmlspecialchars($row['treinamento_nome'] ?? '') ?></div>
                                <span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum vínculo encontrado. Use <strong>Sincronizar matriz</strong> para gerar vínculos obrigatórios.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
