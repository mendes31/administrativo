<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Extrato de pontos</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="number" name="user_id" class="form-control" placeholder="Filtrar por ID utilizador"
                   value="<?= $this->data['filter_user_id'] !== null ? (int)$this->data['filter_user_id'] : '' ?>" min="1">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Filtrar</button>
            <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-point-ledger" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>
    <div class="card border-light shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Utilizador</th>
                        <th>Origem</th>
                        <th>Evento</th>
                        <th>Ref.</th>
                        <th class="text-end">Pontos</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['entries'] ?? [] as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($e['created_at'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($e['user_name'] ?? '')) ?> (<?= (int)($e['user_id'] ?? 0) ?>)</td>
                            <td><code><?= htmlspecialchars((string)($e['source_type'] ?? '')) ?></code></td>
                            <td><code><?= htmlspecialchars((string)($e['event_key'] ?? '')) ?></code></td>
                            <td><small><?= htmlspecialchars((string)($e['ref_type'] ?? '')) ?> #<?= (int)($e['ref_id'] ?? 0) ?></small></td>
                            <td class="text-end fw-semibold"><?= (int)($e['points'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
