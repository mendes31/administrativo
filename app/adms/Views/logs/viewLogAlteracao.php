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
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-database text-primary me-2"></i>
                            <strong>Tabela:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-primary text-white"><?= htmlspecialchars($this->data['log']['tabela']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-hashtag text-info me-2"></i>
                            <strong>ID Objeto:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-info text-white"><?= $this->data['log']['objeto_id'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-user text-success me-2"></i>
                            <strong>Usuário:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-success text-white"><?= $this->data['log']['usuario_nome'] ? htmlspecialchars($this->data['log']['usuario_nome']) : $this->data['log']['usuario_id'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-alt text-warning me-2"></i>
                            <strong>Data:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-warning text-dark"><?= date('d/m/Y H:i', strtotime($this->data['log']['data_alteracao'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-cog text-secondary me-2"></i>
                            <strong>Tipo:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-secondary text-white"><?= htmlspecialchars($this->data['log']['tipo_operacao']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-globe text-danger me-2"></i>
                            <strong>IP:</strong>
                        </div>
                        <div class="info-value">
                            <span class="badge bg-danger text-white"><?= htmlspecialchars($this->data['log']['ip']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Campos Alterados</h5>
            <!-- Desktop: tabela -->
            <div class="d-none d-md-block log-desktop list-desktop">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped table-sm">
                        <thead class="table-success">
                            <tr>
                                <th class="text-start" style="width: 25%; padding-left: 15px;">Campo</th>
                                <th class="text-start" style="width: 37.5%; padding-left: 15px;">Valor Anterior</th>
                                <th class="text-start" style="width: 37.5%; padding-left: 15px;">Novo Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['detalhes'])): ?>
                                <?php foreach ($this->data['detalhes'] as $index => $det): ?>
                                    <tr class="change-row">
                                        <td class="fw-semibold text-primary text-start" style="padding-left: 15px;">
                                            <i class="fas fa-tag me-2"></i><?= htmlspecialchars($det['campo'] ?? '') ?>
                                        </td>
                                        <td class="text-break text-start" style="padding-left: 15px;">
                                            <?php if (!empty($det['valor_anterior'])): ?>
                                                <span class="badge bg-secondary text-white"><?= htmlspecialchars($det['valor_anterior']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-break text-start" style="padding-left: 15px;">
                                            <?php if (!empty($det['valor_novo'])): ?>
                                                <span class="badge bg-success text-white"><?= htmlspecialchars($det['valor_novo']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Nenhuma diferença registrada.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile: cards -->
            <div class="d-block d-md-none log-mobile list-mobile">
                <?php if (!empty($this->data['detalhes'])): ?>
                    <?php foreach ($this->data['detalhes'] as $idx => $det): ?>
                        <div class="card mb-3 shadow-sm border-0">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <span class="fw-semibold text-primary"><?= htmlspecialchars($det['campo'] ?? '') ?></span>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-arrow-left text-secondary me-2"></i>
                                            <span class="fw-semibold text-secondary">Valor Anterior:</span>
                                        </div>
                                        <div class="ms-4">
                                            <?php if (!empty($det['valor_anterior'])): ?>
                                                <span class="badge bg-secondary text-white fs-6"><?= htmlspecialchars($det['valor_anterior']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-arrow-right text-success me-2"></i>
                                            <span class="fw-semibold text-success">Novo Valor:</span>
                                        </div>
                                        <div class="ms-4">
                                            <?php if (!empty($det['valor_novo'])): ?>
                                                <span class="badge bg-success text-white fs-6"><?= htmlspecialchars($det['valor_novo']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma diferença registrada.
                    </div>
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

<style>
/* Estilos para informações gerais */
.info-item {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
}

.info-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.info-label {
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.info-value {
    font-size: 1em;
    font-weight: 500;
}

/* Estilos para a tabela de alterações */
.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 123, 255, 0.05);
}

.table-striped > tbody > tr:nth-of-type(even) > td {
    background-color: rgba(40, 167, 69, 0.05);
}

.change-row:hover {
    background-color: rgba(0, 123, 255, 0.1) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.table th {
    border-top: none;
    font-weight: 600;
    letter-spacing: 0.5px;
    background-color: #28a745 !important;
    color: white !important;
    border-color: #1e7e34 !important;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
    text-align: left;
}

.table th {
    text-align: left;
    padding-left: 15px;
}

/* Estilos para badges */
.badge {
    font-size: 0.85em;
    padding: 6px 10px;
    border-radius: 6px;
    font-weight: 500;
}

/* Estilos para cards mobile */
.log-mobile .card {
    border-left: 4px solid #28a745;
    transition: all 0.2s ease;
}

.log-mobile .card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.log-mobile .card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

/* Responsividade */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9em;
    }
    
    .badge {
        font-size: 0.8em;
        padding: 4px 8px;
    }
    
    .info-item {
        padding: 12px;
    }
    
    .info-label {
        font-size: 0.85em;
    }
}
</style> 