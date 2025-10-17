<?php
use App\adms\Helpers\FormatHelper;
// ... existing code ...
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vincular Cargos ao Treinamento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Vincular Cargos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>
                <?php echo htmlspecialchars($this->data['training']['nome'] ?? $this->data['training']['name'] ?? ''); ?>
            </h5>
            <small class="text-muted">
                Código: <?php echo htmlspecialchars($this->data['training']['codigo'] ?? ''); ?> | 
                Versão: <?php echo htmlspecialchars($this->data['training']['versao'] ?? ''); ?> | 
                Tipo: <?php echo htmlspecialchars($this->data['training']['tipo'] ?? ''); ?>
            </small>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Instruções:</strong> Marque os cargos que devem ter este treinamento como obrigatório. 
                            Os colaboradores desses cargos receberão automaticamente este treinamento em sua matriz de obrigatoriedade.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:80px;" class="text-center">
                                    <i class="fas fa-check-circle me-1"></i>Obrigatório
                                </th>
                                <th style="width:60px;" class="text-center">ID</th>
                                <th>Cargo</th>
                                <th style="width:180px;" class="text-center">Tipo do Treinamento</th>
                                <th style="width:100px;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['positions'])): ?>
                                <?php foreach ($this->data['positions'] as $position): ?>
                                    <?php 
                                    $isLinked = in_array($position['id'], $this->data['linkedPositionIds'] ?? []);
                                    $linkedData = null;
                                    if ($isLinked) {
                                        // Buscar dados específicos do vínculo
                                        foreach ($this->data['linkedPositions'] as $link) {
                                            if (is_array($link) && $link['adms_position_id'] == $position['id']) {
                                                $linkedData = $link;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr class="<?php echo $isLinked ? 'table-success' : ''; ?>">
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="obrigatorio[<?php echo $position['id']; ?>]" 
                                                       value="1"
                                                       id="pos<?php echo $position['id']; ?>" 
                                                       <?php echo $isLinked ? 'checked' : ''; ?>
                                                       onchange="toggleObrigatorio(<?php echo $position['id']; ?>)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $position['id']; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($position['name']); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <select class="form-select form-select-sm w-auto d-inline-block"
                                                    name="tipo_treinamento[<?php echo $position['id']; ?>]"
                                                    id="tipo_<?php echo $position['id']; ?>"
                                                    <?php echo !$isLinked ? 'disabled' : ''; ?>
                                            >
                                                <?php 
                                                    $tipo = $linkedData['tipo_treinamento'] ?? 'Inicial';
                                                ?>
                                                <option value="Inicial" <?php echo ($tipo === 'Inicial') ? 'selected' : ''; ?>>Inicial</option>
                                                <option value="Continuo" <?php echo ($tipo === 'Continuo') ? 'selected' : ''; ?>>Contínuo</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isLinked): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Vinculado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-times me-1"></i>Não vinculado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Nenhum cargo encontrado. Cadastre cargos primeiro.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" onclick="selectAll()">
                                    <i class="fas fa-check-double me-2"></i>Selecionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="deselectAll()">
                                    <i class="fas fa-times me-2"></i>Desmarcar Todos
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Vínculos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleObrigatorio(positionId) {
    const checkbox = document.getElementById('pos' + positionId);
    const tipoSelect = document.getElementById('tipo_' + positionId);
    const row = checkbox.closest('tr');
    
    if (checkbox.checked) {
        if (tipoSelect) tipoSelect.disabled = false;
        row.classList.add('table-success');
        row.classList.remove('table-light');
    } else {
        if (tipoSelect) tipoSelect.disabled = true;
        row.classList.remove('table-success');
        row.classList.add('table-light');
    }
}

function selectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        toggleObrigatorio(checkbox.id.replace('pos', ''));
    });
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        toggleObrigatorio(checkbox.id.replace('pos', ''));
    });
}

// Inicializar estado dos campos
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        toggleObrigatorio(checkbox.id.replace('pos', ''));
    });
});
</script> 