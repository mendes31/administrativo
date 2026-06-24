<?php
$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-file-contract me-2"></i><?= htmlspecialchars($item['tipo'] ?? '') ?> — <?= htmlspecialchars($item['titulo'] ?? '') ?></h2>
        <div class="ms-auto d-flex gap-1">
            <?php if (in_array('SstUpdatePrograma', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-programa/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm">Editar</a><?php endif; ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-programas" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>
    <div class="card shadow-sm mb-3" data-adms-help-section="aba-programa-dados">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th width="30%">Tipo</th><td><?= htmlspecialchars($item['tipo'] ?? '') ?></td></tr>
                <tr><th>Versão</th><td><?= htmlspecialchars($item['versao'] ?? '-') ?></td></tr>
                <tr><th>Status</th><td><span class="badge bg-<?= ($item['status'] ?? '') === 'Vigente' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($item['status'] ?? '') ?></span></td></tr>
                <tr><th>Vigência</th><td><?= !empty($item['vigencia_inicio']) ? date('d/m/Y', strtotime($item['vigencia_inicio'])) : '-' ?> — <?= !empty($item['vigencia_fim']) ? date('d/m/Y', strtotime($item['vigencia_fim'])) : 'indeterminado' ?></td></tr>
                <tr><th>Responsável</th><td><?= htmlspecialchars($item['responsavel_nome'] ?? '-') ?></td></tr>
                <tr><th>Médico</th><td><?= htmlspecialchars($item['medico_nome'] ?? '-') ?></td></tr>
                <tr><th>Escopo</th><td><?= htmlspecialchars($item['cargo_nome'] ?? 'Todos os cargos') ?> / <?= htmlspecialchars($item['departamento_nome'] ?? 'Todos os deptos') ?></td></tr>
                <tr><th>Descrição</th><td><?= nl2br(htmlspecialchars($item['descricao'] ?? '-')) ?></td></tr>
                <tr><th>Observações</th><td><?= nl2br(htmlspecialchars($item['observacoes'] ?? '-')) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="card shadow-sm" data-adms-help-section="aba-programa-anexos">
        <div class="card-header"><i class="fas fa-paperclip me-1"></i> Documentos anexos</div>
        <div class="card-body">
            <?php if (empty($this->data['anexos'])): ?>
                <p class="text-muted mb-0">Nenhum anexo. Edite o programa para enviar o PDF do PGR/PCMSO.</p>
            <?php else: foreach ($this->data['anexos'] as $anexo): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <a href="<?= $_ENV['URL_ADM'] ?>../<?= htmlspecialchars($anexo['file_path'] ?? '') ?>" target="_blank"><?= htmlspecialchars($anexo['file_name'] ?? '') ?></a>
                    <small class="text-muted"><?= !empty($anexo['created_at']) ? date('d/m/Y H:i', strtotime($anexo['created_at'])) : '' ?></small>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
