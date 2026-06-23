<?php
use App\adms\Helpers\SstTreinamentoStatusHelper;

$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
$riscosRelacionados = $this->data['riscosRelacionados'] ?? [];

function fmtTrein(mixed $v): string {
    return ($v === null || $v === '') ? '-' : htmlspecialchars((string)$v);
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-graduation-cap me-2"></i>Treinamento #<?= (int)$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos">Treinamentos</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0"><?= htmlspecialchars($item['nome'] ?? '') ?><?php if (!empty($item['codigo'])): ?> <small class="text-muted">(<?= htmlspecialchars($item['codigo']) ?>)</small><?php endif; ?></h4>
            <div>
                <?php if (in_array('SstUpdateTreinamento', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-treinamento/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados do treinamento</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Código:</th><td><?= fmtTrein($item['codigo'] ?? null) ?></td></tr>
                    <tr><th>NR referência:</th><td><?= fmtTrein($item['nr_referencia'] ?? null) ?></td></tr>
                    <tr><th>Tipo:</th><td><?= fmtTrein($item['tipo'] ?? null) ?></td></tr>
                    <tr><th>Modalidade:</th><td><?= fmtTrein($item['modalidade'] ?? null) ?></td></tr>
                    <tr><th>Carga horária:</th><td><?= !empty($item['carga_horaria_minutos']) ? (int)$item['carga_horaria_minutos'] . ' min' : '-' ?></td></tr>
                    <tr><th>Validade reciclagem:</th><td><?= !empty($item['validade_meses']) ? (int)$item['validade_meses'] . ' meses' : '-' ?></td></tr>
                    <tr><th>Prazo 1º treinamento:</th><td><?= !empty($item['prazo_primeiro_dias']) ? (int)$item['prazo_primeiro_dias'] . ' dias' : '-' ?></td></tr>
                    <tr><th>Descrição:</th><td><?= fmtTrein($item['descricao'] ?? null) ?></td></tr>
                    <tr><th>Status:</th><td><?= fmtTrein($item['status'] ?? null) ?></td></tr>
                </table></div>
            </div>
            <?php include './app/adms/Views/sst/partials/treinamentos_relacionados_readonly.php'; ?>
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>
