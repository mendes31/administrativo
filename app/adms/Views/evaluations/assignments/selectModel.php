<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
$models = $this->data['models'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user-plus"></i> Atribuir Avaliação
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Atribuir</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-primary shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Selecione um Modelo de Avaliação</h5>
        </div>
        <div class="card-body">
            <?php if (empty($models)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>Nenhum modelo de avaliação encontrado</h5>
                    <p>Você precisa criar um modelo de avaliação antes de poder atribuí-lo.</p>
                    <a href="<?= $_ENV['URL_ADM'] ?>create-evaluation-model-with-questions" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Criar Novo Modelo
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($models as $model): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="fas fa-clipboard-list"></i>
                                        <?= htmlspecialchars($model['titulo']) ?>
                                    </h6>
                                    <p class="card-text text-muted small">
                                        <strong>Treinamento:</strong> <?= htmlspecialchars($model['training_name'] ?? 'N/A') ?>
                                    </p>
                                    <p class="card-text text-muted small">
                                        <strong>Questões:</strong> <?= $model['total_questoes'] ?? 0 ?>
                                    </p>
                                    <p class="card-text text-muted small">
                                        <strong>Pontos:</strong> <?= number_format($model['total_pontos'] ?? 0, 2) ?>
                                    </p>
                                    <p class="card-text">
                                        <?= nl2br(htmlspecialchars(substr($model['descricao'] ?? '', 0, 100))) ?>
                                        <?php if (strlen($model['descricao'] ?? '') > 100): ?>...<?php endif; ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= ($model['ativo'] ?? 1) ? 'success' : 'secondary' ?>">
                                            <?= ($model['ativo'] ?? 1) ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                        <a href="<?= $_ENV['URL_ADM'] ?>assign-evaluation/<?= $model['id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-plus"></i> Atribuir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
