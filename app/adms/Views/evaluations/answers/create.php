<?php
if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}
?>

<div class="wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-plus"></i>
            </span>
            Criar Resposta de Avaliação
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= getenv('URL_ADM') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= getenv('URL_ADM') ?>list-evaluation-answers/index">Respostas de Avaliação</a></li>
                <li class="breadcrumb-item active" aria-current="page">Criar</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" class="forms-sample">
                        <?php
                        if (isset($_SESSION['msg'])) {
                            echo $_SESSION['msg'];
                            unset($_SESSION['msg']);
                        }
                        
                        if (isset($this->data['error'])) {
                            echo '<div class="alert alert-danger">' . $this->data['error'] . '</div>';
                        }
                        
                        if (isset($this->data['success'])) {
                            echo '<div class="alert alert-success">' . $this->data['success'] . '</div>';
                        }
                        ?>

                        <!-- Campo CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $this->data['csrf_token'] ?>">

                        <div class="form-group">
                            <label for="usuario_id">Usuário <span class="text-danger">*</span></label>
                            <select name="usuario_id" id="usuario_id" class="form-control" required>
                                <option value="">Selecione um usuário</option>
                                <?php
                                if (isset($this->data['users']) && !empty($this->data['users'])) {
                                    foreach ($this->data['users'] as $user) {
                                        $selected = '';
                                        if (isset($this->data['form']['usuario_id']) && $this->data['form']['usuario_id'] == $user['id']) {
                                            $selected = 'selected';
                                        }
                                        echo "<option value='{$user['id']}' {$selected}>{$user['nome']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evaluation_model_id">Modelo de Avaliação <span class="text-danger">*</span></label>
                            <select name="evaluation_model_id" id="evaluation_model_id" class="form-control" required>
                                <option value="">Selecione um modelo</option>
                                <?php
                                if (isset($this->data['models']) && !empty($this->data['models'])) {
                                    foreach ($this->data['models'] as $model) {
                                        $selected = '';
                                        if (isset($this->data['form']['evaluation_model_id']) && $this->data['form']['evaluation_model_id'] == $model['id']) {
                                            $selected = 'selected';
                                        }
                                        echo "<option value='{$model['id']}' {$selected}>{$model['titulo']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evaluation_question_id">Pergunta <span class="text-danger">*</span></label>
                            <select name="evaluation_question_id" id="evaluation_question_id" class="form-control" required>
                                <option value="">Selecione uma pergunta</option>
                                <?php
                                if (isset($this->data['questions']) && !empty($this->data['questions'])) {
                                    foreach ($this->data['questions'] as $question) {
                                        $selected = '';
                                        if (isset($this->data['form']['evaluation_question_id']) && $this->data['form']['evaluation_question_id'] == $question['id']) {
                                            $selected = 'selected';
                                        }
                                        echo "<option value='{$question['id']}' {$selected}>{$question['pergunta']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="resposta">Resposta <span class="text-danger">*</span></label>
                            <textarea name="resposta" id="resposta" class="form-control" rows="4" placeholder="Digite a resposta" required><?= isset($this->data['form']['resposta']) ? $this->data['form']['resposta'] : '' ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="pontuacao">Pontuação</label>
                            <input type="number" name="pontuacao" id="pontuacao" class="form-control" step="0.01" min="0" max="10" placeholder="0.00" value="<?= isset($this->data['form']['pontuacao']) ? $this->data['form']['pontuacao'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="comentario">Comentário</label>
                            <textarea name="comentario" id="comentario" class="form-control" rows="3" placeholder="Digite um comentário (opcional)"><?= isset($this->data['form']['comentario']) ? $this->data['form']['comentario'] : '' ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="ativo" <?= (isset($this->data['form']['status']) && $this->data['form']['status'] == 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= (isset($this->data['form']['status']) && $this->data['form']['status'] == 'inativo') ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>

                        <button type="submit" name="SendCreateEvaluationAnswer" value="Cadastrar" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Cadastrar
                        </button>

                        <a href="<?= getenv('URL_ADM') ?>list-evaluation-answers/index" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Voltar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro dinâmico de perguntas baseado no modelo selecionado
    const modelSelect = document.getElementById('evaluation_model_id');
    const questionSelect = document.getElementById('evaluation_question_id');
    
    modelSelect.addEventListener('change', function() {
        const modelId = this.value;
        
        // Limpar perguntas
        questionSelect.innerHTML = '<option value="">Selecione uma pergunta</option>';
        
        if (modelId) {
            // Fazer requisição AJAX para buscar perguntas do modelo
            fetch(`<?= getenv('URL_ADM') ?>get-questions-by-model/${modelId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.questions) {
                        data.questions.forEach(question => {
                            const option = document.createElement('option');
                            option.value = question.id;
                            option.textContent = question.pergunta;
                            questionSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar perguntas:', error);
                });
        }
    });
});
</script> 