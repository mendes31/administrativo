<?php ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-stethoscope me-2"></i>Relatório de Exames</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Colaborador</label><select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label">Situação</label><select name="status_vencimento" class="form-select form-select-sm"><option value="">Todos</option><option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option></select></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Colaborador</th><th>Exame</th><th>Tipo</th><th>Realização</th><th>Validade</th><th>Resultado</th></tr></thead><tbody>
        <?php foreach ($this->data['items'] ?? [] as $r): ?><tr>
            <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['exame_nome'] ?? '') ?></td><td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
            <td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td>
            <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>
            <td><?= htmlspecialchars($r['resultado'] ?? '') ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>