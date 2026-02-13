<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;

$csrfTokenVinculo = CSRFHelper::generateCSRFToken('form_rh_vincular_candidato_vaga');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vincular Candidatos à Vaga</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="text-decoration-none">Vagas</a>
            </li>
            <li class="breadcrumb-item">Vincular Candidatos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>
                <?php echo htmlspecialchars($this->data['vaga']['titulo'] ?? ''); ?>
            </h5>
            <small class="text-muted">
                Código: <?php echo htmlspecialchars($this->data['vaga']['codigo'] ?? '-'); ?> | 
                Área: <?php echo htmlspecialchars($this->data['vaga']['area_nome'] ?? '-'); ?> | 
                Cargo: <?php echo htmlspecialchars($this->data['vaga']['cargo_nome'] ?? '-'); ?> | 
                Status: <?php echo htmlspecialchars(ucfirst($this->data['vaga']['status'] ?? 'aberta')); ?>
            </small>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instruções:</strong> Marque os candidatos que devem ser vinculados a esta vaga. 
                        Use os filtros abaixo para encontrar candidatos por área de interesse, graduação, score, etc.
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="" id="formFiltros">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label for="filtro_nome" class="form-label small">Nome</label>
                                    <input type="text" name="nome" id="filtro_nome" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($this->data['filters']['nome'] ?? '') ?>" 
                                           placeholder="Nome do candidato">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro_email" class="form-label small">E-mail</label>
                                    <input type="text" name="email" id="filtro_email" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($this->data['filters']['email'] ?? '') ?>" 
                                           placeholder="E-mail">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro_area" class="form-label small">Área de Interesse</label>
                                    <select name="area_interesse" id="filtro_area" class="form-select form-select-sm">
                                        <option value="">Todas</option>
                                        <?php foreach ($this->data['areas'] ?? [] as $area): ?>
                                            <option value="<?= htmlspecialchars($area) ?>" 
                                                    <?= ($this->data['filters']['area_interesse'] ?? '') === $area ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($area) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro_status" class="form-label small">Status do Processo</label>
                                    <select name="status_processo" id="filtro_status" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                        <option value="recebido" <?= ($this->data['filters']['status_processo'] ?? '') === 'recebido' ? 'selected' : '' ?>>Recebido</option>
                                        <option value="em_entrevista" <?= ($this->data['filters']['status_processo'] ?? '') === 'em_entrevista' ? 'selected' : '' ?>>Em Entrevista</option>
                                        <option value="reprovado" <?= ($this->data['filters']['status_processo'] ?? '') === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                                        <option value="banco_talentos" <?= ($this->data['filters']['status_processo'] ?? '') === 'banco_talentos' ? 'selected' : '' ?>>Banco de Talentos</option>
                                        <option value="contratado" <?= ($this->data['filters']['status_processo'] ?? '') === 'contratado' ? 'selected' : '' ?>>Contratado</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filtro_score_min" class="form-label small">Score Mín.</label>
                                    <input type="number" name="score_min" id="filtro_score_min" class="form-control form-control-sm"
                                           min="0" max="100" 
                                           value="<?= htmlspecialchars($this->data['filters']['score_min'] ?? '') ?>" 
                                           placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label for="filtro_score_max" class="form-label small">Score Máx.</label>
                                    <input type="number" name="score_max" id="filtro_score_max" class="form-control form-control-sm"
                                           min="0" max="100" 
                                           value="<?= htmlspecialchars($this->data['filters']['score_max'] ?? '') ?>" 
                                           placeholder="100">
                                </div>
                                <div class="col-md-2">
                                    <label for="filtro_classificacao" class="form-label small">Classificação</label>
                                    <select name="classificacao" id="filtro_classificacao" class="form-select form-select-sm">
                                        <option value="">Todas</option>
                                        <option value="Excelente" <?= ($this->data['filters']['classificacao'] ?? '') === 'Excelente' ? 'selected' : '' ?>>Excelente</option>
                                        <option value="Muito Bom" <?= ($this->data['filters']['classificacao'] ?? '') === 'Muito Bom' ? 'selected' : '' ?>>Muito Bom</option>
                                        <option value="Bom" <?= ($this->data['filters']['classificacao'] ?? '') === 'Bom' ? 'selected' : '' ?>>Bom</option>
                                        <option value="Regular" <?= ($this->data['filters']['classificacao'] ?? '') === 'Regular' ? 'selected' : '' ?>>Regular</option>
                                        <option value="Baixo" <?= ($this->data['filters']['classificacao'] ?? '') === 'Baixo' ? 'selected' : '' ?>>Baixo</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" name="filtrar" class="btn btn-primary btn-sm me-2">
                                        <i class="fas fa-filter me-1"></i>Filtrar
                                    </button>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eraser me-1"></i>Limpar
                                    </a>
                                </div>
                            </div>
                    </form>
                </div>
            </div>

            <form method="post" action="" id="formVincular">
                <input type="hidden" name="csrf_token" value="<?= $csrfTokenVinculo ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:80px;" class="text-center">
                                    <i class="fas fa-check-circle me-1"></i>Vincular
                                </th>
                                <th style="width:60px;" class="text-center">ID</th>
                                <th>Nome</th>
                                <th>Área de Interesse</th>
                                <th style="width:100px;" class="text-center">Score</th>
                                <th>Formação</th>
                                <th>Última Experiência</th>
                                <th style="width:100px;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['candidatos'])): ?>
                                <?php foreach ($this->data['candidatos'] as $candidato): ?>
                                    <?php 
                                    $isLinked = in_array($candidato['id'], $this->data['candidatosVinculadosIds'] ?? []);
                                    $graduacaoText = trim(strip_tags($candidato['graduacao'] ?? ''));
                                    $expText = trim(strip_tags($candidato['ultima_experiencia'] ?? ''));
                                    ?>
                                    <tr class="<?php echo $isLinked ? 'table-success' : ''; ?>">
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="candidato_id[]" 
                                                       value="<?= (int)$candidato['id'] ?>"
                                                       id="cand<?= (int)$candidato['id'] ?>" 
                                                       <?php echo $isLinked ? 'checked' : ''; ?>
                                                       onchange="toggleVinculo(<?= (int)$candidato['id'] ?>)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= (int)$candidato['id'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($candidato['nome'] ?? '') ?></strong>
                                            <?php if (!empty($candidato['email'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($candidato['email']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($candidato['telefone'])): ?>
                                                <br><small class="text-muted">Tel: <?= htmlspecialchars($candidato['telefone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($candidato['area_interesse'] ?? '-') ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($candidato['score'])): ?>
                                                <?php
                                                $score = (int)$candidato['score'];
                                                $badgeClass = 'badge bg-secondary';
                                                if ($score >= 80) $badgeClass = 'badge bg-success';
                                                elseif ($score >= 60) $badgeClass = 'badge bg-info';
                                                elseif ($score >= 40) $badgeClass = 'badge bg-warning text-dark';
                                                else $badgeClass = 'badge bg-danger';
                                                ?>
                                                <span class="<?= $badgeClass ?>"><?= $score ?>/100</span>
                                                <?php if (!empty($candidato['classificacao'])): ?>
                                                    <br><small class="badge bg-primary"><?= htmlspecialchars($candidato['classificacao']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($graduacaoText): ?>
                                                <small><?= htmlspecialchars(strlen($graduacaoText) > 60 ? substr($graduacaoText, 0, 60) . '...' : $graduacaoText) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($expText): ?>
                                                <small><?= htmlspecialchars(strlen($expText) > 60 ? substr($expText, 0, 60) . '...' : $expText) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Nenhum candidato encontrado. Ajuste os filtros ou cadastre candidatos primeiro.
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
                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" onclick="selectAll()">
                                    <i class="fas fa-check-double me-2"></i>Selecionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="deselectAll()">
                                    <i class="fas fa-times me-2"></i>Desmarcar Todos
                                </button>
                                <button type="submit" form="formVincular" class="btn btn-primary">
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
function toggleVinculo(candidatoId) {
    const checkbox = document.getElementById('cand' + candidatoId);
    const row = checkbox.closest('tr');
    
    if (checkbox.checked) {
        row.classList.add('table-success');
        row.classList.remove('table-light');
    } else {
        row.classList.remove('table-success');
        row.classList.add('table-light');
    }
}

function selectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="candidato_id[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        toggleVinculo(checkbox.id.replace('cand', ''));
    });
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="candidato_id[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        toggleVinculo(checkbox.id.replace('cand', ''));
    });
}

// Inicializar estado dos campos
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="candidato_id[]"]');
    checkboxes.forEach(checkbox => {
        toggleVinculo(checkbox.id.replace('cand', ''));
    });
});
</script>

