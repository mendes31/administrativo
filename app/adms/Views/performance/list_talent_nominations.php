<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Talent Pool (HiPo)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Talent Pool</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-star me-2"></i>Nomeações de alto potencial</span>
            <div>
                <?php if (in_array('NineBoxMatrix', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>nine-box-matrix" class="btn btn-sm btn-outline-primary">Matriz 9BOX</a>
                <?php } ?>
                <?php if (in_array('CreateTalentNomination', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-talent-nomination" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Nomear
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small">Colaborador</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>" <?= ((int) ($this->data['filters']['user_id'] ?? 0) === (int) $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Ciclo</label>
                    <select name="performance_cycle_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['cycles'] ?? [] as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>" <?= ((int) ($this->data['filters']['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="active" <?= ($this->data['filters']['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="removed" <?= ($this->data['filters']['status'] ?? '') === 'removed' ? 'selected' : '' ?>>Removido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Box</label>
                    <select name="nine_box" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php for ($b = 1; $b <= 9; $b++): ?>
                            <option value="<?= $b ?>" <?= ((int) ($this->data['filters']['nine_box'] ?? 0) === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>

            <?php if (empty($this->data['nominations'])): ?>
                <div class="alert alert-info">Nenhuma nomeação encontrada.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Colaborador</th>
                                <th>Ciclo</th>
                                <th>Box</th>
                                <th>Status</th>
                                <th>Nomeado por</th>
                                <th>Em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['nominations'] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['user_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['cycle_name'] ?? '') ?></td>
                                    <td><?= $row['nine_box'] !== null ? (int) $row['nine_box'] : '—' ?></td>
                                    <td>
                                        <?php if (($row['status'] ?? '') === 'active'): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Removido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['nominated_by_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars(FormatHelper::formatDateTime($row['created_at'] ?? '')) ?></td>
                                    <td class="text-end text-nowrap">
                                        <?php if (in_array('ViewTalentNomination', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-talent-nomination/<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <?php } ?>
                                        <?php if (in_array('UpdateTalentNomination', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-talent-nomination/<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-warning">Editar</a>
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
