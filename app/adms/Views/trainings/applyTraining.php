<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Registrar Aplicação de Treinamento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="text-decoration-none">Status de Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Registrar Aplicação</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>Registrar Aplicação
                    </h5>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    
                    <?php
                    $tipoRegistro = $_GET['tipo_registro'] ?? $_POST['tipo_registro'] ?? null;
                    $editando = !empty($this->data['edit_id']);
                    if ($editando) {
                        if (!empty($this->data['trainingUser']['data_realizacao'])) {
                            $tipoRegistro = 'realizacao';
                        } elseif (!empty($this->data['trainingUser']['data_agendada'])) {
                            $tipoRegistro = 'agendamento';
                        }
                        if (isset($_GET['tipo_registro']) && $_GET['tipo_registro'] !== $tipoRegistro) {
                            header('Location: ' . $_ENV['URL_ADM'] . 'list-training-status');
                            exit;
                        }
                    } else {
                        if ($tipoRegistro !== 'realizacao' && $tipoRegistro !== 'agendamento') {
                            $tipoRegistro = 'realizacao';
                        }
                    }
                    $exigeReciclagem = !empty($this->data['training']['reciclagem']) && !empty($this->data['training']['reciclagem_periodo']);
                    $aplicacaoAntecipada = false;
                    if ($exigeReciclagem && !empty($this->data['trainingUser']['data_realizacao'])) {
                        $dataRealizacao = new DateTime($this->data['trainingUser']['data_realizacao']);
                        $dataVencimento = clone $dataRealizacao;
                        $dataVencimento->add(new DateInterval('P' . $this->data['training']['reciclagem_periodo'] . 'M'));
                        $hoje = new DateTime();
                        $aplicacaoAntecipada = ($hoje < $dataVencimento);
                    }
                    $inicioCiclo = $_POST['inicio_ciclo'] ?? $_GET['inicio_ciclo'] ?? 'aplicacao';
                    ?>

                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>apply-training">
                        <input type="hidden" name="training_id" value="<?php echo $this->data['training_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $this->data['user_id']; ?>">
