<?php ?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i>Relatório de EPIs</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Colaborador</label><select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label">Situação troca</label><select name="status_vencimento" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
            <option value="a_vencer" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'a_vencer' ? 'selected' : '' ?>>A vencer (30d)</option>
            <option value="valido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'valido' ? 'selected' : '' ?>>Em dia</option>
        </select></div>
        <div class="col-md-2"><label class="form-label">Termo assinado</label><select name="termo_assinado" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="sim" <?= ($this->data['filters']['termo_assinado'] ?? '') === 'sim' ? 'selected' : '' ?>>Sim</option>
            <option value="nao" <?= ($this->data['filters']['termo_assinado'] ?? '') === 'nao' ? 'selected' : '' ?>>Não</option>
        </select></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-striped table-sm"><thead><tr><th>Colaborador</th><th>EPI</th><th>Tam.</th><th>Qtd</th><th>Entrega</th><th>Prev. troca</th><th>Termo</th></tr></thead><tbody>
        <?php foreach ($this->data['items'] ?? [] as $r):
            $hoje = date('Y-m-d');
            $troca = $r['data_prevista_troca'] ?? null;
            $rowClass = $troca && $troca < $hoje ? 'table-danger' : ($troca && $troca <= date('Y-m-d', strtotime('+30 days')) ? 'table-warning' : '');
        ?><tr class="<?= $rowClass ?>">
            <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['tamanho'] ?? '—') ?></td>
            <td><?= !empty($r['data_movimento']) ? date('d/m/Y', strtotime($r['data_movimento'])) : '-' ?></td>
            <td><?= !empty($r['data_prevista_troca']) ? date('d/m/Y', strtotime($r['data_prevista_troca'])) : '-' ?></td>
            <td><?= !empty($r['termo_assinado']) ? 'Sim' : 'Não' ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>
