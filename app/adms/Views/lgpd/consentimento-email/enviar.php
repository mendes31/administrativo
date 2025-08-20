<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-envelope"></i> 
        Enviar Formulário de Consentimento
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>lgpd">LGPD</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos">Consentimentos</a></li>
        <li class="breadcrumb-item active">Enviar por E-mail</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-envelope me-1"></i>
                    Enviar Formulário de Consentimento por E-mail
                </div>
                <div class="card-body">
                    
                    <?php if (isset($_SESSION['sucesso'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $_SESSION['sucesso']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['sucesso']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['erro'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $_SESSION['erro']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['erro']); ?>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-email-processar" id="emailForm">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">
                                            Nome do Titular <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nome" name="nome" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">
                                            E-mail do Titular <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="finalidade" class="form-label">
                                        Finalidade do Tratamento <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="finalidade" name="finalidade" required>
                                        <option value="">Selecione a finalidade...</option>
                                        <option value="marketing">Marketing e Comunicação</option>
                                        <option value="servicos">Prestação de Serviços</option>
                                        <option value="analytics">Análise e Estatísticas</option>
                                        <option value="seguranca">Segurança e Prevenção</option>
                                        <option value="compliance">Conformidade Legal</option>
                                        <option value="rh">Recursos Humanos</option>
                                        <option value="financeiro">Financeiro</option>
                                        <option value="outros">Outros</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="finalidade_outros" class="form-label" id="label_outros" style="display: none;">
                                        Especifique a Finalidade <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="finalidade_outros" name="finalidade_outros" 
                                              rows="3" style="display: none;" placeholder="Descreva a finalidade específica..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mensagem" class="form-label">
                                        Mensagem Adicional (Opcional)
                                    </label>
                                    <textarea class="form-control" id="mensagem" name="mensagem" rows="4" 
                                              placeholder="Adicione uma mensagem personalizada para o titular..."></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-secondary me-md-2" onclick="limparFormulario()">
                                        <i class="fas fa-eraser"></i> Limpar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Enviar Formulário
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <i class="fas fa-info-circle"></i> Informações
                                </div>
                                <div class="card-body">
                                    <h6>Como funciona:</h6>
                                    <ol class="small">
                                        <li>Preencha os dados do titular</li>
                                        <li>Selecione a finalidade do tratamento</li>
                                        <li>Clique em "Enviar Formulário"</li>
                                        <li>O titular receberá um e-mail com link único</li>
                                        <li>O link é válido por 7 dias</li>
                                    </ol>
                                    
                                    <hr>
                                    
                                    <h6>Vantagens:</h6>
                                    <ul class="small">
                                        <li>✅ Link único e seguro</li>
                                        <li>✅ Validade de 7 dias</li>
                                        <li>✅ Rastreamento completo</li>
                                        <li>✅ Conforme LGPD</li>
                                        <li>✅ E-mail personalizado</li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Template do E-mail:</h6>
                                    <ul class="small">
                                        <li>📧 Assunto personalizado</li>
                                        <li>🔗 Link direto para o formulário</li>
                                        <li>📋 Instruções claras</li>
                                        <li>⚖️ Informações sobre direitos</li>
                                        <li>🛡️ Dados de contato</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Histórico de Envios -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Histórico de Envios Recentes
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="historicoTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Finalidade</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-inbox"></i> Nenhum envio registrado ainda
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar campo de finalidade específica
document.getElementById('finalidade').addEventListener('change', function() {
    const outrosField = document.getElementById('finalidade_outros');
    const outrosLabel = document.getElementById('label_outros');
    
    if (this.value === 'outros') {
        outrosField.style.display = 'block';
        outrosLabel.style.display = 'block';
        outrosField.required = true;
    } else {
        outrosField.style.display = 'none';
        outrosLabel.style.display = 'none';
        outrosField.required = false;
    }
});

// Validação do formulário
document.getElementById('emailForm').addEventListener('submit', function(e) {
    console.log('Formulário submetido!');
    
    const finalidade = document.getElementById('finalidade').value;
    const finalidadeOutros = document.getElementById('finalidade_outros');
    
    if (finalidade === 'outros' && finalidadeOutros.value.trim() === '') {
        e.preventDefault();
        alert('Por favor, especifique a finalidade do tratamento.');
        finalidadeOutros.focus();
        return false;
    }
    
    // Confirmar envio
    if (!confirm('Tem certeza que deseja enviar o formulário de consentimento?')) {
        e.preventDefault();
        return false;
    }
    
    console.log('Enviando formulário...');
});

// Limpar formulário
function limparFormulario() {
    if (confirm('Tem certeza que deseja limpar o formulário?')) {
        document.getElementById('emailForm').reset();
        document.getElementById('finalidade_outros').style.display = 'none';
        document.getElementById('label_outros').style.display = 'none';
    }
}

// Máscara para nome (apenas letras e espaços)
document.getElementById('nome').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
});
</script>
