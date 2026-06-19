<?php
$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
function formatCellValue(string $col, mixed $value): string {
    if ($value === null || $value === '') return '-';
    if (is_bool($value) || $col === 'obrigatorio' || $col === 'termo_assinado') return ($value === true || $value === 1 || $value === '1') ? 'Sim' : 'Não';
    if (str_contains($col, 'data_') && is_string($value)) return strlen($value) > 10 ? date('d/m/Y H:i', strtotime($value)) : date('d/m/Y', strtotime($value));
    return htmlspecialchars((string)$value);
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i><?= htmlspecialchars('EPI') ?> #<?= (int)$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis"><?= htmlspecialchars('EPIs') ?></a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0">Registro #<?= (int)$item['id'] ?></h4>
            <div>
                <?php if (in_array('SstUpdateEpi', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-epi/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Nome:</th><td><?= formatCellValue('nome', $item['nome'] ?? null) ?></td></tr>
<tr><th width="35%">Categoria:</th><td><?= formatCellValue('categoria', $item['categoria'] ?? null) ?></td></tr>
<tr><th width="35%">Descrição:</th><td><?= formatCellValue('descricao', $item['descricao'] ?? null) ?></td></tr>
<tr><th width="35%">Estoque atual:</th><td>
    <?= formatCellValue('estoque_atual', $item['estoque_atual'] ?? null) ?>
    <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?adms_sst_epi_id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-success ms-2">Movimentar</a>
    <?php endif; ?>
</td></tr>
<tr><th width="35%">Estoque mínimo:</th><td><?= formatCellValue('estoque_minimo', $item['estoque_minimo'] ?? null) ?></td></tr>
<tr><th width="35%">Vida útil padrão (dias):</th><td><?= formatCellValue('periodicidade_troca_dias', $item['periodicidade_troca_dias'] ?? null) ?></td></tr>
<tr><th width="35%">Status:</th><td><?= formatCellValue('status', $item['status'] ?? null) ?></td></tr>

                    <tr><th>Cadastrado em:</th><td><?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td></tr>
                    <tr><th>Atualizado em:</th><td><?= !empty($item['updated_at']) ? date('d/m/Y H:i', strtotime($item['updated_at'])) : '-' ?></td></tr>
                </table></div>
            </div>
            <?php if (!empty($this->data['movimentos'])): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header hstack"><h5 class="mb-0">Últimas movimentações de estoque</h5>
                <?php if (in_array('SstListEpiMovimentos', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos?adms_sst_epi_id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary ms-auto">Ver todas</a>
                <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>CA</th><th>Saldo</th></tr></thead>
                        <tbody>
                        <?php foreach ($this->data['movimentos'] as $m): ?>
                        <tr>
                            <td><?= !empty($m['data_movimento']) ? date('d/m/Y', strtotime($m['data_movimento'])) : '-' ?></td>
                            <td><?= htmlspecialchars($m['tipo_movimento'] ?? '') ?></td>
                            <td><?= (int)($m['quantidade'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($m['ca_numero'] ?? '-') ?></td>
                            <td><?= isset($m['saldo_apos']) ? (int)$m['saldo_apos'] : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php include './app/adms/Views/sst/partials/riscos_relacionados_readonly.php'; ?>
            
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>