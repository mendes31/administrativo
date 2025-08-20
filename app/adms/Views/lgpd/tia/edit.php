<?php
use App\adms\Helpers\CSRFHelper;

$tia = $this->data['form'];
$data_groups = $this->data['data_groups'];
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-edit text-warning"></i>
            Editar Teste de Impacto às Atividades (TIA): <?php echo htmlspecialchars($tia['codigo']); ?>
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
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-check"></i>
                Editar Teste TIA
            </h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_tia_edit'); ?>">

                <!-- Informações Básicas -->
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Informações Básicas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="titulo" class="form-label">
                                        Título do Teste <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="titulo" 
                                           name="titulo" 
                                           value="<?php echo htmlspecialchars($tia['titulo'] ?? ''); ?>"
                                           placeholder="Ex: Teste de impacto para sistema de RH"
                                           required>
                                    <div class="form-text">
                                        Descreva de forma clara o objetivo do teste.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="departamento_id" class="form-label">
                                        Departamento Responsável <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="departamento_id" name="departamento_id" required>
                                        <option value="">Selecione o departamento</option>
                                        <?php foreach ($this->data['departamentos'] as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo (isset($tia['departamento_id']) && $tia['departamento_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <label for="descricao" class="form-label">
                                        Descrição da Atividade
                                    </label>
                                    <textarea class="form-control" 
                                              id="descricao" 
                                              name="descricao" 
                                              rows="3" 
                                              placeholder="Descreva detalhadamente a atividade que será testada"><?php echo htmlspecialchars($tia['descricao'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Explique o que a atividade faz, quais dados processa e como funciona.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vinculação com ROPA -->
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Vinculação com ROPA (Opcional)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="ropa_id" class="form-label">
                                        ROPA Relacionada
                                    </label>
                                    <select class="form-select" id="ropa_id" name="ropa_id">
                                        <option value="">Selecione uma ROPA (opcional)</option>
                                        <?php foreach ($this->data['ropas'] as $ropa): ?>
                                            <option value="<?php echo $ropa['id']; ?>" 
                                                    <?php echo (isset($tia['ropa_id']) && $tia['ropa_id'] == $ropa['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ropa['codigo'] . ' - ' . $ropa['atividade']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        Se este teste estiver relacionado a uma operação de tratamento já cadastrada.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="responsavel_id" class="form-label">
                                        Responsável pelo Teste
                                    </label>
                                    <select class="form-select" id="responsavel_id" name="responsavel_id">
                                        <option value="">Selecione o responsável</option>
                                        <?php foreach ($this->data['usuarios'] as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" 
                                                    <?php echo (isset($tia['responsavel_id']) && $tia['responsavel_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalhes do Teste -->
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">Detalhes do Teste</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="data_teste" class="form-label">
                                        Data do Teste <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="data_teste" 
                                           name="data_teste" 
                                           value="<?php echo $tia['data_teste'] ?? date('Y-m-d'); ?>"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="resultado" class="form-label">
                                        Resultado do Teste <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="resultado" name="resultado" required>
                                        <option value="">Selecione o resultado</option>
                                        <option value="Baixo Risco" <?php echo (isset($tia['resultado']) && $tia['resultado'] === 'Baixo Risco') ? 'selected' : ''; ?>>Baixo Risco</option>
                                        <option value="Médio Risco" <?php echo (isset($tia['resultado']) && $tia['resultado'] === 'Médio Risco') ? 'selected' : ''; ?>>Médio Risco</option>
                                        <option value="Alto Risco" <?php echo (isset($tia['resultado']) && $tia['resultado'] === 'Alto Risco') ? 'selected' : ''; ?>>Alto Risco</option>
                                        <option value="Necessita AIPD" <?php echo (isset($tia['resultado']) && $tia['resultado'] === 'Necessita AIPD') ? 'selected' : ''; ?>>Necessita AIPD</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">Selecione o status</option>
                                        <option value="Em Andamento" <?php echo (isset($tia['status']) && $tia['status'] === 'Em Andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                                        <option value="Concluído" <?php echo (isset($tia['status']) && $tia['status'] === 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                        <option value="Aprovado" <?php echo (isset($tia['status']) && $tia['status'] === 'Aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="justificativa" class="form-label">
                                        Justificativa do Resultado
                                    </label>
                                    <textarea class="form-control" 
                                              id="justificativa" 
                                              name="justificativa" 
                                              rows="3" 
                                              placeholder="Explique o motivo do resultado obtido"><?php echo htmlspecialchars($tia['justificativa'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Descreva os fatores que levaram a este resultado.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="recomendacoes" class="form-label">
                                        Recomendações
                                    </label>
                                    <textarea class="form-control" 
                                              id="recomendacoes" 
                                              name="recomendacoes" 
                                              rows="3" 
                                              placeholder="Quais ações devem ser tomadas"><?php echo htmlspecialchars($tia['recomendacoes'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Sugira medidas para mitigar riscos ou melhorar a atividade.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grupos de Dados -->
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Grupos de Dados Processados</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <p class="text-muted mb-3">
                                        Selecione os grupos de dados que serão processados por esta atividade:
                                    </p>
                                    
                                    <div class="row" id="data-groups-container">
                                        <?php if (!empty($data_groups)): ?>
                                            <?php foreach ($data_groups as $index => $group): ?>
                                                <div class="row mb-3">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Grupo de Dados</label>
                                                        <select class="form-select" name="data_groups[<?php echo $index; ?>][data_group_id]">
                                                            <option value="">Selecione um grupo</option>
                                                            <?php foreach ($this->data['todos_data_groups'] as $all_group): ?>
                                                                <option value="<?php echo $all_group['id']; ?>" 
                                                                        <?php echo $group['data_group_id'] == $all_group['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($all_group['name']); ?>
                                                                    <?php if ($all_group['is_sensitive']): ?>
                                                                        <span class="text-danger">(Sensível)</span>
                                                                    <?php endif; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Volume</label>
                                                        <select class="form-select" name="data_groups[<?php echo $index; ?>][volume_dados]">
                                                            <option value="Baixo" <?php echo $group['volume_dados'] === 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                                                            <option value="Médio" <?php echo $group['volume_dados'] === 'Médio' ? 'selected' : ''; ?>>Médio</option>
                                                            <option value="Alto" <?php echo $group['volume_dados'] === 'Alto' ? 'selected' : ''; ?>>Alto</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label">Sensibilidade</label>
                                                        <select class="form-select" name="data_groups[<?php echo $index; ?>][sensibilidade]">
                                                            <option value="Baixa" <?php echo $group['sensibilidade'] === 'Baixa' ? 'selected' : ''; ?>>Baixa</option>
                                                            <option value="Média" <?php echo $group['sensibilidade'] === 'Média' ? 'selected' : ''; ?>>Média</option>
                                                            <option value="Alta" <?php echo $group['sensibilidade'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 mb-3">
                                                        <label class="form-label">&nbsp;</label>
                                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeDataGroup(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="row mb-3">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Grupo de Dados</label>
                                                    <select class="form-select" name="data_groups[0][data_group_id]">
                                                        <option value="">Selecione um grupo</option>
                                                        <?php foreach ($this->data['todos_data_groups'] as $group): ?>
                                                            <option value="<?php echo $group['id']; ?>">
                                                                <?php echo htmlspecialchars($group['name']); ?>
                                                                <?php if ($group['is_sensitive']): ?>
                                                                    <span class="text-danger">(Sensível)</span>
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Volume</label>
                                                    <select class="form-select" name="data_groups[0][volume_dados]">
                                                        <option value="Baixo">Baixo</option>
                                                        <option value="Médio" selected>Médio</option>
                                                        <option value="Alto">Alto</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Sensibilidade</label>
                                                    <select class="form-select" name="data_groups[0][sensibilidade]">
                                                        <option value="Baixa">Baixa</option>
                                                        <option value="Média" selected>Média</option>
                                                        <option value="Alta">Alta</option>
                                                    </select>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addDataGroup()">
                                        <i class="fas fa-plus me-2"></i>Adicionar Grupo de Dados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-view/<?php echo $tia['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Atualizar Teste TIA
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let dataGroupCounter = <?php echo !empty($data_groups) ? count($data_groups) : 1; ?>;

function addDataGroup() {
    const container = document.getElementById('data-groups-container');
    const newGroup = document.createElement('div');
    newGroup.className = 'row mb-3';
    newGroup.innerHTML = `
        <div class="col-md-6 mb-3">
            <label class="form-label">Grupo de Dados</label>
            <select class="form-select" name="data_groups[${dataGroupCounter}][data_group_id]">
                <option value="">Selecione um grupo</option>
                <?php foreach ($this->data['todos_data_groups'] as $group): ?>
                    <option value="<?php echo $group['id']; ?>">
                        <?php echo htmlspecialchars($group['name']); ?>
                        <?php if ($group['is_sensitive']): ?>
                            <span class="text-danger">(Sensível)</span>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Volume</label>
            <select class="form-select" name="data_groups[${dataGroupCounter}][volume_dados]">
                <option value="Baixo">Baixo</option>
                <option value="Médio" selected>Médio</option>
                <option value="Alto">Alto</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Sensibilidade</label>
            <select class="form-select" name="data_groups[${dataGroupCounter}][sensibilidade]">
                <option value="Baixa">Baixa</option>
                <option value="Média" selected>Média</option>
                <option value="Alta">Alta</option>
            </select>
        </div>
        <div class="col-md-1 mb-3">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeDataGroup(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newGroup);
    dataGroupCounter++;
}

function removeDataGroup(button) {
    button.closest('.row').remove();
}
</script>
