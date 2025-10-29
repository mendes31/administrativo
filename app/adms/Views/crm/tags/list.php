<?php
$tags = $this->data['tags'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-tags text-primary me-2"></i>
            Tags CRM
        </h1>
        <a href="<?= $_ENV['URL_ADM'] ?>crm-create-tag" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Nova Tag
        </a>
    </div>

    <!-- Lista de Tags -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($tags)): ?>
                <div class="row">
                    <?php foreach ($tags as $tag): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge" style="background-color: <?= htmlspecialchars($tag['color']) ?>; font-size: 1rem; padding: 0.5rem 1rem;">
                                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-update-tag/<?= $tag['id'] ?>" 
                                               class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-tag/<?= $tag['id'] ?>" 
                                               class="btn btn-danger" title="Excluir"
                                               onclick="return confirm('Tem certeza que deseja excluir esta tag?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php if ($tag['description']): ?>
                                        <p class="text-muted small mb-0">
                                            <?= htmlspecialchars($tag['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma tag encontrada.</p>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-create-tag" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar Primeira Tag
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

