<?php
/** @var array $policy */
$policy = $this->data['policy'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Visualizar Política Interna</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
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
                <?php if (in_array('UpdatePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                       class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <?php if (in_array('DeletePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
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
                            <?php
                            $requiresAck = !empty($policy['requires_ack']);
                            $readStatus = $this->data['read_status'] ?? null;
                            $acknowledged = $readStatus && !empty($readStatus['acknowledged']);
                            if ($requiresAck):
                                ?>
                                <?php if ($acknowledged): ?>
                                    <span class="badge bg-success ms-2">
                                        <i class="fas fa-check-circle me-1"></i>Ciente
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark ms-2" style="border:1px solid #dc3545;">
                                        <i class="fas fa-triangle-exclamation me-1"></i>Ciência pendente
                                    </span>
                                <?php endif; ?>
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
                            <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($policy['usuario_nome'] ?? 'N/A'); ?>
                            <?php if (!empty($policy['created_at'])): ?>
                                <span class="ms-3" title="Criada em">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($policy['created_at'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($policy['expire_at'])): ?>
                                <span class="ms-3" title="Expira em">
                                    <i class="fas fa-hourglass-end me-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($policy['expire_at'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($policy['updated_at']) && $policy['updated_at'] !== $policy['created_at']): ?>
                                <span class="ms-3">
                                    <i class="fas fa-edit me-1"></i>Atualizada em: <?php echo date('d/m/Y H:i', strtotime($policy['updated_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php
                        $requiresAck = !empty($policy['requires_ack']);
                        $userId = $_SESSION['user_id'] ?? null;
                        if ($requiresAck && $userId) {
                            // Botão de ciência, como no módulo de Informativos
                            $readStatus = $this->data['read_status'] ?? null;
                            $acknowledged = $readStatus && !empty($readStatus['acknowledged']);
                            if ($acknowledged) {
                                echo '<button type="button" class="btn btn-outline-success w-100 mb-3" disabled><i class="fas fa-check me-1"></i>Ciente confirmado</button>';
                            } else {
                                ?>
                                <button type="button"
                                        id="btn-ack-policy"
                                        class="btn btn-success w-100 mb-3"
                                        data-policy-id="<?php echo (int)($policy['id'] ?? 0); ?>">
                                    <i class="fas fa-check me-1"></i>Estou ciente
                                </button>
                                <?php
                            }
                        }
                        ?>

                        <div class="mb-4">
                            <h5>Conteúdo</h5>
                            <div class="border rounded p-3 bg-light">
                                <?php
                                $conteudo = $policy['conteudo'] ?? '';
                                // Compatibilidade: se vier HTML (ex.: TinyMCE), renderiza como HTML; senão mantém escape e quebras de linha.
                                if (preg_match('/<[^>]+>/', $conteudo)) {
                                    echo $conteudo;
                                } else {
                                    echo nl2br(htmlspecialchars($conteudo));
                                }
                                ?>
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
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Urgente:</small><br>
                                    <?php if (!empty($policy['urgente'])): ?>
                                        <span class="badge bg-danger">Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Categoria:</small><br>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?></span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Departamento:</small><br>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($policy['department_name'] ?? ''); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Expira em:</small><br>
                                    <?php if (!empty($policy['expire_at'])): ?>
                                        <span><?php echo date('d/m/Y H:i', strtotime($policy['expire_at'])); ?></span>
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

<script>
// Registrar leitura ao carregar a página (similar ao módulo de Informativos)
(function() {
    const policyId = <?php echo (int)($policy['id'] ?? 0); ?>;
    if (!policyId) return;

    try {
        fetch('<?php echo $_ENV['URL_ADM']; ?>read-policy/' + policyId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(() => {});
    } catch (e) {}
})();

// Confirmação de ciência via AJAX (similar ao acknowledge-informativo)
document.getElementById('btn-ack-policy')?.addEventListener('click', function () {
    const policyId = this.getAttribute('data-policy-id');
    if (!policyId) return;

    this.disabled = true;

    fetch('<?php echo $_ENV['URL_ADM']; ?>acknowledge-policy/' + policyId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function (resp) {
        return resp.json().catch(() => ({}));
    }).then(function (data) {
        if (data && data.success) {
            window.location.reload();
        } else {
            alert(data && data.message ? data.message : 'Erro ao confirmar ciência.');
            document.getElementById('btn-ack-policy').disabled = false;
        }
    }).catch(function () {
        alert('Erro de comunicação ao confirmar ciência.');
        document.getElementById('btn-ack-policy').disabled = false;
    });
});
</script>

