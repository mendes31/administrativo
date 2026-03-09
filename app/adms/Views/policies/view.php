<?php
/** @var array $policy */
$policy = $this->data['policy'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Política Interna</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="text-decoration-none">Políticas Internas</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-eye me-2"></i>Detalhes da Política</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (!empty($this->data['buttonPermission']) && in_array('UpdatePolicy', $this->data['buttonPermission'], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                       class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <?php if (!empty($this->data['buttonPermission']) && in_array('DeletePolicy', $this->data['buttonPermission'], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                       class="btn btn-danger btn-sm mb-1"
                       onclick="return confirm('Tem certeza que deseja excluir esta política?');">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="btn btn-secondary btn-sm mb-1">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h3 class="mb-2">
                            <?php echo htmlspecialchars($policy['titulo'] ?? ''); ?>
                            <?php if (!empty($policy['urgente'])): ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                </span>
                            <?php endif; ?>
                        </h3>

                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <?php if (!empty($policy['categoria_nome'] ?? $policy['categoria'])): ?>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($policy['categoria_nome'] ?? $policy['categoria']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($policy['department_name'])): ?>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($policy['department_name']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($policy['ativo'])): ?>
                                <span class="badge bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativa</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-muted small mb-3">
                            <?php if (!empty($policy['created_at'])): ?>
                                <span class="me-3">
                                    <i class="fas fa-calendar me-1"></i>
                                    Criada em: <?php echo date('d/m/Y H:i', strtotime($policy['created_at'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($policy['expire_at'])): ?>
                                <span class="me-3">
                                    <i class="fas fa-hourglass-end me-1"></i>
                                    Expira em: <?php echo date('d/m/Y H:i', strtotime($policy['expire_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h5>Conteúdo</h5>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br(htmlspecialchars($policy['conteudo'] ?? '')); ?>
                            </div>
                        </div>

                        <?php if (!empty($policy['resumo'])): ?>
                            <div class="mb-4">
                                <h5>Resumo</h5>
                                <div class="border rounded p-3 bg-light">
                                    <?php echo htmlspecialchars($policy['resumo']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php if (!empty($policy['imagem'])): ?>
                        <div class="mb-4">
                            <h5>Imagem</h5>
                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['imagem']); ?>"
                                 class="img-fluid rounded shadow"
                                 alt="Imagem da política"
                                 style="max-width: 100%; max-height: 300px;"
                                 onerror="this.style.display='none';">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($policy['anexo'])): ?>
                        <div class="mb-4">
                            <h5>Anexo</h5>
                            <div class="d-grid gap-2">
                                <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['anexo']); ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-paperclip me-1"></i>Abrir/Download do Anexo
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>Informações</h5>
                        <div class="border rounded p-3 bg-light">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">ID:</small><br>
                                    <strong><?php echo (int) ($policy['id'] ?? 0); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Status:</small><br>
                                    <?php if (!empty($policy['ativo'])): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativa</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

