<?php
use App\adms\Helpers\CSRFHelper;

$template = $this->data['template'];
$departamentos = $this->data['departamentos'];
$usuarios = $this->data['usuarios'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-users text-primary"></i>
            Template TIA - Recursos Humanos
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="text-decoration-none">TIA</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-templates" class="text-decoration-none">Templates</a>
            </li>
            <li class="breadcrumb-item">RH</li>
        </ol>
    </div>

    <!-- Informações do Template -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Sobre o Template RH
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Este template é específico para atividades de Recursos Humanos e inclui campos e critérios 
                        relevantes para o setor. Use-o como base para criar seus testes TIA relacionados a RH.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário do Template -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Aplicar Template RH
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" method="POST">
                        <?php CSRFHelper::generateCSRFToken('tia_create'); ?>
                        
                        <!-- Campos pré-preenchidos do template -->
                        <input type="hidden" name="template_source" value="rh">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codigo" class="form-label">Código do Teste</label>
                                    <input type="text" class="form-control" id="codigo" name="codigo" 
                                           value="<?php echo htmlspecialchars($template['codigo'] ?? ''); ?>" required>
                                    <div class="form-text">Código único para identificação do teste</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="departamento_id" class="form-label">Departamento</label>
                                    <select class="form-select" id="departamento_id" name="departamento_id" required>
                                        <option value="">Selecione o departamento</option>
                                        <?php foreach ($departamentos as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo ($template['departamento_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do Teste</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($template['titulo'] ?? ''); ?>" required>
                            <div class="form-text">Título descritivo do teste de impacto</div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($template['descricao'] ?? ''); ?></textarea>
                            <div class="form-text">Descrição detalhada da atividade a ser testada</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data_teste" class="form-label">Data do Teste</label>
                                    <input type="date" class="form-control" id="data_teste" name="data_teste" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="responsavel_id" class="form-label">Responsável</label>
                                    <select class="form-select" id="responsavel_id" name="responsavel_id" required>
                                        <option value="">Selecione o responsável</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo $usuario['id']; ?>" 
                                                    <?php echo ($template['responsavel_id'] ?? '') == $usuario['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($usuario['name']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Selecione o usuário responsável pelo teste</div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos específicos do template RH -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_atividade_rh" class="form-label">Tipo de Atividade RH</label>
                                    <select class="form-select" id="tipo_atividade_rh" name="tipo_atividade_rh">
                                        <option value="">Selecione o tipo</option>
                                        <option value="Recrutamento e Seleção">Recrutamento e Seleção</option>
                                        <option value="Gestão de Performance">Gestão de Performance</option>
                                        <option value="Treinamento e Desenvolvimento">Treinamento e Desenvolvimento</option>
                                        <option value="Folha de Pagamento">Folha de Pagamento</option>
                                        <option value="Benefícios">Benefícios</option>
                                        <option value="Saúde e Segurança">Saúde e Segurança</option>
                                        <option value="Relacionamento Trabalhista">Relacionamento Trabalhista</option>
                                        <option value="Outros">Outros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="volume_funcionarios" class="form-label">Volume de Funcionários</label>
                                    <select class="form-select" id="volume_funcionarios" name="volume_funcionarios">
                                        <option value="">Selecione o volume</option>
                                        <option value="Baixo">Baixo (1-50)</option>
                                        <option value="Médio">Médio (51-200)</option>
                                        <option value="Alto">Alto (201-1000)</option>
                                        <option value="Muito Alto">Muito Alto (1000+)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações Específicas</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($template['observacoes'] ?? ''); ?></textarea>
                            <div class="form-text">Observações específicas para este teste</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-templates" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar aos Templates
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Aplicar Template e Criar TIA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
