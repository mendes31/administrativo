<?php
use App\adms\Helpers\CSRFHelper;

// Extrair dados passados pelo controller
if (isset($this->data['ripd'])) {
    extract($this->data['ripd']);
} else {
    // Fallback caso os dados não estejam disponíveis
    $_SESSION['error'] = "Dados do RIPD não encontrados!";
    header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
    exit;
}
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-dashboard">Dashboard LGPD</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd">Relatórios RIPD</a></li>
            <li class="breadcrumb-item active" aria-current="page">Visualizar RIPD</li>
        </ol>
    </nav>

    <!-- Alertas -->
    <?php include_once 'app/adms/Views/partials/alerts.php'; ?>

    <!-- Cabeçalho do RIPD -->
    <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        <?= htmlspecialchars($titulo ?? '') ?>
                    </h4>
                    <p class="mb-0 mt-1">
                        <strong>Código:</strong> <?= $codigo ?? '' ?> | 
                        <strong>Versão:</strong> <?= $versao ?? '' ?>
                    </p>
                </div>
                <div class="text-end">
                    <?php
                    $statusClass = match($status ?? '') {
                        'Aprovado' => 'bg-success',
                        'Em Revisão' => 'bg-warning',
                        'Rejeitado' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    ?>
                    <span class="badge <?= $statusClass ?> fs-6"><?= $status ?? '' ?></span>
                    <div class="mt-2">
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-export-pdf/<?= $id ?? '' ?>" class="btn btn-info btn-sm me-2" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Exportar PDF
                        </a>
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-edit/<?= $id ?? '' ?>" class="btn btn-warning btn-sm me-2">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Principal -->
        <div class="col-lg-8">
            <!-- Informações da AIPD Relacionada -->
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-link me-2"></i>AIPD Relacionada
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Código:</strong> <?= htmlspecialchars($aipd_codigo ?? '') ?></p>
                            <p><strong>Título:</strong> <?= htmlspecialchars($aipd_titulo ?? '') ?></p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($aipd_status ?? '') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Nível de Risco:</strong> <?= htmlspecialchars($aipd_nivel_risco ?? '') ?></p>
                            <p><strong>Departamento:</strong> <?= htmlspecialchars($departamento_nome ?? '') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conclusão Geral -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Conclusão Geral
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($conclusao_geral ?? '')) ?></p>
                </div>
            </div>

            <!-- Recomendações Finais -->
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Recomendações Finais
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($recomendacoes_finais ?? '')) ?></p>
                </div>
            </div>

            <!-- Próximos Passos -->
            <?php if (!empty($proximos_passos)): ?>
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-route me-2"></i>Próximos Passos
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($proximos_passos ?? '')) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Observações -->
            <?php if (!empty($observacoes_revisao) || !empty($observacoes_aprovacao)): ?>
            <div class="card mb-4 border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Observações e Comentários
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($observacoes_revisao)): ?>
                        <h6>Observações da Revisão:</h6>
                        <p class="mb-3"><?= nl2br(htmlspecialchars($observacoes_revisao ?? '')) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($observacoes_aprovacao)): ?>
                        <h6>Observações da Aprovação:</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($observacoes_aprovacao ?? '')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Lateral -->
        <div class="col-lg-4">
            <!-- Informações do Relatório -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações do Relatório
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>Data de Elaboração:</strong><br>
                        <?= date('d/m/Y', strtotime($data_elaboracao ?? '')) ?></p>
                    
                    <p><strong>Elaborador:</strong><br>
                        <?= htmlspecialchars($elaborador_nome ?? '') ?></p>
                    
                    <?php if (!empty($revisor_nome)): ?>
                        <p><strong>Revisor:</strong><br>
                            <?= htmlspecialchars($revisor_nome ?? '') ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($aprovador_nome)): ?>
                        <p><strong>Aprovador:</strong><br>
                            <?= htmlspecialchars($aprovador_nome ?? '') ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($data_aprovacao)): ?>
                        <p><strong>Data de Aprovação:</strong><br>
                            <?= date('d/m/Y', strtotime($data_aprovacao ?? '')) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($responsavel_implementacao_nome)): ?>
                        <p><strong>Responsável pela Implementação:</strong><br>
                            <?= htmlspecialchars($responsavel_implementacao_nome ?? '') ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($prazo_implementacao)): ?>
                        <p><strong>Prazo para Implementação:</strong><br>
                            <?= date('d/m/Y', strtotime($prazo_implementacao ?? '')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Riscos e Medidas da AIPD -->
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Riscos e Medidas (AIPD)
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Descrição:</h6>
                    <p class="mb-3"><?= htmlspecialchars($aipd_descricao ?? 'Não informado') ?></p>
                    
                    <h6>Observações:</h6>
                    <p class="mb-0"><?= htmlspecialchars($aipd_observacoes ?? 'Não informado') ?></p>
                </div>
            </div>

            <!-- Conclusões da AIPD -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>Conclusões da AIPD
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= htmlspecialchars($aipd_observacoes ?? 'Não informado') ?></p>
                </div>
            </div>

            <!-- Recomendações da AIPD -->
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Recomendações da AIPD
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= htmlspecialchars($aipd_observacoes ?? 'Não informado') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="d-flex justify-content-between mt-4">
        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar à Lista
        </a>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-edit/<?= $id ?? '' ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Editar RIPD
            </a>
            <?php if (($status ?? '') !== 'Aprovado'): ?>
                <button type="button" class="btn btn-danger" onclick="confirmarExclusao()">
                    <i class="fas fa-trash me-1"></i>Excluir RIPD
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExclusao" tabindex="-1" aria-labelledby="modalExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExclusaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este RIPD?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-delete/<?= $id ?? '' ?>" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('ripd_delete') ?>">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao() {
    const modal = new bootstrap.Modal(document.getElementById('modalExclusao'));
    modal.show();
}
</script>
