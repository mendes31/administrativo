<?php
use App\adms\Helpers\CSRFHelper;

$template = $this->data['template'];
$departamentos = $this->data['departamentos'];
$usuarios = $this->data['usuarios'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-chart-line text-warning"></i>
            Template TIA - Financeiro
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
            <li class="breadcrumb-item">Financeiro</li>
        </ol>
    </div>

    <!-- Informações do Template -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Sobre o Template Financeiro
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Este template é específico para atividades financeiras e inclui campos e critérios 
                        relevantes para operações bancárias, contabilidade e gestão financeira.
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
                        Aplicar Template Financeiro
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" method="POST">
                        <?php CSRFHelper::generateCSRFToken('tia_create'); ?>
                        
                        <!-- Campos pré-preenchidos do template -->
                        <input type="hidden" name="template_source" value="financeiro">
                        
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

                        <!-- Campos específicos do template Financeiro -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_operacao" class="form-label">Tipo de Operação</label>
                                    <select class="form-select" id="tipo_operacao" name="tipo_operacao">
                                        <option value="">Selecione o tipo</option>
                                        <option value="Operações Bancárias">Operações Bancárias</option>
                                        <option value="Contabilidade">Contabilidade</option>
                                        <option value="Gestão de Caixa">Gestão de Caixa</option>
                                        <option value="Contas a Pagar">Contas a Pagar</option>
                                        <option value="Contas a Receber">Contas a Receber</option>
                                        <option value="Relatórios Financeiros">Relatórios Financeiros</option>
                                        <option value="Auditoria">Auditoria</option>
                                        <option value="Outros">Outros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="volume_transacoes" class="form-label">Volume de Transações</label>
                                    <select class="form-select" id="volume_transacoes" name="volume_transacoes">
                                        <option value="">Selecione o volume</option>
                                        <option value="Baixo">Baixo (1-100/mês)</option>
                                        <option value="Médio">Médio (101-1000/mês)</option>
                                        <option value="Alto">Alto (1001-10000/mês)</option>
                                        <option value="Muito Alto">Muito Alto (10000+/mês)</option>
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
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Aplicar Template e Criar TIA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
