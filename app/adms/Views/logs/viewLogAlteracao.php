<?php
// Preparar destaque do cabeçalho conforme o tipo da operação
$tipoOperacao = strtoupper($this->data['log']['tipo_operacao'] ?? '');
$badgeClass = 'bg-secondary';
if ($tipoOperacao === 'INSERT') {
    $badgeClass = 'bg-success';
} elseif ($tipoOperacao === 'UPDATE') {
    $badgeClass = 'bg-warning text-dark';
} elseif ($tipoOperacao === 'DELETE') {
    $badgeClass = 'bg-danger';
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes da Modificação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-log-alteracoes" class="text-decoration-none">Log de Modificações</a>
            </li>
            <li class="breadcrumb-item">Detalhes</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-semibold">Alteração #<?= $this->data['log']['id'] ?></span>
            <span class="badge <?= $badgeClass ?> px-3 py-2">
                <?= $tipoOperacao ?>
            </span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-5 col-sm-2">Tabela</dt>
                <dd class="col-7 col-sm-4"><?= htmlspecialchars($this->data['log']['tabela']) ?></dd>
                <dt class="col-5 col-sm-2">ID Objeto</dt>
                <dd class="col-7 col-sm-4"><?= $this->data['log']['objeto_id'] ?></dd>
                <dt class="col-5 col-sm-2">Usuário</dt>
                <dd class="col-7 col-sm-4"><?= $this->data['log']['usuario_nome'] ? htmlspecialchars($this->data['log']['usuario_nome']) : $this->data['log']['usuario_id'] ?></dd>
                <dt class="col-5 col-sm-2">Data</dt>
                <dd class="col-7 col-sm-4"><?= date('d/m/Y H:i', strtotime($this->data['log']['data_alteracao'])) ?></dd>
                <dt class="col-5 col-sm-2">Tipo</dt>
                <dd class="col-7 col-sm-4"><?= htmlspecialchars($this->data['log']['tipo_operacao']) ?></dd>
                <dt class="col-5 col-sm-2">IP</dt>
                <dd class="col-7 col-sm-4"><?= htmlspecialchars($this->data['log']['ip']) ?></dd>
            </dl>

            <h5 class="mt-4 mb-3">Campos Alterados</h5>
            <!-- Desktop: tabela -->
            <div class="d-none d-md-block log-desktop list-desktop">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valor Anterior</th>
                                <th>Novo Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['detalhes'])): ?>
                                <?php foreach ($this->data['detalhes'] as $det): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($det['campo'] ?? '') ?></td>
                                        <td class="text-break"><?= htmlspecialchars($det['valor_anterior'] ?? '') ?></td>
                                        <td class="text-break"><?= htmlspecialchars($det['valor_novo'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3">Nenhuma diferença registrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile: cards -->
            <div class="d-block d-md-none log-mobile list-mobile">
                <?php if (!empty($this->data['detalhes'])): ?>
                    <?php foreach ($this->data['detalhes'] as $idx => $det): ?>
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold mb-1"><?= htmlspecialchars($det['campo'] ?? '') ?></div>
                                        <div class="small"><b>Antes:</b> <span class="text-break"><?= htmlspecialchars($det['valor_anterior'] ?? '') ?></span></div>
                                        <div class="small"><b>Depois:</b> <span class="text-break"><?= htmlspecialchars($det['valor_novo'] ?? '') ?></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">Nenhuma diferença registrada.</div>
                <?php endif; ?>
            </div>
            <?php if (!empty($this->data['justificativa'])): ?>
                <h5 class="mt-4">Justificativa</h5>
                <div class="alert alert-info">
                    <strong>Justificativa:</strong> <?= nl2br(htmlspecialchars($this->data['justificativa']['justificativa'])) ?><br>
                    <strong>Assinatura:</strong> <?= htmlspecialchars($this->data['justificativa']['assinatura']) ?><br>
                    <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($this->data['justificativa']['data_justificativa'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 