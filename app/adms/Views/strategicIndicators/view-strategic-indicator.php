<?php
$indicator = $this->data['indicator'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Indicador Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/adms/strategic-indicators-list" class="text-decoration-none">Indicadores Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-chart-line me-2"></i>Detalhes do Indicador Estratégico</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nome do Indicador</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($indicator['nome'] ?? '') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Tipo</label>
                    <p class="form-control-plaintext">
                        <span class="badge bg-<?= ($indicator['tipo'] ?? '') == 'Quantitativo' ? 'primary' : 'info' ?>">
                            <?= htmlspecialchars($indicator['tipo'] ?? '') ?>
                        </span>
                    </p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Unidade de Medida</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($indicator['unidade_medida'] ?? 'Não informado') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Meta</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($indicator['meta'] ?? 'Não informado') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Frequência de Medição</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($indicator['frequencia_medicao'] ?? 'Não informado') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <p class="form-control-plaintext">
                        <span class="badge bg-<?= ($indicator['status'] ?? '') == 'Ativo' ? 'success' : 'secondary' ?>">
                            <?= htmlspecialchars($indicator['status'] ?? '') ?>
                        </span>
                    </p>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold">Descrição</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($indicator['descricao'] ?? 'Não informado') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Data de Criação</label>
                    <p class="form-control-plaintext">
                        <?= $indicator['created_at'] ? date('d/m/Y H:i', strtotime($indicator['created_at'])) : 'Não informado' ?>
                    </p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Última Atualização</label>
                    <p class="form-control-plaintext">
                        <?= $indicator['updated_at'] ? date('d/m/Y H:i', strtotime($indicator['updated_at'])) : 'Não informado' ?>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <a href="/adms/strategic-indicators-edit/<?= $indicator['id'] ?? '' ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="/adms/delete-strategic-indicator/<?= $indicator['id'] ?? '' ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Tem certeza que deseja excluir este indicador?');">
                            <i class="fas fa-trash-alt me-2"></i>Excluir
                        </a>
                        <a href="/adms/strategic-indicators-list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



