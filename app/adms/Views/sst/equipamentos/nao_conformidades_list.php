<?php
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$filters = $this->data['filters'] ?? [];
$items = $this->data['items'] ?? [];
$statusOpts = ['Aberta', 'Em tratamento', 'Encerrada', 'Cancelada'];
?>
<div class="container-fluid px-3 px-md-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Não conformidades — Equipamentos</h2>
    </div>
    <form method="GET" action="<?= htmlspecialchars($urlAdm) ?>sst-list-equipamento-nao-conformidades" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Busca</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="NC, item ou código equipamento">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($statusOpts as $st): ?>
                    <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="abertas" value="1" id="abertas" <?= !empty($filters['status_open']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="abertas">Somente abertas / em tratamento</label>
                </div>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Filtrar</button></div>
        </div>
    </form>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>NC</th>
                        <th>Equipamento</th>
                        <th>Item</th>
                        <th>Competência</th>
                        <th>Status</th>
                        <th>Encerramento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma NC encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($r['codigo'] ?? '') ?></td>
                        <td>
                            <?= htmlspecialchars($r['equipamento_codigo'] ?? '') ?>
                            <?php if (($r['equipamento_status'] ?? '') === 'Bloqueado'): ?>
                            <span class="badge bg-danger">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($r['descricao'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['competencia'] ?? '') ?></td>
                        <td>
                            <?php
                            $st = (string) ($r['status'] ?? '');
                            $badge = match ($st) {
                                'Aberta' => 'bg-danger',
                                'Em tratamento' => 'bg-warning text-dark',
                                'Encerrada' => 'bg-success',
                                default => 'bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($st) ?></span>
                        </td>
                        <td class="small">
                            <?php if (!empty($r['acao_encerramento_codigo'])): ?>
                                via <?= htmlspecialchars($r['acao_encerramento_codigo']) ?>
                                <?php if (!empty($r['encerrada_em'])): ?>
                                <br><?= date('d/m/Y H:i', strtotime((string) $r['encerrada_em'])) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($urlAdm) ?>sst-view-equipamento-nao-conformidade/<?= (int) $r['id'] ?>">Abrir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($this->data['pagination'])): ?>
        <div class="card-footer"><?php include './app/adms/Views/partials/pagination.php'; ?></div>
        <?php endif; ?>
    </div>
</div>