<?php if (!empty($this->data['edit_id'])): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $this->data['edit_id']; ?>">
<?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><strong>Tipo de Registro *</strong></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_registro" id="radio_realizacao" value="realizacao" <?= $tipoRegistro === 'realizacao' ? 'checked' : '' ?> disabled>
                                    <label class="form-check-label" for="radio_realizacao">Registrar Realização</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_registro" id="radio_agendamento" value="agendamento" <?= $tipoRegistro === 'agendamento' ? 'checked' : '' ?> disabled>
                                    <label class="form-check-label" for="radio_agendamento">Agendar Treinamento</label>
                                </div>
                                <input type="hidden" name="tipo_registro" value="<?= $tipoRegistro ?>">
                            </div>
                        </div>

                        <div class="row mb-3" id="div_realizacao">
                            <div class="col-md-4">
                                <label for="data_realizacao" class="form-label">
                                    <strong>Data de Realização *</strong>
                                </label>
                                <input type="date" 
                                       name="data_realizacao" 
                                       class="form-control" 
                                       id="data_realizacao" 
                                       value="<?php echo $this->data['trainingUser']['data_realizacao'] ?? date('Y-m-d'); ?>"
                                       min="<?php echo $this->data['trainingUser']['created_at'] ? date('Y-m-d', strtotime($this->data['trainingUser']['created_at'])) : ''; ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text">Data em que o treinamento foi realizado</div>
                            </div>
                            <div class="col-md-4">
                                <label for="data_avaliacao" class="form-label">
                                    <strong>Data de Avaliação</strong>
                                </label>
                                <input type="date" 
                                       name="data_avaliacao" 
                                       class="form-control" 
                                       id="data_avaliacao" 
                                       value="<?php echo $this->data['trainingUser']['data_avaliacao'] ?? ''; ?>"
                                       min="<?php echo $this->data['trainingUser']['data_realizacao'] ?? ''; ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text">Data em que a avaliação do treinamento foi realizada</div>
                            </div>
                            <div class="col-md-4">
                                <label for="nota" class="form-label">
                                    <strong>Nota</strong>
                                </label>
                                <input type="number" 
                                       name="nota" 
                                       class="form-control" 
                                       id="nota" 
                                       min="0" 
                                       max="10" 
                                       step="0.1"
                                       value="<?php echo $this->data['trainingUser']['nota'] ?? ''; ?>"
                                       placeholder="0.0 a 10.0"
                                       required>
                                <div class="form-text">Nota de 0 a 10 (opcional)</div>
                            </div>
                        </div>

                        <div class="row mb-3" id="div_agendamento" style="display:none;">
                            <div class="col-md-6">
                                <label for="data_agendada" class="form-label">
                                    <strong>Data Agendada *</strong>
                                </label>
                                <input type="date" 
                                       name="data_agendada" 
                                       class="form-control" 
                                       id="data_agendada" 
                                       value="<?php echo $this->data['trainingUser']['data_agendada'] ?? ''; ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text">Data em que o treinamento está agendado para ocorrer</div>
                            </div>
                        </div>

                        <!-- Campos do Instrutor -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="instructor_type" class="form-label">
                                    <strong>Tipo de Instrutor *</strong>
                                </label>
                                <select name="instructor_type" id="instructor_type" class="form-select" onchange="toggleInstructorFields()" required>
                                    <option value="">Selecione...</option>
                                    <option value="internal" <?php echo (isset($this->data['form_instructor_type']) && $this->data['form_instructor_type'] == 'internal') ? 'selected' : ''; ?>>Colaborador Interno</option>
                                    <option value="external" <?php echo (isset($this->data['form_instructor_type']) && $this->data['form_instructor_type'] == 'external') ? 'selected' : ''; ?>>Instrutor Externo</option>
                                </select>
                                <div class="form-text">Selecione o tipo de instrutor (obrigatório)</div>
                            </div>
                            <div class="col-md-3" id="instructor_user_div" style="display: none;">
                                <label for="instructor_user_id" class="form-label">
                                    <strong>Instrutor (Colaborador Interno) *</strong>
                                </label>
                                <select name="instructor_user_id" id="instructor_user_id" class="form-select" onchange="fillInstructorEmail()" required>
                                    <option value="">Selecione um usuário...</option>
                                    <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                                <?php echo (isset($this->data['form_instructor_user_id']) && $this->data['form_instructor_user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Selecione o colaborador instrutor (obrigatório)</div>
                            </div>
                            <div class="col-md-3" id="instructor_name_div" style="display: none;">
                                <label for="instrutor_nome" class="form-label">
                                    <strong>Nome do Instrutor Externo *</strong>
                                </label>
                                <input type="text" 
                                       name="instrutor_nome" 
                                       class="form-control" 
                                       id="instrutor_nome" 
                                       value="<?php echo htmlspecialchars($this->data['form_instrutor_nome'] ?? ''); ?>"
                                       placeholder="Nome do instrutor externo"
                                       required>
                                <div class="form-text">Nome do instrutor externo (obrigatório)</div>
                            </div>
                            <div class="col-md-3" id="instructor_email_div" style="display: none;">
                                <label for="instrutor_email" class="form-label">
                                    <strong>E-mail do Instrutor *</strong>
                                </label>
                                <input type="email" 
                                       name="instrutor_email" 
                                       class="form-control" 
                                       id="instrutor_email" 
                                       value="<?php echo htmlspecialchars($this->data['form_instrutor_email'] ?? ''); ?>"
                                       placeholder="email@exemplo.com"
                                       required>
                                <div class="form-text">E-mail do instrutor (obrigatório)</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">
                                    <strong>Observações</strong>
                                </label>
                                <textarea name="observacoes" 
                                          class="form-control" 
                                          id="observacoes" 
                                          rows="4" 
                                          placeholder="Observações sobre a aplicação ou agendamento do treinamento..."><?php echo $this->data['trainingUser']['observacoes'] ?? ''; ?></textarea>
                                <div class="form-text">Observações adicionais sobre o treinamento</div>
                            </div>
                        </div>

                        <?php if ($exigeReciclagem && $tipoRegistro === 'realizacao'): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><strong>Início do Próximo Ciclo *</strong></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inicio_ciclo" id="ciclo_aplicacao" value="aplicacao" <?= $inicioCiclo === 'aplicacao' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ciclo_aplicacao">A partir da data de aplicação</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inicio_ciclo" id="ciclo_vencimento" value="vencimento" <?= $inicioCiclo === 'vencimento' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ciclo_vencimento">A partir da data prevista de vencimento anterior<?php if (isset($dataVencimento)) echo ' (' . $dataVencimento->format('d/m/Y') . ')'; ?></label>
                                </div>
                                <div class="form-text">Escolha como será calculado o início do próximo ciclo de reciclagem.</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Salvar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações do Treinamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Treinamento:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($this->data['training']['nome']); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Código:</strong><br>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($this->data['training']['codigo']); ?></span>
                    </div>
                    
                    <?php if (!empty($this->data['training']['versao'])): ?>
                    <div class="mb-3">
                        <strong>Versão:</strong><br>
                        <span class="badge bg-info"><?php echo htmlspecialchars($this->data['training']['versao']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($this->data['training']['reciclagem']) && !empty($this->data['training']['reciclagem_periodo'])): ?>
                    <div class="mb-3">
                        <strong>Reciclagem:</strong><br>
                        <span class="badge bg-warning text-dark">
                            <?php echo FormatHelper::formatReciclagemPeriodo((int)$this->data['training']['reciclagem_periodo']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>Informações do Colaborador
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Nome:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($this->data['user']['name']); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Email:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($this->data['user']['email']); ?></span>
                    </div>
                    
                    <?php if (!empty($this->data['trainingUser']['data_realizacao'])): ?>
                    <div class="mb-3">
                        <strong>Última Aplicação:</strong><br>
                        <span class="badge bg-info">
                            <?php echo (new DateTime($this->data['trainingUser']['data_realizacao']))->format('d/m/Y'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($this->data['trainingUser']['nota'])): ?>
                    <div class="mb-3">
                        <strong>Última Nota:</strong><br>
                        <span class="badge bg-success"><?php echo number_format($this->data['trainingUser']['nota'], 1); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Exibe apenas o bloco do tipo correto
const tipoRegistro = '<?= $tipoRegistro ?>';
// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    toggleInstructorFields();
    
    // Configurar inicialmente o min da data de avaliação
    updateDataAvaliacaoMin();
    
    // Event listeners para validação de data em tempo real
    const dataRealizacao = document.getElementById('data_realizacao');
    const dataAvaliacao = document.getElementById('data_avaliacao');
    
    if (dataRealizacao) {
        dataRealizacao.addEventListener('change', function() {
            validateDataRealizacao();
            updateDataAvaliacaoMin();
        });
        dataRealizacao.addEventListener('blur', validateDataRealizacao);
    }
    
    if (dataAvaliacao) {
        dataAvaliacao.addEventListener('change', validateDataAvaliacao);
        dataAvaliacao.addEventListener('blur', validateDataAvaliacao);
    }
    
    // Validação do formulário
    document.querySelector('form').addEventListener('submit', function(e) {
            let erro = false;
            
            // Validações de data
            if (!validateDataRealizacao()) {
                erro = true;
            }
            if (!validateDataAvaliacao()) {
                erro = true;
            }
            
            // Nota obrigatória
            const nota = document.getElementById('nota');
            if (!nota.value) {
                nota.classList.add('is-invalid');
                nota.focus();
                erro = true;
                if (!document.getElementById('nota-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.id = 'nota-feedback';
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'O campo Nota é obrigatório.';
                    nota.parentNode.appendChild(feedback);
                }
            } else {
                nota.classList.remove('is-invalid');
                const feedback = document.getElementById('nota-feedback');
                if (feedback) feedback.remove();
            }
            
            // Tipo de instrutor obrigatório
            const instructorType = document.getElementById('instructor_type');
            
            if (instructorType) {
                if (!instructorType.value) {
                    instructorType.classList.add('is-invalid');
                    instructorType.focus();
                    erro = true;
                    if (!document.getElementById('instructor-type-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.id = 'instructor-type-feedback';
                        feedback.className = 'invalid-feedback';
                        feedback.innerText = 'Selecione o tipo de instrutor.';
                        instructorType.parentNode.appendChild(feedback);
                    }
                } else {
                    instructorType.classList.remove('is-invalid');
                    const feedback = document.getElementById('instructor-type-feedback');
                    if (feedback) feedback.remove();
                    
                    // Só valida o instrutor se o tipo estiver selecionado
                    if (instructorType.value === 'internal') {
                        const instructorUser = document.getElementById('instructor_user_id');
                        if (!instructorUser.value) {
                            instructorUser.classList.add('is-invalid');
                            instructorUser.focus();
                            erro = true;
                            if (!document.getElementById('instructor-feedback')) {
                                const feedback = document.createElement('div');
                                feedback.id = 'instructor-feedback';
                                feedback.className = 'invalid-feedback';
                                feedback.innerText = 'Selecione o instrutor interno.';
                                instructorUser.parentNode.appendChild(feedback);
                            }
                        } else {
                            instructorUser.classList.remove('is-invalid');
                            const feedback = document.getElementById('instructor-feedback');
                            if (feedback) feedback.remove();
                        }
                    } else if (instructorType.value === 'external') {
                        const nome = document.getElementById('instrutor_nome');
                        const email = document.getElementById('instrutor_email');
                        if (!nome.value || !email.value) {
                            if (!nome.value) nome.classList.add('is-invalid');
                            if (!email.value) email.classList.add('is-invalid');
                            if (!nome.value) nome.focus();
                            erro = true;
                            if (!document.getElementById('instructor-feedback')) {
                                const feedback = document.createElement('div');
                                feedback.id = 'instructor-feedback';
                                feedback.className = 'invalid-feedback';
                                feedback.innerText = 'Informe nome e e-mail do instrutor externo.';
                                email.parentNode.appendChild(feedback);
                            }
                        } else {
                            nome.classList.remove('is-invalid');
                            email.classList.remove('is-invalid');
                            const feedback = document.getElementById('instructor-feedback');
                            if (feedback) feedback.remove();
                        }
                    }
                }
            }
            
            if (erro) {
                e.preventDefault();
            }
        });
});

function toggleInstructorFields() {
    var typeSelect = document.getElementById('instructor_type');
    var userDiv = document.getElementById('instructor_user_div');
    var nameDiv = document.getElementById('instructor_name_div');
    var emailDiv = document.getElementById('instructor_email_div');
    var userSelect = document.getElementById('instructor_user_id');
    var emailInput = document.getElementById('instrutor_email');
    var nameInput = document.getElementById('instrutor_nome');

    // Limpar validações anteriores
    userSelect.classList.remove('is-invalid');
    nameInput.classList.remove('is-invalid');
    emailInput.classList.remove('is-invalid');
    
    // Remover feedbacks de erro
    const feedbacks = document.querySelectorAll('#instructor-feedback, #instructor-type-feedback');
    feedbacks.forEach(feedback => feedback.remove());

    if (typeSelect.value === 'internal') {
        userDiv.style.display = 'block';
        nameDiv.style.display = 'none';
        emailDiv.style.display = 'block';
        emailInput.readOnly = true;
        nameInput.value = '';
        nameInput.removeAttribute('required');
        userSelect.setAttribute('required', 'required');
        setTimeout(fillInstructorEmail, 10);
    } else if (typeSelect.value === 'external') {
        userDiv.style.display = 'none';
        nameDiv.style.display = 'block';
        emailDiv.style.display = 'block';
        emailInput.readOnly = false;
        userSelect.value = '';
        userSelect.removeAttribute('required');
        nameInput.setAttribute('required', 'required');
        emailInput.setAttribute('required', 'required');
    } else {
        userDiv.style.display = 'none';
        nameDiv.style.display = 'none';
        emailDiv.style.display = 'none';
        userSelect.value = '';
        emailInput.value = '';
        nameInput.value = '';
        userSelect.removeAttribute('required');
        nameInput.removeAttribute('required');
        emailInput.removeAttribute('required');
    }
}

function fillInstructorEmail() {
    var userSelect = document.getElementById('instructor_user_id');
    var emailInput = document.getElementById('instrutor_email');
    if (!userSelect || !emailInput) return;
    var selectedOption = userSelect.options[userSelect.selectedIndex];
    if (userSelect.value && selectedOption && selectedOption.getAttribute('data-email')) {
        emailInput.value = selectedOption.getAttribute('data-email');
    } else {
        emailInput.value = '';
    }
}

// Validações de data
function validateDataRealizacao() {
    var dataRealizacao = document.getElementById('data_realizacao');
    var dataAvaliacao = document.getElementById('data_avaliacao');
    var hoje = new Date().toISOString().split('T')[0];
    
    // Data de realização não pode ser superior à data atual
    if (dataRealizacao.value > hoje) {
        dataRealizacao.classList.add('is-invalid');
        showFeedback(dataRealizacao, 'Data de realização não pode ser superior à data atual.');
        return false;
    }
    
    // Data de realização não pode ser anterior à data de criação do vínculo
    var dataCriacaoVinculo = '<?php echo $this->data['trainingUser']['created_at'] ? date('Y-m-d', strtotime($this->data['trainingUser']['created_at'])) : ''; ?>';
    if (dataCriacaoVinculo && dataRealizacao.value < dataCriacaoVinculo) {
        dataRealizacao.classList.add('is-invalid');
        showFeedback(dataRealizacao, 'Data de realização não pode ser anterior à data de criação do vínculo.');
        return false;
    }
    
    dataRealizacao.classList.remove('is-invalid');
    removeFeedback(dataRealizacao);
    return true;
}

function validateDataAvaliacao() {
    var dataAvaliacao = document.getElementById('data_avaliacao');
    var dataRealizacao = document.getElementById('data_realizacao');
    var hoje = new Date().toISOString().split('T')[0];
    
    if (!dataAvaliacao.value) return true; // Campo opcional
    
    // Data de avaliação não pode ser superior à data atual
    if (dataAvaliacao.value > hoje) {
        dataAvaliacao.classList.add('is-invalid');
        showFeedback(dataAvaliacao, 'Data de avaliação não pode ser superior à data atual.');
        return false;
    }
    
    // Data de avaliação não pode ser menor que a data de realização
    if (dataRealizacao.value && dataAvaliacao.value < dataRealizacao.value) {
        dataAvaliacao.classList.add('is-invalid');
        showFeedback(dataAvaliacao, 'Data de avaliação não pode ser menor que a data de realização.');
        return false;
    }
    
    dataAvaliacao.classList.remove('is-invalid');
    removeFeedback(dataAvaliacao);
    return true;
}

function showFeedback(element, message) {
    removeFeedback(element);
    var feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.innerText = message;
    element.parentNode.appendChild(feedback);
}

function removeFeedback(element) {
    var feedback = element.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

// Atualizar o atributo min da data de avaliação baseado na data de realização
function updateDataAvaliacaoMin() {
    var dataRealizacao = document.getElementById('data_realizacao');
    var dataAvaliacao = document.getElementById('data_avaliacao');
    
    if (dataRealizacao && dataAvaliacao && dataRealizacao.value) {
        dataAvaliacao.min = dataRealizacao.value;
        
        // Se a data de avaliação atual for menor que a nova data de realização, limpar
        if (dataAvaliacao.value && dataAvaliacao.value < dataRealizacao.value) {
            dataAvaliacao.value = '';
            // Remover validação de erro se existir
            dataAvaliacao.classList.remove('is-invalid');
            removeFeedback(dataAvaliacao);
        }
    }
}
</script> 