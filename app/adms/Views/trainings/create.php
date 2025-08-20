<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_create_training');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Treinamento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" id="nome" value="<?php echo $this->data['form']['nome'] ?? ''; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" id="codigo" value="<?php echo $this->data['form']['codigo'] ?? ''; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="versao" class="form-label">Versão</label>
                    <input type="text" name="versao" class="form-control" id="versao" value="<?php echo $this->data['form']['versao'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="prazo_treinamento" class="form-label">Prazo Treinamento (dias)</label>
                    <input type="number" name="prazo_treinamento" class="form-control" id="prazo_treinamento" min="1" value="<?php echo $this->data['form']['prazo_treinamento'] ?? '1'; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="area_responsavel_id" class="form-label">Área Responsável</label>
                    <select name="area_responsavel_id" class="form-select" id="area_responsavel_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo (isset($this->data['form']['area_responsavel_id']) && $this->data['form']['area_responsavel_id'] == $dep['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="area_elaborador_id" class="form-label">Área Elaborador</label>
                    <select name="area_elaborador_id" class="form-select" id="area_elaborador_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo (isset($this->data['form']['area_elaborador_id']) && $this->data['form']['area_elaborador_id'] == $dep['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tipo_obrigatoriedade" class="form-label">Tipo de Obrigatoriedade</label>
                    <select name="tipo_obrigatoriedade" class="form-select" id="tipo_obrigatoriedade" required>
                        <option value="">Selecione...</option>
                        <option value="Legal" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Legal') ? 'selected' : ''; ?>>Legal</option>
                        <option value="Normativa" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Normativa') ? 'selected' : ''; ?>>Normativa</option>
                        <option value="Contratual" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Contratual') ? 'selected' : ''; ?>>Contratual</option>
                        <option value="Corporativa" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Corporativa') ? 'selected' : ''; ?>>Corporativa</option>
                        <option value="Técnica" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Técnica') ? 'selected' : ''; ?>>Técnica</option>
                        <option value="Estratégica" <?php echo (isset($this->data['form']['tipo_obrigatoriedade']) && $this->data['form']['tipo_obrigatoriedade'] == 'Estratégica') ? 'selected' : ''; ?>>Estratégica</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select name="categoria" class="form-select" id="categoria" required>
                        <option value="">Selecione...</option>
                        <option value="Autotreinamento" <?php echo (isset($this->data['form']['categoria']) && $this->data['form']['categoria'] == 'Autotreinamento') ? 'selected' : ''; ?>>Autotreinamento</option>
                        <option value="Ministrado" <?php echo (isset($this->data['form']['categoria']) && $this->data['form']['categoria'] == 'Ministrado') ? 'selected' : ''; ?>>Ministrado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="instructor_type" class="form-label">Tipo de Instrutor</label>
                    <select name="instructor_type" id="instructor_type" class="form-select" onchange="toggleInstructorFields()">
                        <option value="">Selecione...</option>
                        <option value="internal" <?php echo (isset($this->data['form']['instructor_type']) && $this->data['form']['instructor_type'] == 'internal') ? 'selected' : ''; ?>>Colaborador Interno</option>
                        <option value="external" <?php echo (isset($this->data['form']['instructor_type']) && $this->data['form']['instructor_type'] == 'external') ? 'selected' : ''; ?>>Instrutor Externo</option>
                    </select>
                </div>
                <div class="col-md-3" id="instructor_user_div" style="display: none;">
                    <label for="instructor_user_id" class="form-label">Instrutor (Colaborador Interno)</label>
                    <select name="instructor_user_id" id="instructor_user_id" class="form-select" onchange="fillInstructorEmail()">
                        <option value="">Selecione um usuário...</option>
                        <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                            <option value="<?php echo $user['id']; ?>" data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" <?php echo (isset($this->data['form']['instructor_user_id']) && $this->data['form']['instructor_user_id'] == $user['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3" id="instructor_name_div" style="display: none;">
                    <label for="instructor_name" class="form-label">Nome do Instrutor Externo</label>
                    <input type="text" name="instructor_name" class="form-control" id="instructor_name" value="<?php echo $this->data['form']['instructor_name'] ?? ''; ?>">
                </div>
                <div class="col-md-3" id="instructor_email_div" style="display: none;">
                    <label for="instructor_email" class="form-label">E-mail do Instrutor</label>
                    <input type="email" name="instructor_email" class="form-control" id="instructor_email" value="<?php echo $this->data['form']['instructor_email'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="carga_horaria" class="form-label">Carga Horária (hh:mm)</label>
                    <input type="time" name="carga_horaria" class="form-control" id="carga_horaria" step="60" value="<?php echo $this->data['form']['carga_horaria'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="ativo" class="form-label">Status</label>
                    <select name="ativo" class="form-select" id="ativo">
                        <option value="1" <?php echo (isset($this->data['form']['ativo']) && $this->data['form']['ativo'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo (isset($this->data['form']['ativo']) && $this->data['form']['ativo'] == 0) ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="reciclagem" class="form-label">Necessita Reciclagem?</label>
                    <select name="reciclagem" id="reciclagem" class="form-select" onchange="toggleReciclagemPeriodo()">
                        <option value="0" <?php echo (isset($this->data['form']['reciclagem']) && !$this->data['form']['reciclagem']) ? 'selected' : ''; ?>>Não</option>
                        <option value="1" <?php echo (isset($this->data['form']['reciclagem']) && $this->data['form']['reciclagem']) ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>
                <div class="col-md-3" id="reciclagem_periodo_div" style="display: none;">
                    <label for="reciclagem_periodo" class="form-label">Período de Reciclagem</label>
                    <input type="number" name="reciclagem_periodo" class="form-control" id="reciclagem_periodo" min="1" value="<?php echo $this->data['form']['reciclagem_periodo'] ?? ''; ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleInstructorFields() {
    var typeSelect = document.getElementById('instructor_type');
    var userDiv = document.getElementById('instructor_user_div');
    var nameDiv = document.getElementById('instructor_name_div');
    var emailDiv = document.getElementById('instructor_email_div');
    var userSelect = document.getElementById('instructor_user_id');
    var emailInput = document.getElementById('instructor_email');
    var nameInput = document.getElementById('instructor_name');

    if (typeSelect.value === 'internal') {
        userDiv.style.display = 'block';
        nameDiv.style.display = 'none';
        emailDiv.style.display = 'block';
        emailInput.readOnly = true;
        nameInput.value = '';
        setTimeout(fillInstructorEmail, 10);
    } else if (typeSelect.value === 'external') {
        userDiv.style.display = 'none';
        nameDiv.style.display = 'block';
        emailDiv.style.display = 'block';
        emailInput.readOnly = false;
        userSelect.value = '';
        emailInput.value = '';
    } else {
        userDiv.style.display = 'none';
        nameDiv.style.display = 'none';
        emailDiv.style.display = 'none';
        userSelect.value = '';
        emailInput.value = '';
        nameInput.value = '';
    }
}

function fillInstructorEmail() {
    var userSelect = document.getElementById('instructor_user_id');
    var emailInput = document.getElementById('instructor_email');
    if (!userSelect || !emailInput) return;
    var selectedOption = userSelect.options[userSelect.selectedIndex];
    if (userSelect.value && selectedOption && selectedOption.getAttribute('data-email')) {
        emailInput.value = selectedOption.getAttribute('data-email');
    } else {
        emailInput.value = '';
    }
}

function toggleReciclagemPeriodo() {
    var reciclagemSelect = document.getElementById('reciclagem');
    var periodoDiv = document.getElementById('reciclagem_periodo_div');
    if (reciclagemSelect.value == '1') {
        periodoDiv.style.display = 'block';
    } else {
        periodoDiv.style.display = 'none';
        document.getElementById('reciclagem_periodo').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var userSelect = document.getElementById('instructor_user_id');
    if (userSelect) {
        userSelect.addEventListener('change', fillInstructorEmail);
    }
    toggleInstructorFields();
    var typeSelect = document.getElementById('instructor_type');
    if (typeSelect && typeSelect.value === 'internal') {
        fillInstructorEmail();
    }
    toggleReciclagemPeriodo();
    
    // Validação do prazo de treinamento
    var prazoTreinamento = document.getElementById('prazo_treinamento');
    if (prazoTreinamento) {
        prazoTreinamento.addEventListener('input', function() {
            var value = parseInt(this.value);
            if (value <= 0) {
                this.setCustomValidity('O prazo de treinamento deve ser maior que 0.');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validar no envio do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var prazoValue = parseInt(prazoTreinamento.value);
            if (prazoValue <= 0) {
                e.preventDefault();
                alert('O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.');
                prazoTreinamento.focus();
                return false;
            }
        });
    }
});
</script> 