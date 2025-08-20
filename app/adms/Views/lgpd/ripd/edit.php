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

// Extrair lista de usuários se disponível
$users = $this->data['users'] ?? [];
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-dashboard">Dashboard LGPD</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd">Relatórios RIPD</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-view/<?= $id ?? '' ?>"><?= htmlspecialchars($codigo ?? '') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Editar RIPD</li>
        </ol>
    </nav>

    <!-- Alertas -->
    <?php include_once 'app/adms/Views/partials/alerts.php'; ?>

    <!-- Card Principal -->
    <div class="card border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                Editar Relatório de Impacto à Proteção de Dados (RIPD)
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-edit/update/<?= $id ?? '' ?>">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('ripd_edit') ?>">
                
                <!-- Seção de Identificação -->
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Identificação do RIPD</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="aipd_id" class="form-label">AIPD Relacionada</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars(($aipd_codigo ?? '') . ' - ' . ($aipd_titulo ?? '')) ?>" readonly>
                                <small class="text-muted">AIPD não pode ser alterada após criação</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="titulo" class="form-label">Título do RIPD *</label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required 
                                       value="<?= htmlspecialchars($titulo ?? '') ?>"
                                       placeholder="Ex: RIPD - Tratamento de Dados de Clientes">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="versao" class="form-label">Versão</label>
                                <input type="text" name="versao" id="versao" class="form-control" 
                                       value="<?= htmlspecialchars($versao ?? '') ?>" placeholder="1.0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_elaboracao" class="form-label">Data de Elaboração *</label>
                                <input type="date" name="data_elaboracao" id="data_elaboracao" class="form-control" 
                                       value="<?= $data_elaboracao ?? '' ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="elaborador_id" class="form-label">Elaborador</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($elaborador_nome ?? '') ?>" readonly>
                                <small class="text-muted">Elaborador não pode ser alterado</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Workflow -->
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-tasks me-2"></i>Workflow de Aprovação</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="revisor_id" class="form-label">Revisor</label>
                                <select name="revisor_id" id="revisor_id" class="form-select">
                                    <option value="">Selecione o revisor</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= (($revisor_id ?? '') == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="aprovador_id" class="form-label">Aprovador</label>
                                <select name="aprovador_id" id="aprovador_id" class="form-select">
                                    <option value="">Selecione o aprovador</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= (($aprovador_id ?? '') == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Rascunho" <?= (($status ?? '') == 'Rascunho') ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="Em Revisão" <?= (($status ?? '') == 'Em Revisão') ? 'selected' : '' ?>>Em Revisão</option>
                                    <option value="Aprovado" <?= (($status ?? '') == 'Aprovado') ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="Rejeitado" <?= (($status ?? '') == 'Rejeitado') ? 'selected' : '' ?>>Rejeitado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_aprovacao" class="form-label">Data de Aprovação</label>
                                <input type="date" name="data_aprovacao" id="data_aprovacao" class="form-control" 
                                       value="<?= $data_aprovacao ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="responsavel_implementacao" class="form-label">Responsável pela Implementação</label>
                                <select name="responsavel_implementacao" id="responsavel_implementacao" class="form-select">
                                    <option value="">Selecione o responsável</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= (($responsavel_implementacao ?? '') == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Conteúdo -->
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-file-text me-2"></i>Conteúdo do Relatório</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="conclusao_geral" class="form-label">Conclusão Geral *</label>
                            <textarea name="conclusao_geral" id="conclusao_geral" class="form-control" rows="4" required
                                                                             placeholder="Descreva a conclusão geral baseada na AIPD..."><?= htmlspecialchars($conclusao_geral ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="recomendacoes_finais" class="form-label">Recomendações Finais *</label>
                            <textarea name="recomendacoes_finais" id="recomendacoes_finais" class="form-control" rows="4" required
                                                                             placeholder="Liste as recomendações finais para implementação..."><?= htmlspecialchars($recomendacoes_finais ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="proximos_passos" class="form-label">Próximos Passos</label>
                            <textarea name="proximos_passos" id="proximos_passos" class="form-control" rows="3"
                                                                             placeholder="Descreva os próximos passos a serem tomados..."><?= htmlspecialchars($proximos_passos ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prazo_implementacao" class="form-label">Prazo para Implementação</label>
                                <input type="date" name="prazo_implementacao" id="prazo_implementacao" class="form-control" 
                                       value="<?= $prazo_implementacao ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Observações -->
                <div class="card mb-3 border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Observações e Comentários</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="observacoes_revisao" class="form-label">Observações da Revisão</label>
                            <textarea name="observacoes_revisao" id="observacoes_revisao" class="form-control" rows="3"
                                                                             placeholder="Observações do revisor..."><?= htmlspecialchars($observacoes_revisao ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_aprovacao" class="form-label">Observações da Aprovação</label>
                            <textarea name="observacoes_aprovacao" id="observacoes_aprovacao" class="form-control" rows="3"
                                                                             placeholder="Observações do aprovador..."><?= htmlspecialchars($observacoes_aprovacao ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-flex justify-content-between">
                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-view/<?= $id ?? '' ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Atualizar RIPD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validação de campos obrigatórios
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = ['titulo', 'conclusao_geral', 'recomendacoes_finais'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos obrigatórios.');
    }
});

// Auto-preenchimento de data de aprovação quando status for "Aprovado"
document.getElementById('status').addEventListener('change', function() {
    const dataAprovacao = document.getElementById('data_aprovacao');
    if (this.value === 'Aprovado' && !dataAprovacao.value) {
        dataAprovacao.value = new Date().toISOString().split('T')[0];
    }
});
</script>
