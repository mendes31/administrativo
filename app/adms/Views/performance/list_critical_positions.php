<?php
$riskLabels = ['high' => ['Alto', 'danger'], 'medium' => ['Médio', 'warning'], 'low' => ['Baixo', 'secondary']];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Sucessão — Cargos Críticos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Sucessão</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-sitemap me-2"></i>Cargos críticos</span>
            <div>
                <?php if (in_array('ListTalentNominations', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="btn btn-sm btn-outline-warning">Talent Pool</a>
                <?php } ?>
                <?php if (in_array('CreateCriticalPosition', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-critical-position" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Novo</a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="active" <?= ($this->data['filters']['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($this->data['filters']['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Risco</label>
                    <select name="risk_level" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($riskLabels as $code => $meta): ?>
                            <option value="<?= $code ?>" <?= ($this->data['filters']['risk_level'] ?? '') === $code ? 'selected' : '' ?>><?= $meta[0] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Buscar cargo</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>
            <?php if (empty($this->data['items'])): ?>
                <div class="alert alert-info">Nenhum cargo crítico encontrado.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Cargo</th><th>Risco</th><th>Status</th><th>Sucessores</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($this->data['items'] as $row):
                                $rk = $riskLabels[$row['risk_level'] ?? ''] ?? ['—', 'secondary'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['position_name'] ?? '') ?></td>
                                    <td><span class="badge bg-<?= $rk[1] ?>"><?= $rk[0] ?></span></td>
                                    <td><?= ($row['status'] ?? '') === 'active' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                                    <td><?= (int) ($row['successors_count'] ?? 0) ?></td>
                                    <td class="text-end text-nowrap">
                                        <?php if (in_array('ViewCriticalPosition', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-critical-position/<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <?php } ?>
                                        <?php if (in_array('UpdateCriticalPosition', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-critical-position/<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-warning">Editar</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= $this->data['pagination'] ?? '' ?>
            <?php endif; ?>
        </div>
    </div>
</div>
