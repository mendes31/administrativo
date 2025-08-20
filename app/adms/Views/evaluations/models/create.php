<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário
$csrf_token = CSRFHelper::generateCSRFToken('form_create_evaluation_model');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Modelo de Avaliação</h2>

        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="text-decoration-none">Modelos de Avaliação</a></li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span>Cadastrar Novo Modelo</span>
        </div>

        <div class="card-body">
            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>create-evaluation-model" id="formCreateEvaluationModel">
                
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="training_id" class="form-label">Treinamento <span class="text-danger">*</span></label>
                        <select name="training_id" id="training_id" class="form-select" required>
                            <option value="">Selecione um treinamento</option>
                            <?php if (isset($this->data['trainings']) && is_array($this->data['trainings'])): ?>
                                <?php foreach ($this->data['trainings'] as $training): ?>
                                    <option value="<?php echo $training['id']; ?>" 
                                            <?php echo (isset($this->data['form']['training_id']) && $this->data['form']['training_id'] == $training['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($training['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ativo" class="form-label">Status</label>
                        <select name="ativo" id="ativo" class="form-select">
                            <option value="1" <?php echo (isset($this->data['form']['ativo']) && $this->data['form']['ativo'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo (isset($this->data['form']['ativo']) && $this->data['form']['ativo'] == 0) ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" id="titulo" class="form-control" 
                           value="<?php echo $this->data['form']['titulo'] ?? ''; ?>" 
                           placeholder="Digite o título do modelo de avaliação" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="4" 
                              placeholder="Digite uma descrição para o modelo de avaliação"><?php echo $this->data['form']['descricao'] ?? ''; ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" name="SendAddEvaluationModel" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário
    const form = document.getElementById('formCreateEvaluationModel');
    const titulo = document.getElementById('titulo');
    const training_id = document.getElementById('training_id');

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validar título
        if (titulo.value.trim() === '') {
            titulo.classList.add('is-invalid');
            isValid = false;
        } else {
            titulo.classList.remove('is-invalid');
        }

        // Validar treinamento
        if (training_id.value === '') {
            training_id.classList.add('is-invalid');
            isValid = false;
        } else {
            training_id.classList.remove('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
    });
});
</script> 