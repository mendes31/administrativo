<?php
$informativo = $this->data['informativo'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Informativo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none">Informativos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-eye me-2"></i>Detalhes do Informativo</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (isset($this->data['buttonPermission']['UpdateInformativo'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-informativo/<?php echo $informativo['id']; ?>" class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <?php if (isset($this->data['buttonPermission']['DeleteInformativo'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>delete-informativo/<?php echo $informativo['id']; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Tem certeza que deseja excluir este informativo?');">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="btn btn-secondary btn-sm mb-1">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Título e Status -->
                    <div class="mb-4">
                        <h3 class="mb-2">
                            <?php echo htmlspecialchars($informativo['titulo']); ?>
                            <?php if ($informativo['urgente']): ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                </span>
                            <?php endif; ?>
                        </h3>
                        
                        <div class="d-flex gap-2 mb-2">
                            <span class="badge bg-info"><?php echo htmlspecialchars($informativo['categoria']); ?></span>
                            <?php if ($informativo['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-muted small">
                            <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($informativo['usuario_nome'] ?? 'N/A'); ?>
                            <span class="ms-3">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?>
                            </span>
                            <?php if ($informativo['updated_at'] !== $informativo['created_at']): ?>
                                <span class="ms-3">
                                    <i class="fas fa-edit me-1"></i>Atualizado em: <?php echo date('d/m/Y H:i', strtotime($informativo['updated_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Conteúdo -->
                    <div class="mb-4">
                        <h5>Conteúdo</h5>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($informativo['conteudo'])); ?>
                        </div>
                    </div>
                    
                    <!-- Resumo -->
                    <?php if (!empty($informativo['resumo'])): ?>
                        <div class="mb-4">
                            <h5>Resumo</h5>
                            <div class="border rounded p-3 bg-light">
                                <?php echo htmlspecialchars($informativo['resumo']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <!-- Imagem -->
                    <?php if (!empty($informativo['imagem'])): ?>
                        <div class="mb-4">
                            <h5>Imagem</h5>
                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>"
                                 class="img-fluid rounded shadow"
                                 alt="Imagem do informativo"
                                 style="max-width: 100%; max-height: 300px;"
                                 onerror="this.style.display='none';">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Anexo -->
                    <?php if (!empty($informativo['anexo'])): ?>
                        <div class="mb-4">
                            <h5>Anexo</h5>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>"
                               target="_blank" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-download me-1"></i>Download
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Informações Adicionais -->
                    <div class="mb-4">
                        <h5>Informações</h5>
                        <div class="border rounded p-3 bg-light">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">ID:</small><br>
                                    <strong><?php echo $informativo['id']; ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Status:</small><br>
                                    <?php if ($informativo['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Urgente:</small><br>
                                    <?php if ($informativo['urgente']): ?>
                                        <span class="badge bg-danger">Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Categoria:</small><br>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($informativo['categoria']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 