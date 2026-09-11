<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEpiCategoriaHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_epis_form');
$action = $isEdit ? 'sst-update-epi/' . (int)$item['id'] : 'sst-create-epi';
$categoriaSelecionada = SstEpiCategoriaHelper::canonicalize((string) ($item['categoria'] ?? ''))
    ?? trim((string) ($item['categoria'] ?? ''));
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('EPI') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis"><?= htmlspecialchars('EPIs') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h6 class="text-muted text-uppercase small mb-3">Dados básicos</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="categoria">Categoria de proteção *</label>
                        <select name="categoria" id="categoria" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php
                            $catsForm = SstEpiCategoriaHelper::all();
                            if ($categoriaSelecionada !== '' && !in_array($categoriaSelecionada, $catsForm, true)) {
                                $catsForm[] = $categoriaSelecionada;
                            }
                            foreach ($catsForm as $cat):
                            ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoriaSelecionada === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                        <div class="form-text">Detalhes técnicos do item. O Nº CA é informado nas movimentações de estoque.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="imagem">Foto do EPI</label>
                        <?php
                        $fotoUrl = \App\adms\Helpers\SstEpiImagemHelper::url($item['imagem'] ?? null);
                        if ($fotoUrl !== ''):
                        ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="Foto atual" class="img-thumbnail sst-epi-foto-preview">
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remover_imagem" value="1" id="remover_imagem">
                            <label class="form-check-label" for="remover_imagem">Remover foto atual</label>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="imagem" id="imagem" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">JPG, PNG, GIF ou WEBP. Máximo 5 MB. Aparece na listagem e na ficha do item.</div>
                        <div id="epiImagemPreview" class="mt-2 d-none">
                            <img alt="Prévia" class="img-thumbnail sst-epi-foto-preview">
                        </div>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3 mt-2">Estoque e controle</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="estoque_minimo" id="labelEstoqueMinimo">Estoque mínimo (alerta de compra)</label>
                        <input type="number" name="estoque_minimo" id="estoque_minimo" class="form-control" min="0" value="<?= htmlspecialchars((string)($item['estoque_minimo'] ?? '')) ?>">
                        <div class="form-text" id="hintEstoqueMinimo">Saldo total do EPI. Com grade, este valor vira o mínimo padrão de cada tamanho.</div>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Saldo atual</label>
                        <input type="text" class="form-control" value="<?= (int)($item['estoque_atual'] ?? 0) ?>" readonly disabled>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?adms_sst_epi_id=<?= (int)$item['id'] ?>&tipo=Entrada" class="btn btn-sm btn-outline-success mt-1">Registrar entrada</a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="periodicidade_troca_dias">Vida útil padrão (dias)</label>
                        <input type="number" name="periodicidade_troca_dias" id="periodicidade_troca_dias" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['periodicidade_troca_dias'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="grade_preset">Tamanho / numeração</label>
                        <?php
                        $gradeAtual = \App\adms\Helpers\SstEpiTamanhoHelper::parseGrade((string) ($item['grade_tamanhos'] ?? ''));
                        $presetAtual = !empty($item['controla_tamanho'])
                            ? \App\adms\Helpers\SstEpiTamanhoHelper::detectPreset($gradeAtual)
                            : \App\adms\Helpers\SstEpiTamanhoHelper::PRESET_NENHUM;
                        ?>
                        <select name="grade_preset" id="grade_preset" class="form-select">
                            <?php foreach (\App\adms\Helpers\SstEpiTamanhoHelper::presetLabels() as $key => $lab): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $presetAtual === $key ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Calçado e uniforme: um cadastro só; o tamanho entra na movimentação e na ficha.</div>
                    </div>
                    <div class="col-md-8 mb-3 <?= $presetAtual === \App\adms\Helpers\SstEpiTamanhoHelper::PRESET_PERSONALIZADA ? '' : 'd-none' ?>" id="wrapGradePersonalizada">
                        <label class="form-label" for="grade_tamanhos">Grade personalizada</label>
                        <input type="text" name="grade_tamanhos" id="grade_tamanhos" class="form-control"
                            value="<?= $presetAtual === \App\adms\Helpers\SstEpiTamanhoHelper::PRESET_PERSONALIZADA ? htmlspecialchars((string) ($item['grade_tamanhos'] ?? '')) : '' ?>"
                            placeholder="Ex.: 36, 37, 38, 39, 40">
                        <div class="form-text">Separe por vírgula. Use o mesmo código na entrada de estoque (38, GG…).</div>
                    </div>
                    <div class="col-12 mb-3 d-none" id="wrapMinTamanhos">
                        <label class="form-label">Mínimo por numeração</label>
                        <p class="form-text mb-2">Deixe vazio para usar o padrão acima. Ex.: padrão 3 em todos; nº 38 = 8.</p>
                        <div id="gridMinTamanhos" class="row g-2"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const sel = document.getElementById('grade_preset');
    const wrap = document.getElementById('wrapGradePersonalizada');
    const wrapMin = document.getElementById('wrapMinTamanhos');
    const grid = document.getElementById('gridMinTamanhos');
    const gradeInput = document.getElementById('grade_tamanhos');
    const labelMin = document.getElementById('labelEstoqueMinimo');
    const hintMin = document.getElementById('hintEstoqueMinimo');
    const GRADES = {
        calcado: <?= json_encode(\App\adms\Helpers\SstEpiTamanhoHelper::calcado(), JSON_UNESCAPED_UNICODE) ?>,
        vestuario: <?= json_encode(\App\adms\Helpers\SstEpiTamanhoHelper::vestuario(), JSON_UNESCAPED_UNICODE) ?>
    };
    const OVERRIDES = <?= json_encode($this->data['minimos_tamanho'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    if (!sel || !wrap) return;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }
    function parsePersonalizada(raw) {
        return String(raw || '').split(/[,;|\n\r\/]+/).map(function (p) {
            return p.replace(/^(n[ºo°.\s]+|tam(anho)?[.\s:]*)/i, '').replace(/\s+/g, '').toUpperCase();
        }).filter(function (p, i, arr) { return p && arr.indexOf(p) === i; });
    }
    function currentGrade() {
        if (sel.value === 'calcado') return GRADES.calcado.slice();
        if (sel.value === 'vestuario') return GRADES.vestuario.slice();
        if (sel.value === 'personalizada') return parsePersonalizada(gradeInput ? gradeInput.value : '');
        return [];
    }
    function currentValues() {
        const map = Object.assign({}, OVERRIDES);
        if (!grid) return map;
        grid.querySelectorAll('input[data-tam]').forEach(function (inp) {
            map[inp.getAttribute('data-tam')] = inp.value;
        });
        return map;
    }
    function renderMinimos() {
        if (!wrapMin || !grid) return;
        const grade = currentGrade();
        const comGrade = grade.length > 0;
        wrapMin.classList.toggle('d-none', !comGrade);
        if (labelMin) {
            labelMin.textContent = comGrade ? 'Mínimo padrão por tamanho' : 'Estoque mínimo (alerta de compra)';
        }
        if (hintMin) {
            hintMin.textContent = comGrade
                ? 'Ex.: 3 = alerta se qualquer número ficar com 3 ou menos. Números específicos na grade abaixo.'
                : 'Saldo total do EPI. Sem grade, o alerta usa só este total.';
        }
        if (!comGrade) {
            grid.innerHTML = '';
            return;
        }
        const vals = currentValues();
        grid.innerHTML = grade.map(function (tam) {
            const v = vals[tam] !== undefined && vals[tam] !== null ? String(vals[tam]) : '';
            return '<div class="col-4 col-sm-3 col-md-2 col-xl-1">' +
                '<label class="form-label small mb-0">' + esc(tam) + '</label>' +
                '<input type="number" min="0" class="form-control form-control-sm" name="min_tamanho[' + esc(tam) + ']" data-tam="' + esc(tam) + '" value="' + esc(v) + '" placeholder="padr.">' +
                '</div>';
        }).join('');
    }
    function sync() {
        wrap.classList.toggle('d-none', sel.value !== 'personalizada');
        renderMinimos();
    }
    sel.addEventListener('change', sync);
    if (gradeInput) gradeInput.addEventListener('input', renderMinimos);
    sync();
})();
(function () {
    const input = document.getElementById('imagem');
    const wrap = document.getElementById('epiImagemPreview');
    if (!input || !wrap) return;
    const img = wrap.querySelector('img');
    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file || !img) {
            wrap.classList.add('d-none');
            return;
        }
        img.src = URL.createObjectURL(file);
        wrap.classList.remove('d-none');
    });
})();
</script>
