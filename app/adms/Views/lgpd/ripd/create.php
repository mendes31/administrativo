<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-dashboard">Dashboard LGPD</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd">Relatórios RIPD</a></li>
            <li class="breadcrumb-item active" aria-current="page">Criar RIPD</li>
        </ol>
    </nav>

    <!-- Alertas -->
    <?php include_once 'app/adms/Views/partials/alerts.php'; ?>

    <!-- Card Principal -->
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                Criar Relatório de Impacto à Proteção de Dados (RIPD)
            </h5>
        </div>
        <div class="card-body">
            <!-- Opção de Geração Automática -->
            <div class="alert alert-info">
                <h6><i class="fas fa-magic me-2"></i>Geração Automática</h6>
                <p class="mb-2">Você pode gerar um RIPD automaticamente baseado em uma AIPD existente, ou criar manualmente.</p>
                <form method="POST" action="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('ripd_generate') ?>">
                    <input type="hidden" name="action" value="generateFromAipd">
                    <div class="row">
                        <div class="col-md-8">
                            <select name="aipd_id" class="form-select" required>
                                <option value="">Selecione uma AIPD para gerar RIPD automaticamente</option>
                                <?php foreach ($this->data['aipds'] as $aipd): ?>
                                    <option value="<?= $aipd['id'] ?>">
                                        <?= htmlspecialchars($aipd['codigo']) ?> - <?= htmlspecialchars($aipd['titulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-magic me-1"></i>Gerar Automaticamente
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <hr>

            <!-- Formulário Manual -->
            <h6 class="text-muted mb-3">Ou preencha o formulário manualmente:</h6>
            
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('ripd_create') ?>">
                <input type="hidden" name="action" value="create">
                
                <!-- Seção de Identificação -->
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Identificação do RIPD</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="aipd_id" class="form-label">AIPD Relacionada *</label>
                                <select name="aipd_id" id="aipd_id" class="form-select" required>
                                    <option value="">Selecione uma AIPD</option>
                                    <?php foreach ($this->data['aipds'] as $aipd): ?>
                                        <option value="<?= $aipd['id'] ?>">
                                            <?= htmlspecialchars($aipd['codigo']) ?> - <?= htmlspecialchars($aipd['titulo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="titulo" class="form-label">Título do RIPD *</label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required 
                                       placeholder="Ex: RIPD - Tratamento de Dados de Clientes">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="versao" class="form-label">Versão</label>
                                <input type="text" name="versao" id="versao" class="form-control" value="1.0" 
                                       placeholder="1.0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_elaboracao" class="form-label">Data de Elaboração *</label>
                                <input type="date" name="data_elaboracao" id="data_elaboracao" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="elaborador_id" class="form-label">Elaborador *</label>
                                <select name="elaborador_id" id="elaborador_id" class="form-select" required>
                                    <option value="">Selecione o elaborador</option>
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="aprovador_id" class="form-label">Aprovador</label>
                                <select name="aprovador_id" id="aprovador_id" class="form-select">
                                    <option value="">Selecione o aprovador</option>
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Rascunho">Rascunho</option>
                                    <option value="Em Revisão">Em Revisão</option>
                                    <option value="Aprovado">Aprovado</option>
                                    <option value="Rejeitado">Rejeitado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_aprovacao" class="form-label">Data de Aprovação</label>
                                <input type="date" name="data_aprovacao" id="data_aprovacao" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="responsavel_implementacao" class="form-label">Responsável pela Implementação</label>
                                <select name="responsavel_implementacao" id="responsavel_implementacao" class="form-select">
                                    <option value="">Selecione o responsável</option>
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
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
                                      placeholder="Descreva a conclusão geral baseada na AIPD..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="recomendacoes_finais" class="form-label">Recomendações Finais *</label>
                            <textarea name="recomendacoes_finais" id="recomendacoes_finais" class="form-control" rows="4" required
                                      placeholder="Liste as recomendações finais para implementação..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="proximos_passos" class="form-label">Próximos Passos</label>
                            <textarea name="proximos_passos" id="proximos_passos" class="form-control" rows="3"
                                      placeholder="Descreva os próximos passos a serem tomados..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prazo_implementacao" class="form-label">Prazo para Implementação</label>
                                <input type="date" name="prazo_implementacao" id="prazo_implementacao" class="form-control">
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
                                      placeholder="Observações do revisor..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_aprovacao" class="form-label">Observações da Aprovação</label>
                            <textarea name="observacoes_aprovacao" id="observacoes_aprovacao" class="form-control" rows="3"
                                      placeholder="Observações do aprovador..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-flex justify-content-between">
                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Criar RIPD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-preenchimento de campos baseado na AIPD selecionada
document.getElementById('aipd_id').addEventListener('change', function() {
    const aipdId = this.value;
    if (aipdId) {
        // Aqui você pode adicionar lógica para carregar dados da AIPD selecionada
        // e preencher automaticamente alguns campos
        console.log('AIPD selecionada:', aipdId);
    }
});

// Validação de campos obrigatórios
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = ['aipd_id', 'titulo', 'elaborador_id', 'conclusao_geral', 'recomendacoes_finais'];
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
</script>
