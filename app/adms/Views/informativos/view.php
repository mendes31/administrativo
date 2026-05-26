<?php
$informativo = $this->data['informativo'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Visualizar Informativo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
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
                <?php
                $btnPerm = $this->data['buttonPermission'] ?? [];
                $canManageInf = !empty($this->data['can_manage_informativo']);
                ?>
                <?php if ($canManageInf && is_array($btnPerm) && in_array('UpdateInformativo', $btnPerm, true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-informativo/<?php echo $informativo['id']; ?>" class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <?php
                $canResendPush = is_array($btnPerm)
                    && in_array('ResendInformativoPush', $btnPerm, true)
                    && !empty($informativo['ativo'])
                    && \App\adms\Models\Services\InformativoPublishNotifier::isWithinPublicationWindow($informativo);
                ?>
                <?php if ($canResendPush): ?>
                    <?php $csrfResend = \App\adms\Helpers\CSRFHelper::generateCSRFToken('resend_informativo_push'); ?>
                    <div class="btn-group d-inline mb-1">
                        <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                title="Reenviar notificação push PWA para colaboradores">
                            <i class="fas fa-bell me-1"></i>Reenviar push PWA
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <form method="post"
                                      action="<?php echo $_ENV['URL_ADM']; ?>resend-informativo-push/<?php echo (int) $informativo['id']; ?>"
                                      onsubmit="return confirm('Enviar push apenas para quem ainda NÃO recebeu?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfResend; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $informativo['id']; ?>">
                                    <input type="hidden" name="mode" value="pending">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-user-plus me-1 text-info"></i>Somente quem não recebeu
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form method="post"
                                      action="<?php echo $_ENV['URL_ADM']; ?>resend-informativo-push/<?php echo (int) $informativo['id']; ?>"
                                      onsubmit="return confirm('Reenviar push para TODOS os colaboradores elegíveis?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfResend; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $informativo['id']; ?>">
                                    <input type="hidden" name="mode" value="all">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-users me-1 text-warning"></i>Todos os colaboradores
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($canManageInf && is_array($btnPerm) && in_array('InformativoPushStatus', $btnPerm, true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>informativo-push-status/<?php echo (int) $informativo['id']; ?>"
                       class="btn btn-outline-info btn-sm mb-1"
                       title="Ver quem recebeu o push">
                        <i class="fas fa-satellite-dish me-1"></i>Status Push
                    </a>
                <?php endif; ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm mb-1';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
                <?php if ($canManageInf && is_array($btnPerm) && in_array('DeleteInformativo', $btnPerm, true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>delete-informativo/<?php echo $informativo['id']; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Tem certeza que deseja excluir este informativo?');">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="btn btn-secondary btn-sm mb-1 d-none d-md-inline-block">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <button type="button"
                        class="btn btn-secondary btn-sm mb-1 d-inline d-md-none"
                        onclick="window.history.back();"
                        aria-label="Voltar">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </button>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Título e Status -->
                    <div class="mb-4">
                        <h3 class="mb-2">
                            <?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?>
                            <?php if ($informativo['urgente']): ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                </span>
                            <?php endif; ?>
                        </h3>
                        
                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <span class="badge bg-info"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['categoria_nome'] ?? $informativo['categoria'] ?? ''); ?></span>
                            <?php if (!empty($informativo['department_name'])): ?>
                                <span class="badge bg-secondary"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['department_name'] ?? ''); ?></span>
                            <?php endif; ?>
                            <?php if ($informativo['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php endif; ?>
                        </div>

                        <?php
                        $requiresAck = !empty($informativo['requires_ack']);
                        $userId = $_SESSION['user_id'] ?? null;
                        if ($requiresAck && $userId) {
                            $read = $this->data['read_status'] ?? null;
                            $acknowledged = $read && !empty($read['acknowledged']);
                            if ($acknowledged) {
                                echo '<button type="button" class="btn btn-outline-success w-100" disabled><i class="fas fa-check me-1"></i>Ciente confirmado</button>';
                            } else {
                                echo '<form method="post" action="' . $_ENV['URL_ADM'] . 'acknowledge-informativo/' . (int)$informativo['id'] . '">';
                                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(\App\adms\Helpers\CSRFHelper::generateCSRFToken('ack_inf')) . '">';
                                echo '<button type="submit" class="btn btn-success w-100"><i class="fas fa-check me-1"></i>Estou ciente</button>';
                                echo '</form>';
                            }
                        }
                        ?>
                        
                        <div class="text-muted small">
                            <i class="fas fa-user me-1"></i>Por: <?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['usuario_nome'] ?? 'N/A'); ?>
                            <span class="ms-3" title="Publicado em">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?>
                            </span>
                            <?php if (!empty($informativo['expire_at'])): ?>
                                <span class="ms-3" title="Expira em">
                                    <i class="fas fa-hourglass-end me-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($informativo['expire_at'])); ?>
                                </span>
                            <?php endif; ?>
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
                        <div class="border rounded p-3 bg-light adms-view-rich-content">
                            <?php
                            $conteudo = $informativo['conteudo'] ?? '';
                            // Compatibilidade: se vier HTML (ex.: TinyMCE), renderiza como HTML.
                            // Se vier texto puro/Markdown sem HTML, mantém escape e quebra de linha.
                            if (preg_match('/<[^>]+>/', $conteudo)) {
                                echo $conteudo;
                            } else {
                                echo nl2br(htmlspecialchars($conteudo));
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Resumo -->
                    <?php if (!empty($informativo['resumo'])): ?>
                        <div class="mb-4">
                            <h5>Resumo</h5>
                            <div class="border rounded p-3 bg-light">
                                <?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['resumo'] ?? ''); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <!-- Imagem -->
                    <?php if (!empty($informativo['imagem'])): ?>
                        <?php
                        $informativoImageUrl = $_ENV['URL_ADM'] . 'serve-file?path=' . urlencode($informativo['imagem']);
                        ?>
                        <div class="mb-4">
                            <h5>Imagem</h5>
                            <img src="<?php echo htmlspecialchars($informativoImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                 data-adms-image-preview="<?php echo htmlspecialchars($informativoImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                 class="img-fluid rounded shadow"
                                 alt="Imagem do informativo"
                                 title="Clique para ampliar"
                                 role="button"
                                 tabindex="0"
                                 style="max-width: 100%; max-height: 300px; cursor: pointer;"
                                 loading="lazy"
                                 decoding="async"
                                 fetchpriority="low"
                                 onerror="this.style.display='none';">
                            <p class="text-muted small mt-1 mb-0"><i class="fas fa-search-plus me-1"></i>Clique na imagem para ampliar</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Anexo -->
                    <?php if (!empty($informativo['anexo'])): ?>
                        <div class="mb-4">
                            <h5>Anexo</h5>
                            <div class="d-grid gap-2">
                                <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <?php echo \App\adms\Helpers\FormatHelper::renderFileIcon($informativo['anexo'], 'me-1'); ?>
                                    Abrir/Download do Anexo
                                </a>
                            </div>
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
                                    <span class="badge bg-info"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['categoria_nome'] ?? $informativo['categoria'] ?? ''); ?></span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Departamento:</small><br>
                                    <span class="badge bg-secondary"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['department_name'] ?? ''); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Expira em:</small><br>
                                    <?php if (!empty($informativo['expire_at'])): ?>
                                        <span><?php echo date('d/m/Y H:i', strtotime($informativo['expire_at'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
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

<?php include __DIR__ . '/../partials/image_preview_modal.php'; ?>