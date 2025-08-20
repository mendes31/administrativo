<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-shopping-cart text-success"></i>
            AIPD - E-commerce
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Template E-commerce</li>
        </ol>
    </div>

    <div class="alert alert-info">
        <h6><i class="fas fa-info-circle"></i> Template Específico para E-commerce</h6>
        <p class="mb-0">
            <strong>Use este template para atividades que envolvem vendas online, marketplaces, 
            plataformas de e-commerce, etc.</strong><br>
            <strong>Exemplos práticos:</strong> Loja virtual própria, marketplace, dropshipping, 
            programa de afiliados, social commerce, mobile commerce, plataformas de pagamento.
        </p>
    </div>

    <div class="alert alert-warning">
        <h6><i class="fas fa-exclamation-triangle"></i> Importante: Vínculo com ROPA</h6>
        <p class="mb-0">
            <strong>A AIPD deve estar vinculada a uma ROPA específica.</strong> A lista abaixo mostra ROPAs relevantes para e-commerce. 
            Se não encontrar uma ROPA adequada, você pode criar uma nova ROPA primeiro.
        </p>
    </div>

    <div class="card mb-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-shield-alt"></i>
                Avaliação de Impacto à Proteção de Dados - E-commerce
            </h5>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_template_ecommerce'); ?>">

                <!-- Campos Específicos do E-commerce -->
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Informações Específicas do E-commerce</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="tipo_plataforma" class="form-label">
                                        <?php echo $this->data['template_ecommerce']['campos_especificos']['tipo_plataforma']['label']; ?> *
                                    </label>
                                    <select name="tipo_plataforma" class="form-select" id="tipo_plataforma" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($this->data['template_ecommerce']['campos_especificos']['tipo_plataforma']['options'] as $valor => $texto): ?>
                                            <option value="<?php echo $valor; ?>" 
                                                    <?php echo (isset($this->data['form']['tipo_plataforma']) && $this->data['form']['tipo_plataforma'] === $valor) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($texto); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="categoria_produtos" class="form-label">
                                        <?php echo $this->data['template_ecommerce']['campos_especificos']['categoria_produtos']['label']; ?>
                                    </label>
                                    <select name="categoria_produtos" class="form-select" id="categoria_produtos">
                                        <option value="">Selecione</option>
                                        <?php foreach ($this->data['template_ecommerce']['campos_especificos']['categoria_produtos']['options'] as $valor => $texto): ?>
                                            <option value="<?php echo $valor; ?>" 
                                                    <?php echo (isset($this->data['form']['categoria_produtos']) && $this->data['form']['categoria_produtos'] === $valor) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($texto); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="form-label">
                                        <?php echo $this->data['template_ecommerce']['campos_especificos']['dados_tratados']['label']; ?> *
                                    </label>
                                    <div class="row">
                                        <?php foreach ($this->data['template_ecommerce']['campos_especificos']['dados_tratados']['options'] as $valor => $texto): ?>
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="dados_tratados[]" 
                                                           value="<?php echo $valor; ?>" 
                                                           id="dados_<?php echo $valor; ?>"
                                                           <?php echo (isset($this->data['form']['dados_tratados']) && is_array($this->data['form']['dados_tratados']) && in_array($valor, $this->data['form']['dados_tratados'])) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="dados_<?php echo $valor; ?>">
                                                        <?php echo htmlspecialchars($texto); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="base_legal" class="form-label">
                                        <?php echo $this->data['template_ecommerce']['campos_especificos']['base_legal']['label']; ?> *
                                    </label>
                                    <select name="base_legal" class="form-select" id="base_legal" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($this->data['template_ecommerce']['campos_especificos']['base_legal']['options'] as $valor => $texto): ?>
                                            <option value="<?php echo $valor; ?>" 
                                                    <?php echo (isset($this->data['form']['base_legal']) && $this->data['form']['base_legal'] === $valor) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($texto); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campos Padrão da AIPD -->
                <div class="col-12">
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Informações Gerais da AIPD</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label for="titulo" class="form-label">Título da AIPD *</label>
                                    <input type="text" name="titulo" class="form-control" id="titulo" 
                                           value="<?php echo $this->data['form']['titulo'] ?? $this->data['template_ecommerce']['titulo_padrao']; ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="departamento_id" class="form-label">Departamento *</label>
                                    <select name="departamento_id" class="form-select" id="departamento_id" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($this->data['departamentos'] as $departamento): ?>
                                            <?php extract($departamento); ?>
                                            <?php $selected = isset($this->data['form']['departamento_id']) && $this->data['form']['departamento_id'] == $id ? 'selected' : ''; ?>
                                            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="ropa_id" class="form-label">ROPA *</label>
                                    <div class="input-group">
                                        <select name="ropa_id" class="form-select" id="ropa_id" required>
                                            <option value="">Selecione</option>
                                            <?php foreach ($this->data['ropas'] as $ropa): ?>
                                                <?php extract($ropa); ?>
                                                <?php $selected = isset($this->data['form']['ropa_id']) && $this->data['form']['ropa_id'] == $id ? 'selected' : ''; ?>
                                                <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $codigo . ' - ' . $atividade; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-create" class="btn btn-outline-secondary" target="_blank">
                                            <i class="fas fa-plus"></i> Nova ROPA
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="responsavel_id" class="form-label">Responsável *</label>
                                    <select name="responsavel_id" class="form-select" id="responsavel_id" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($this->data['usuarios'] as $usuario): ?>
                                            <?php extract($usuario); ?>
                                            <?php $selected = isset($this->data['form']['responsavel_id']) && $this->data['form']['responsavel_id'] == $id ? 'selected' : ''; ?>
                                            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="data_inicio" class="form-label">Data de Início *</label>
                                    <input type="date" name="data_inicio" class="form-control" id="data_inicio" 
                                           value="<?php echo $this->data['form']['data_inicio'] ?? date('Y-m-d'); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="nivel_risco" class="form-label">Nível de Risco *</label>
                                    <select name="nivel_risco" class="form-select" id="nivel_risco" required>
                                        <option value="">Selecione</option>
                                        <option value="Baixo" <?php echo (isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Baixo') ? 'selected' : ''; ?>>Baixo</option>
                                        <option value="Médio" <?php echo (isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Médio') ? 'selected' : ''; ?>>Médio</option>
                                        <option value="Alto" <?php echo (isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Alto') ? 'selected' : ''; ?> selected>Alto</option>
                                        <option value="Crítico" <?php echo (isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Crítico') ? 'selected' : ''; ?>>Crítico</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label for="objetivo" class="form-label">Objetivo da AIPD *</label>
                                    <textarea name="objetivo" class="form-control" id="objetivo" rows="3" required><?php echo $this->data['form']['objetivo'] ?? $this->data['template_ecommerce']['objetivo_padrao']; ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label for="escopo" class="form-label">Escopo da AIPD *</label>
                                    <textarea name="escopo" class="form-control" id="escopo" rows="3" required><?php echo $this->data['form']['escopo'] ?? $this->data['template_ecommerce']['escopo_padrao']; ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label for="metodologia" class="form-label">Metodologia</label>
                                    <textarea name="metodologia" class="form-control" id="metodologia" rows="3"><?php echo $this->data['form']['metodologia'] ?? $this->data['template_ecommerce']['metodologia_padrao']; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riscos e Medidas Pré-definidas -->
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">Riscos Identificados e Medidas de Mitigação</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="riscos_identificados" class="form-label">Riscos Identificados</label>
                                    <textarea name="riscos_identificados" class="form-control" id="riscos_identificados" rows="6"><?php 
                                        if (isset($this->data['form']['riscos_identificados']) && !empty($this->data['form']['riscos_identificados'])) {
                                            echo htmlspecialchars($this->data['form']['riscos_identificados']);
                                        } else {
                                            echo implode("\n• ", array_merge([''], $this->data['template_ecommerce']['riscos_identificados']));
                                        }
                                    ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="medidas_mitigacao" class="form-label">Medidas de Mitigação</label>
                                    <textarea name="medidas_mitigacao" class="form-control" id="medidas_mitigacao" rows="6"><?php 
                                        if (isset($this->data['form']['medidas_mitigacao']) && !empty($this->data['form']['medidas_mitigacao'])) {
                                            echo htmlspecialchars($this->data['form']['medidas_mitigacao']);
                                        } else {
                                            echo implode("\n• ", array_merge([''], $this->data['template_ecommerce']['medidas_mitigacao']));
                                        }
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conclusões e Recomendações -->
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Conclusões e Recomendações</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label for="conclusoes" class="form-label">Conclusões</label>
                                    <textarea name="conclusoes" class="form-control" id="conclusoes" rows="3"><?php echo $this->data['form']['conclusoes'] ?? $this->data['template_ecommerce']['conclusoes_padrao']; ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label for="recomendacoes" class="form-label">Recomendações</label>
                                    <textarea name="recomendacoes" class="form-control" id="recomendacoes" rows="4"><?php 
                                        if (isset($this->data['form']['recomendacoes']) && !empty($this->data['form']['recomendacoes'])) {
                                            echo htmlspecialchars($this->data['form']['recomendacoes']);
                                        } else {
                                            echo implode("\n• ", array_merge([''], $this->data['template_ecommerce']['recomendacoes_padrao']));
                                        }
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar AIPD - E-commerce
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-selecionar nível de risco baseado nos dados tratados
document.addEventListener('DOMContentLoaded', function() {
    const dadosTratados = document.querySelectorAll('input[name="dados_tratados[]"]');
    const nivelRisco = document.getElementById('nivel_risco');
    
    dadosTratados.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const dadosCriticos = ['dados_biometricos'];
            const temDadosCriticos = dadosCriticos.some(tipo => 
                document.querySelector(`input[value="${tipo}"]`).checked
            );
            
            if (temDadosCriticos) {
                nivelRisco.value = 'Crítico';
            } else if (document.querySelectorAll('input[name="dados_tratados[]"]:checked').length > 0) {
                nivelRisco.value = 'Alto';
            }
        });
    });
});
</script>
