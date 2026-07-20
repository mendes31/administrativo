<?php
$statusLabels = ['draft' => 'Rascunho', 'approved' => 'Aprovada', 'applied' => 'Aplicada', 'cancelled' => 'Cancelada'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Promoções</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto"><li class="breadcrumb-item">Carreira</li></ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <span><i class="fas fa-level-up-alt me-2"></i>Promoções</span>
            <div>
                <?php if (in_array('ListCareerTracks', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-career-tracks" class="btn btn-sm btn-outline-secondary">Trilhas</a>
                <?php } ?>
                <?php if (in_array('CreateCareerPromotion', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-career-promotion" class="btn btn-sm btn-success">Nova</a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Colaborador</option>
                        <?php foreach ($this->data['employees'] ?? [] as $e): ?>
                            <option value="<?= (int) $e['id'] ?>" <?= ((int) ($this->data['filters']['user_id'] ?? 0) === (int) $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status</option>
                        <?php foreach ($statusLabels as $c => $l): ?>
                            <option value="<?= $c ?>" <?= ($this->data['filters']['status'] ?? '') === $c ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-career-promotions?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>
            <?php if (empty($this->data['promotions'])): ?>
                <div class="alert alert-info">Nenhuma promoção.</div>
            <?php else: ?>
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Colaborador</th><th>Para cargo</th><th>Data</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($this->data['promotions'] as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['user_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['to_position_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['effective_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($statusLabels[$p['status'] ?? ''] ?? ($p['status'] ?? '')) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>view-career-promotion/<?= (int) $p['id'] ?>">Ver</a>
                                    <?php if (in_array('UpdateCareerPromotion', $this->data['buttonPermission'] ?? [], true) && ($p['status'] ?? '') !== 'applied') { ?>
                                        <a class="btn btn-sm btn-outline-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-career-promotion/<?= (int) $p['id'] ?>">Editar</a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?= $this->data['pagination'] ?? '' ?>
            <?php endif; ?>
        </div>
    </div>
</div>
