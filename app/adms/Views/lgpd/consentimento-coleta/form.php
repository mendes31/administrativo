<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dados['title_head']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .consent-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin: 50px auto;
            max-width: 600px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .form-check {
            margin-bottom: 15px;
        }
        
        .form-check-input:checked {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-box h5 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #7f8c8d;
            margin-bottom: 0;
        }
        
        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="consent-form">
            <div class="form-header">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h1>Autorização de Tratamento de Dados</h1>
                <p>Conforme a Lei Geral de Proteção de Dados (LGPD)</p>
            </div>
            
            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['sucesso']; ?>
                    <?php unset($_SESSION['sucesso']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $_SESSION['erro']; ?>
                    <?php unset($_SESSION['erro']); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h5><i class="fas fa-info-circle"></i> Informações Importantes</h5>
                <p>
                    Este formulário coleta seu consentimento para o tratamento de dados pessoais. 
                    Você tem o direito de revogar este consentimento a qualquer momento. 
                    Para mais informações, consulte nossa <a href="/politica-privacidade" target="_blank">Política de Privacidade</a>.
                </p>
            </div>
            
            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-coleta-processar" id="consentForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nome" class="form-label">
                                Nome Completo <span class="required">*</span>
                            </label>
                                                         <input type="text" class="form-control" id="nome" name="nome" 
                                    value="<?php echo htmlspecialchars($dados['dados_preenchidos']['nome'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                E-mail <span class="required">*</span>
                            </label>
                                                         <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($dados['dados_preenchidos']['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="finalidade" class="form-label">
                        Finalidade do Tratamento <span class="required">*</span>
                    </label>
                    <select class="form-control" id="finalidade" name="finalidade" required>
                        <option value="">Selecione a finalidade...</option>
                                                 <option value="marketing" <?php echo ($dados['dados_preenchidos']['finalidade'] ?? '') === 'marketing' ? 'selected' : ''; ?>>Marketing e Comunicação</option>
                         <option value="servicos" <?php echo ($dados['dados_preenchidos']['finalidade'] ?? '') === 'servicos' ? 'selected' : ''; ?>>Prestação de Serviços</option>
                         <option value="analytics" <?php echo ($dados['dados_preenchidos']['finalidade'] ?? '') === 'analytics' ? 'selected' : ''; ?>>Análise e Estatísticas</option>
                         <option value="seguranca" <?php echo ($dados['dados_preenchidos']['finalidade'] ?? '') === 'seguranca' ? 'selected' : ''; ?>>Segurança e Prevenção</option>
                        <option value="compliance">Conformidade Legal</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="finalidade_outros" class="form-label" id="label_outros" style="display: none;">
                        Especifique a Finalidade <span class="required">*</span>
                    </label>
                    <textarea class="form-control" id="finalidade_outros" name="finalidade_outros" 
                              rows="3" style="display: none;" placeholder="Descreva a finalidade específica..."></textarea>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="politica_privacidade" name="politica_privacidade" required>
                        <label class="form-check-label" for="politica_privacidade">
                            Li e aceito a <a href="/politica-privacidade" target="_blank">Política de Privacidade</a> <span class="required">*</span>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termos_aceitos" name="termos_aceitos">
                        <label class="form-check-label" for="termos_aceitos">
                            Aceito receber comunicações por e-mail sobre produtos e serviços
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cookies" name="cookies">
                        <label class="form-check-label" for="cookies">
                            Aceito o uso de cookies para melhorar minha experiência
                        </label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Autorizar Tratamento de Dados
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-lock"></i> 
                    Seus dados são protegidos e tratados conforme a LGPD
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
        document.getElementById('consentForm').addEventListener('submit', function(e) {
            const finalidade = document.getElementById('finalidade').value;
            const finalidadeOutros = document.getElementById('finalidade_outros');
            
            if (finalidade === 'outros' && finalidadeOutros.value.trim() === '') {
                e.preventDefault();
                alert('Por favor, especifique a finalidade do tratamento.');
                finalidadeOutros.focus();
                return false;
            }
            
            if (!document.getElementById('politica_privacidade').checked) {
                e.preventDefault();
                alert('É obrigatório aceitar a Política de Privacidade.');
                return false;
            }
        });
        
        // Máscara para nome (apenas letras e espaços)
        document.getElementById('nome').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
        });
    </script>
</body>
</html>
