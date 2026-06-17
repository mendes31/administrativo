<?php ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-procedures me-2"></i>Relatório de Afastamentos</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Colaborador</label>
            <select name="adms_user_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($this->data['users'] ?? [] as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="Ativo" <?= ($this->data['filters']['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="Encerrado" <?= ($this->data['filters']['status'] ?? '') === 'Encerrado' ? 'selected' : '' ?>>Encerrado</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['Doença', 'Acidente de trabalho', 'Licença', 'Maternidade', 'Outro'] as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($this->data['filters']['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Início de</label>
            <input type="date" name="data_inicio_de" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['data_inicio_de'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Início até</label>
            <input type="date" name="data_inicio_ate" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['data_inicio_ate'] ?? '') ?>">
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>CID</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Dias</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data['items'] ?? [] as $r): ?>
                    <tr class="<?= ($r['status'] ?? '') === 'Ativo' ? 'table-warning' : '' ?>">
                        <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars(trim(($r['cid_codigo'] ?? '') . ' ' . ($r['cid_descricao'] ?? ''))) ?: '-' ?></td>
                        <td><?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?></td>
                        <td><?= !empty($r['data_fim']) ? date('d/m/Y', strtotime($r['data_fim'])) : '-' ?></td>
                        <td><?= (int)($r['dias_afastamento'] ?? 0) ?: '-' ?></td>
                        <td><span class="badge bg-<?= ($r['status'] ?? '') === 'Ativo' ? 'warning text-dark' : 'secondary' ?>"><?= htmlspecialchars($r['status'] ?? '') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>
