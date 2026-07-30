<?php
use App\adms\Helpers\CSRFHelper;

$csrfTokenVinculo = CSRFHelper::generateCSRFToken('form_rh_vincular_candidato_vaga');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3 mb-0">Vincular Candidatos à Vaga</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto flex-shrink-0">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="text-decoration-none">Vagas</a>
            </li>
            <li class="breadcrumb-item">Vincular Candidatos</li>
        </ol>
    </div>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>
                <?php echo htmlspecialchars($this->data['vaga']['titulo'] ?? ''); ?>
            </h5>
            <small class="text-muted text-break">
                Código: <?php echo htmlspecialchars($this->data['vaga']['codigo'] ?? '-'); ?> ·
                Área: <?php echo htmlspecialchars($this->data['vaga']['area_nome'] ?? '-'); ?> ·
                Cargo: <?php echo htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($this->data['vaga']['cargo_nome'] ?? '')) ?: '-'); ?> ·
                Status: <?php echo htmlspecialchars(ucfirst($this->data['vaga']['status'] ?? 'aberta')); ?>
            </small>
        </div>
        <div class="card-body">
            <div class="alert alert-info small">
                <i class="fas fa-info-circle me-2"></i>
                Marque os candidatos que devem ser vinculados a esta vaga. Use os filtros para encontrar por área, score, etc.
            </div>

            <div class="card mb-3 border">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="" id="formFiltros">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label for="filtro_nome" class="form-label small">Nome</label>
                                <input type="text" name="nome" id="filtro_nome" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($this->data['filters']['nome'] ?? '') ?>"
                                       placeholder="Nome do candidato">
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="filtro_email" class="form-label small">E-mail</label>
                                <input type="text" name="email" id="filtro_email" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($this->data['filters']['email'] ?? '') ?>"
                                       placeholder="E-mail">
                            </div>
                            <div class="col-6 col-md-3">
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
                            <div class="col-6 col-md-3">
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
                            <div class="col-4 col-md-2">
                                <label for="filtro_score_min" class="form-label small">Score Mín.</label>
                                <input type="number" name="score_min" id="filtro_score_min" class="form-control form-control-sm"
                                       min="0" max="100"
                                       value="<?= htmlspecialchars($this->data['filters']['score_min'] ?? '') ?>"
                                       placeholder="0">
                            </div>
                            <div class="col-4 col-md-2">
                                <label for="filtro_score_max" class="form-label small">Score Máx.</label>
                                <input type="number" name="score_max" id="filtro_score_max" class="form-control form-control-sm"
                                       min="0" max="100"
                                       value="<?= htmlspecialchars($this->data['filters']['score_max'] ?? '') ?>"
                                       placeholder="100">
                            </div>
                            <div class="col-4 col-md-2">
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
                            <div class="col-12 col-md-3 d-flex flex-wrap align-items-end gap-2">
                                <button type="submit" name="filtrar" class="btn btn-primary btn-sm">
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

                <?php if (!empty($this->data['candidatos'])): ?>
                    <div class="vstack gap-2">
                        <?php foreach ($this->data['candidatos'] as $candidato):
                            $isLinked = in_array($candidato['id'], $this->data['candidatosVinculadosIds'] ?? [], true)
                                || in_array((int) $candidato['id'], array_map('intval', $this->data['candidatosVinculadosIds'] ?? []), true);
                            $graduacaoText = trim(strip_tags($candidato['graduacao'] ?? ''));
                            $expText = trim(strip_tags($candidato['ultima_experiencia'] ?? ''));
                            $candId = (int) $candidato['id'];
                            $score = !empty($candidato['score']) ? (int) $candidato['score'] : null;
                            $badgeClass = 'badge bg-secondary';
                            if ($score !== null) {
                                if ($score >= 80) {
                                    $badgeClass = 'badge bg-success';
                                } elseif ($score >= 60) {
                                    $badgeClass = 'badge bg-info';
                                } elseif ($score >= 40) {
                                    $badgeClass = 'badge bg-warning text-dark';
                                } else {
                                    $badgeClass = 'badge bg-danger';
                                }
                            }
                            ?>
                            <div class="border rounded-3 p-3 <?= $isLinked ? 'border-success bg-success-subtle' : 'bg-white' ?>"
                                 id="card-cand-<?= $candId ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check form-switch m-0 pt-1 flex-shrink-0">
                                        <input class="form-check-input" type="checkbox"
                                               name="candidato_id[]"
                                               value="<?= $candId ?>"
                                               id="cand<?= $candId ?>"
                                               <?= $isLinked ? 'checked' : '' ?>
                                               onchange="toggleVinculo(<?= $candId ?>)">
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="badge bg-secondary">#<?= $candId ?></span>
                                            <strong class="text-break"><?= htmlspecialchars($candidato['nome'] ?? '') ?></strong>
                                            <?php if ($isLinked): ?>
                                                <span class="badge bg-success">Vinculado</span>
                                            <?php endif; ?>
                                            <?php if ($score !== null): ?>
                                                <span class="<?= $badgeClass ?>"><?= $score ?>/100</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-break text-muted">
                                            <?= htmlspecialchars($candidato['email'] ?? '') ?>
                                            <?php if (!empty($candidato['telefone'])): ?>
                                                · <?= htmlspecialchars($candidato['telefone']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small mt-1">
                                            <span class="text-muted">Área:</span>
                                            <?= htmlspecialchars($candidato['area_interesse'] ?? '-') ?>
                                        </div>
                                        <?php if ($graduacaoText !== ''): ?>
                                            <div class="small text-muted text-break">Formação: <?= htmlspecialchars(strlen($graduacaoText) > 90 ? substr($graduacaoText, 0, 90) . '…' : $graduacaoText) ?></div>
                                        <?php endif; ?>
                                        <?php if ($expText !== ''): ?>
                                            <div class="small text-muted text-break">Experiência: <?= htmlspecialchars(strlen($expText) > 90 ? substr($expText, 0, 90) . '…' : $expText) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border text-muted mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Nenhum candidato encontrado. Ajuste os filtros ou cadastre candidatos primeiro.
                    </div>
                <?php endif; ?>
            </form>

            <div class="mt-3 d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-between">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                        <i class="fas fa-check-double me-1"></i>Selecionar todos
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()">
                        <i class="fas fa-times me-1"></i>Desmarcar
                    </button>
                    <button type="submit" form="formVincular" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar vínculos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVinculo(candidatoId) {
    const checkbox = document.getElementById('cand' + candidatoId);
    const card = document.getElementById('card-cand-' + candidatoId);
    if (!checkbox || !card) return;
    if (checkbox.checked) {
        card.classList.add('border-success', 'bg-success-subtle');
        card.classList.remove('bg-white');
    } else {
        card.classList.remove('border-success', 'bg-success-subtle');
        card.classList.add('bg-white');
    }
}

function selectAll() {
    document.querySelectorAll('input[type="checkbox"][name="candidato_id[]"]').forEach(function (checkbox) {
        checkbox.checked = true;
        toggleVinculo(checkbox.id.replace('cand', ''));
    });
}

function deselectAll() {
    document.querySelectorAll('input[type="checkbox"][name="candidato_id[]"]').forEach(function (checkbox) {
        checkbox.checked = false;
        toggleVinculo(checkbox.id.replace('cand', ''));
    });
}
</script>
