<?php
// ... existing code ...
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
        <div class="card-header hstack gap-2">
            <span>Alteração #<?= $this->data['log']['id'] ?></span>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-2">Tabela</dt>
                <dd class="col-sm-4"><?= htmlspecialchars($this->data['log']['tabela']) ?></dd>
                <dt class="col-sm-2">ID Objeto</dt>
                <dd class="col-sm-4"><?= $this->data['log']['objeto_id'] ?></dd>
                <dt class="col-sm-2">Usuário</dt>
                <dd class="col-sm-4"><?= $this->data['log']['usuario_nome'] ? htmlspecialchars($this->data['log']['usuario_nome']) : $this->data['log']['usuario_id'] ?></dd>
                <dt class="col-sm-2">Data</dt>
                <dd class="col-sm-4"><?= date('d/m/Y H:i', strtotime($this->data['log']['data_alteracao'])) ?></dd>
                <dt class="col-sm-2">Tipo</dt>
                <dd class="col-sm-4"><?= htmlspecialchars($this->data['log']['tipo_operacao']) ?></dd>
                <dt class="col-sm-2">IP</dt>
                <dd class="col-sm-4"><?= htmlspecialchars($this->data['log']['ip']) ?></dd>
            </dl>
            <h5 class="mt-4">Campos Alterados</h5>
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
                                <td><?= htmlspecialchars($det['valor_anterior'] ?? '') ?></td>
                                <td><?= htmlspecialchars($det['valor_novo'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Nenhuma diferença registrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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