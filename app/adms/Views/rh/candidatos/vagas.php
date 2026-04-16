<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;

$csrfTokenVinculo = CSRFHelper::generateCSRFToken('form_rh_vincular_candidato_vaga');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vincular Vagas ao Candidato</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="text-decoration-none">Currículos</a>
            </li>
            <li class="breadcrumb-item">Vincular Vagas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>
                <?php echo htmlspecialchars($this->data['candidato']['nome'] ?? ''); ?>
            </h5>
            <small class="text-muted">
                ID: <?php echo (int)($this->data['candidato']['id'] ?? 0); ?> | 
                E-mail: <?php echo htmlspecialchars($this->data['candidato']['email'] ?? '-'); ?> | 
                Status Processo: <?php echo htmlspecialchars($this->data['candidato']['status_processo'] ?? '-'); ?>
            </small>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instruções:</strong> Marque as vagas em que este candidato deve ser vinculado.
                        Use os filtros abaixo para encontrar vagas por título, área, cargo, tipo de contrato, etc.
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="" id="formFiltrosVagas">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label for="filtro_titulo" class="form-label small">Título da Vaga</label>
                                <input type="text" name="titulo" id="filtro_titulo" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($this->data['filters']['titulo'] ?? '') ?>" 
                                       placeholder="Título da vaga">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_status_vaga" class="form-label small">Status da Vaga</label>
                                <?php
                                $statusAtual = $this->data['filters']['status'] ?? '';
                                $statusLista = [
                                    ''         => 'Todas',
                                    'aberta'   => 'Aberta',
                                    'pausada'  => 'Pausada',
                                    'fechada'  => 'Fechada',
                                    'cancelada'=> 'Cancelada',
                                ];
                                ?>
                                <select name="status" id="filtro_status_vaga" class="form-select form-select-sm">
                                    <?php foreach ($statusLista as $valor => $label): ?>
                                        <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_area_vaga" class="form-label small">Área/Departamento</label>
                                <select name="area_id" id="filtro_area_vaga" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($this->data['departments'] as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= ($this->data['filters']['area_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_cargo_vaga" class="form-label small">Cargo</label>
                                <select name="cargo_id" id="filtro_cargo_vaga" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($this->data['positions'] as $pos): ?>
                                        <option value="<?= $pos['id'] ?>" <?= ($this->data['filters']['cargo_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pos['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_tipo_contrato" class="form-label small">Tipo de Contrato</label>
                                <?php
                                $tipoAtual = $this->data['filters']['tipo_contrato'] ?? '';
                                $tipos = [
                                    ''          => 'Todos',
                                    'CLT'       => 'CLT',
                                    'PJ'        => 'PJ',
                                    'Estágio'   => 'Estágio',
                                    'Temporário'=> 'Temporário',
                                    'Freelancer'=> 'Freelancer',
                                ];
                                ?>
                                <select name="tipo_contrato" id="filtro_tipo_contrato" class="form-select form-select-sm">
                                    <?php foreach ($tipos as $valor => $label): ?>
                                        <option value="<?= $valor ?>" <?= $tipoAtual === $valor ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="filtrar" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-vagas/<?= (int)($this->data['candidato']['id'] ?? 0) ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eraser me-1"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <form method="post" action="" id="formVincularVagas">
                <input type="hidden" name="csrf_token" value="<?= $csrfTokenVinculo ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:80px;" class="text-center">
                                    <i class="fas fa-check-circle me-1"></i>Vincular
                                </th>
                                <th style="width:60px;" class="text-center">ID</th>
                                <th>Título</th>
                                <th>Área</th>
                                <th>Cargo</th>
                                <th style="width:120px;" class="text-center">Tipo</th>
                                <th style="width:120px;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['vagas'])): ?>
                                <?php foreach ($this->data['vagas'] as $vaga): ?>
                                    <?php 
                                    $isLinked = in_array($vaga['id'], $this->data['vagasVinculadasIds'] ?? []);
                                    ?>
                                    <tr class="<?php echo $isLinked ? 'table-success' : ''; ?>">
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="vaga_id[]" 
                                                       value="<?= (int)$vaga['id'] ?>"
                                                       id="vaga<?= (int)$vaga['id'] ?>" 
                                                       <?php echo $isLinked ? 'checked' : ''; ?>
                                                       onchange="toggleVagaVinculo(<?= (int)$vaga['id'] ?>)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= (int)$vaga['id'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($vaga['titulo'] ?? '') ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($vaga['area_nome'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($vaga['cargo_nome'] ?? '')) ?: '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($vaga['tipo_contrato'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php
                                            $st = $vaga['status'] ?? 'aberta';
                                            $badge = match($st) {
                                                'aberta'   => 'badge bg-success',
                                                'pausada'  => 'badge bg-warning text-dark',
                                                'fechada'  => 'badge bg-secondary',
                                                'cancelada'=> 'badge bg-danger',
                                                default    => 'badge bg-secondary',
                                            };
                                            ?>
                                            <span class="<?= $badge ?>">
                                                <?= htmlspecialchars(ucfirst($st)) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Nenhuma vaga encontrada. Ajuste os filtros ou cadastre vagas primeiro.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)($this->data['candidato']['id'] ?? 0) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" onclick="selectAllVagas()">
                                <i class="fas fa-check-double me-2"></i>Selecionar Todas
                            </button>
                            <button type="button" class="btn btn-outline-secondary me-2" onclick="deselectAllVagas()">
                                <i class="fas fa-times me-2"></i>Desmarcar Todas
                            </button>
                            <button type="submit" form="formVincularVagas" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Vínculos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVagaVinculo(vagaId) {
    const checkbox = document.getElementById('vaga' + vagaId);
    const row = checkbox.closest('tr');

    if (checkbox.checked) {
        row.classList.add('table-success');
        row.classList.remove('table-light');
    } else {
        row.classList.remove('table-success');
        row.classList.add('table-light');
    }
}

function selectAllVagas() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="vaga_id[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        toggleVagaVinculo(checkbox.id.replace('vaga', ''));
    });
}

function deselectAllVagas() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="vaga_id[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        toggleVagaVinculo(checkbox.id.replace('vaga', ''));
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="vaga_id[]"]');
    checkboxes.forEach(checkbox => {
        toggleVagaVinculo(checkbox.id.replace('vaga', ''));
    });
});
</script>

