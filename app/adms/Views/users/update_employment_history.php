<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Histórico de Emprego</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-user/<?= $this->data['history']['adms_user_id'] ?>" class="text-decoration-none">
                    Visualizar Usuário
                </a>
            </li>
            <li class="breadcrumb-item">Editar Histórico</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Editar Histórico de Emprego</span>
            <span class="ms-auto d-sm-flex flex-row">
                <?php if (in_array('ViewUser', $this->data['buttonPermission'] ?? [])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-user/{$this->data['history']['adms_user_id']}' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Voltar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($this->data['user'])): ?>
                <div class="alert alert-info mb-3">
                    <strong>Colaborador:</strong> <?= htmlspecialchars($this->data['user']['name'] ?? '') ?><br>
                    <strong>ID do Registro:</strong> #<?= $this->data['history']['id'] ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_employment_history'); ?>">

                <div class="col-md-6">
                    <label for="tipo_periodo" class="form-label">Tipo de Período</label>
                    <select name="tipo_periodo" class="form-select" id="tipo_periodo" required>
                        <option value="Admissão" <?= ($this->data['history']['tipo_periodo'] ?? '') === 'Admissão' ? 'selected' : '' ?>>Admissão</option>
                        <option value="Recontratação" <?= ($this->data['history']['tipo_periodo'] ?? '') === 'Recontratação' ? 'selected' : '' ?>>Recontratação</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="data_admissao" class="form-label">Data de Admissão <span class="text-danger">*</span></label>
                    <input type="date" name="data_admissao" class="form-control" id="data_admissao" 
                           value="<?= $this->data['history']['data_admissao'] ?? '' ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="data_desligamento" class="form-label">Data de Desligamento</label>
                    <input type="date" name="data_desligamento" class="form-control" id="data_desligamento" 
                           value="<?= $this->data['history']['data_desligamento'] ?? '' ?>">
                    <div class="form-text">Deixe em branco se o colaborador ainda está ativo neste período</div>
                </div>

                <div class="col-md-6">
                    <label for="motivo_desligamento" class="form-label">Motivo do Desligamento</label>
                    <input type="text" name="motivo_desligamento" class="form-control" id="motivo_desligamento" 
                           placeholder="Ex: Pedido de demissão, Demissão sem justa causa, Aposentadoria, etc." 
                           value="<?= htmlspecialchars($this->data['history']['motivo_desligamento'] ?? '') ?>" 
                           maxlength="255">
                    <div class="form-text">Preencha apenas se houver data de desligamento</div>
                </div>

                <div class="col-md-6">
                    <?php $hti = $this->data['history']['tipo_impacto_desligamento'] ?? ''; ?>
                    <label for="tipo_impacto_desligamento" class="form-label">Classificação do desligamento</label>
                    <select name="tipo_impacto_desligamento" id="tipo_impacto_desligamento" class="form-select">
                        <option value="" <?= ($hti === '' || $hti === null) ? 'selected' : '' ?>>Não informado</option>
                        <option value="regrettable" <?= $hti === 'regrettable' ? 'selected' : '' ?>>Regrettable (desejável reter)</option>
                        <option value="non_regrettable" <?= $hti === 'non_regrettable' ? 'selected' : '' ?>>Non-regrettable</option>
                        <option value="nao_classificado" <?= $hti === 'nao_classificado' ? 'selected' : '' ?>>Não classificado (explícito)</option>
                    </select>
                    <div class="form-text">Para People Analytics / turnover. Opcional se não houver desligamento.</div>
                </div>

                <div class="col-md-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" id="observacoes" rows="3" 
                              placeholder="Observações adicionais sobre este período..."><?= htmlspecialchars($this->data['history']['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-user/<?= $this->data['history']['adms_user_id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataDesligamentoInput = document.getElementById('data_desligamento');
    const motivoDesligamentoInput = document.getElementById('motivo_desligamento');
    
    // Mostrar/ocultar campo de motivo baseado na data de desligamento
    function toggleMotivoField() {
        if (dataDesligamentoInput.value) {
            motivoDesligamentoInput.closest('.col-md-6').style.display = 'block';
        } else {
            motivoDesligamentoInput.closest('.col-md-6').style.display = 'block'; // Sempre mostrar, mas opcional
        }
    }
    
    dataDesligamentoInput.addEventListener('change', toggleMotivoField);
    toggleMotivoField(); // Verificar estado inicial
});
</script>

