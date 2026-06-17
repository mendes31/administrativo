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
        <h2 class="mt-3"><i class="fas fa-stethoscope me-2"></i><?= htmlspecialchars('Exame') ?> #<?= (int)$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-exames"><?= htmlspecialchars('Exames') ?></a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0">Registro #<?= (int)$item['id'] ?></h4>
            <div>
                <?php if (in_array('SstUpdateExame', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-exame/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-exames" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Nome:</th><td><?= formatCellValue('nome', $item['nome'] ?? null) ?></td></tr>
<tr><th width="35%">Descrição:</th><td><?= formatCellValue('descricao', $item['descricao'] ?? null) ?></td></tr>
<tr><th width="35%">Periodicidade (meses):</th><td><?= formatCellValue('periodicidade_meses', $item['periodicidade_meses'] ?? null) ?></td></tr>
<tr><th width="35%">Status:</th><td><?= formatCellValue('status', $item['status'] ?? null) ?></td></tr>

                    <tr><th>Cadastrado em:</th><td><?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td></tr>
                    <tr><th>Atualizado em:</th><td><?= !empty($item['updated_at']) ? date('d/m/Y H:i', strtotime($item['updated_at'])) : '-' ?></td></tr>
                </table></div>
            </div>
            
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>