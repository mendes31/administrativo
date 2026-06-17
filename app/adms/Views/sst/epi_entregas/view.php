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
        <h2 class="mt-3"><i class="fas fa-hand-holding me-2"></i><?= htmlspecialchars('Entrega de EPI') ?> #<?= (int)$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-entregas"><?= htmlspecialchars('Entregas de EPI') ?></a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0">Registro #<?= (int)$item['id'] ?></h4>
            <div>
                <?php if (in_array('SstUpdateEpiEntrega', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-epi-entrega/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-entregas" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Colaborador:</th><td><?= formatCellValue('adms_user_id', $item['adms_user_id'] ?? null) ?></td></tr>
<tr><th width="35%">EPI:</th><td><?= formatCellValue('adms_sst_epi_id', $item['adms_sst_epi_id'] ?? null) ?></td></tr>
<tr><th width="35%">Movimento:</th><td><?= formatCellValue('tipo_movimento', $item['tipo_movimento'] ?? null) ?></td></tr>
<tr><th width="35%">Quantidade:</th><td><?= formatCellValue('quantidade', $item['quantidade'] ?? null) ?></td></tr>
<tr><th width="35%">Data:</th><td><?= formatCellValue('data_movimento', $item['data_movimento'] ?? null) ?></td></tr>
<tr><th width="35%">Prev. troca:</th><td><?= formatCellValue('data_prevista_troca', $item['data_prevista_troca'] ?? null) ?></td></tr>
<tr><th width="35%">Termo assinado:</th><td><?= formatCellValue('termo_assinado', $item['termo_assinado'] ?? null) ?></td></tr>
<tr><th width="35%">Observações:</th><td><?= formatCellValue('observacoes', $item['observacoes'] ?? null) ?></td></tr>
<tr><th>Colaborador:</th><td><?= htmlspecialchars($item['colaborador_nome'] ?? '-') ?></td></tr>

                    <tr><th>Cadastrado em:</th><td><?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td></tr>
                    <tr><th>Atualizado em:</th><td><?= !empty($item['updated_at']) ? date('d/m/Y H:i', strtotime($item['updated_at'])) : '-' ?></td></tr>
                </table></div>
            </div>
                        <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-paperclip me-2"></i>Anexos</h5></div>
                <div class="card-body">
                    <?php if (empty($this->data['anexos'])): ?>
                        <p class="text-muted mb-0">Nenhum anexo.</p>
                    <?php else: foreach ($this->data['anexos'] as $anexo): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span><?= htmlspecialchars($anexo['file_name'] ?? '') ?></span>
                            <small class="text-muted"><?= !empty($anexo['created_at']) ? date('d/m/Y H:i', strtotime($anexo['created_at'])) : '' ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>