<?php
$indicator = $this->data['indicator'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Excluir Indicador Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/adms/strategic-indicators-list" class="text-decoration-none">Indicadores Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Excluir</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header bg-danger text-white">
            <span><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</span>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenção!</strong> Esta ação não pode ser desfeita. Tem certeza que deseja excluir este indicador estratégico?
            </div>

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
                    <label class="form-label fw-bold">Status</label>
                    <p class="form-control-plaintext">
                        <span class="badge bg-<?= ($indicator['status'] ?? '') == 'Ativo' ? 'success' : 'secondary' ?>">
                            <?= htmlspecialchars($indicator['status'] ?? '') ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <form method="post" action="/adms/delete-strategic-indicator/<?= $indicator['id'] ?? '' ?>" class="d-inline">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-2"></i>Sim, Excluir
                            </button>
                        </form>
                        <a href="/adms/view-strategic-indicator/<?= $indicator['id'] ?? '' ?>" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Visualizar
                        </a>
                        <a href="/adms/strategic-indicators-list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



