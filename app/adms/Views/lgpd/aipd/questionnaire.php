<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Questionário AIPD - Avaliação de Impacto à Proteção de Dados</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Questionário</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list text-primary"></i>
                Questionário Dinâmico para AIPD
            </h5>
            <small class="text-muted">
                Responda as perguntas abaixo para que o sistema possa avaliar se sua operação necessita de AIPD e gerar recomendações personalizadas.
            </small>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_questionario_aipd'); ?>">

                <?php 
                $secaoNum = 1;
                foreach ($this->data['questionario'] as $secaoKey => $secao): 
                ?>
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-list-ol"></i>
                                Seção <?php echo $secaoNum; ?>: <?php echo htmlspecialchars($secao['titulo']); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($secao['perguntas'] as $pergunta): ?>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <?php echo htmlspecialchars($pergunta['pergunta']); ?>
                                        <?php if ($pergunta['obrigatoria']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($pergunta['tipo'] === 'select'): ?>
                                        <select name="<?php echo $pergunta['id']; ?>" 
                                                class="form-select <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>"
                                                <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>>
                                            <option value="">Selecione uma opção</option>
                                            <?php foreach ($pergunta['opcoes'] as $valor => $texto): ?>
                                                <option value="<?php echo $valor; ?>" 
                                                        <?php echo (isset($this->data['form'][$pergunta['id']]) && $this->data['form'][$pergunta['id']] === $valor) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($texto); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($pergunta['tipo'] === 'radio'): ?>
                                        <div class="mt-2">
                                            <?php foreach ($pergunta['opcoes'] as $valor => $texto): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>" 
                                                           type="radio" 
                                                           name="<?php echo $pergunta['id']; ?>" 
                                                           value="<?php echo $valor; ?>" 
                                                           id="<?php echo $pergunta['id'] . '_' . $valor; ?>"
                                                           <?php echo (isset($this->data['form'][$pergunta['id']]) && $this->data['form'][$pergunta['id']] === $valor) ? 'checked' : ''; ?>
                                                           <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $pergunta['id'] . '_' . $valor; ?>">
                                                        <?php echo htmlspecialchars($texto); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                    <?php elseif ($pergunta['tipo'] === 'checkbox'): ?>
                                        <div class="mt-2">
                                            <?php foreach ($pergunta['opcoes'] as $valor => $texto): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>" 
                                                           type="checkbox" 
                                                           name="<?php echo $pergunta['id']; ?>[]" 
                                                           value="<?php echo $valor; ?>" 
                                                           id="<?php echo $pergunta['id'] . '_' . $valor; ?>"
                                                           <?php echo (isset($this->data['form'][$pergunta['id']]) && is_array($this->data['form'][$pergunta['id']]) && in_array($valor, $this->data['form'][$pergunta['id']])) ? 'checked' : ''; ?>
                                                           <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $pergunta['id'] . '_' . $valor; ?>">
                                                        <?php echo htmlspecialchars($texto); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                    <?php elseif ($pergunta['tipo'] === 'textarea'): ?>
                                        <textarea name="<?php echo $pergunta['id']; ?>" 
                                                  class="form-control <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>"
                                                  rows="3"
                                                  placeholder="Digite sua resposta..."
                                                  <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>><?php echo htmlspecialchars($this->data['form'][$pergunta['id']] ?? ''); ?></textarea>

                                    <?php else: ?>
                                        <input type="text" 
                                               name="<?php echo $pergunta['id']; ?>" 
                                               class="form-control <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>"
                                               value="<?php echo htmlspecialchars($this->data['form'][$pergunta['id']] ?? ''); ?>"
                                               <?php echo $pergunta['obrigatoria'] ? 'required' : ''; ?>>
                                    <?php endif; ?>

                                    <div class="invalid-feedback">
                                        Esta pergunta é obrigatória.
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php 
                $secaoNum++;
                endforeach; 
                ?>

                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Importante:</h6>
                    <ul class="mb-0">
                        <li>Responda todas as perguntas obrigatórias (marcadas com *)</li>
                        <li>Seja o mais preciso possível nas suas respostas</li>
                        <li>O sistema analisará suas respostas e gerará recomendações personalizadas</li>
                        <li>Com base no resultado, você poderá criar uma AIPD com dados pré-preenchidos</li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Processar Questionário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validação do formulário
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Validação para checkboxes obrigatórios
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"].required');
    
    checkboxes.forEach(function(checkbox) {
        const name = checkbox.name;
        const checkboxesWithSameName = document.querySelectorAll(`input[name="${name}"]`);
        
        checkboxesWithSameName.forEach(function(cb) {
            cb.addEventListener('change', function() {
                const checkedBoxes = document.querySelectorAll(`input[name="${name}"]:checked`);
                const isValid = checkedBoxes.length > 0;
                
                checkboxesWithSameName.forEach(function(c) {
                    if (isValid) {
                        c.setCustomValidity('');
                    } else {
                        c.setCustomValidity('Selecione pelo menos uma opção');
                    }
                });
            });
        });
    });
});
</script>
