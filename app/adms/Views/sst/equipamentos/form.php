<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamentos_form');
$action = $isEdit ? 'sst-update-equipamento/' . (int)$item['id'] : 'sst-create-equipamento';
$periodicidades = $this->data['periodicidades'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-fire-extinguisher me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> equipamento</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <h6 class="text-muted text-uppercase small mb-3">Identificação</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="adms_sst_equipamento_tipo_id">Grupo *</label>
                        <select name="adms_sst_equipamento_tipo_id" id="adms_sst_equipamento_tipo_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['tipos'] ?? [] as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"
                                data-prefixo="<?= htmlspecialchars((string)($t['prefixo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-controla-recarga="<?= !empty($t['controla_recarga']) ? '1' : '0' ?>"
                                data-validade-meses="<?= (int)($t['validade_recarga_meses'] ?? 12) ?>"
                                <?= (int)($item['adms_sst_equipamento_tipo_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nome']) ?><?= !empty($t['prefixo']) ? ' (' . htmlspecialchars((string)$t['prefixo']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="empresa_contratante">Empresa (site) *</label>
                        <select name="empresa_contratante" id="empresa_contratante" class="form-select" required>
                            <?php
                            $empVal = (string)($item['empresa_contratante'] ?? '');
                            $empSelected = \App\adms\Helpers\SstEquipamentoSiteHelper::normalize($empVal) ?? $empVal;
                            $sites = $this->data['empresas_contratantes'] ?? \App\adms\Helpers\SstEquipamentoSiteHelper::options();
                            if ($empVal !== '' && $empSelected !== '' && !isset($sites[$empSelected])) {
                                $sites[$empVal] = \App\adms\Helpers\SstEquipamentoSiteHelper::label($empVal) . ' (fora da lista — escolha um site)';
                                $empSelected = $empVal;
                            }
                            ?>
                            <option value="" <?= $empSelected === '' ? 'selected' : '' ?>>Selecione</option>
                            <?php foreach ($sites as $slug => $empLabel): ?>
                            <option value="<?= htmlspecialchars((string)$slug) ?>" <?= $empSelected === (string)$slug ? 'selected' : '' ?>><?= htmlspecialchars((string)$empLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Define o contador do código. Compõe a localização com a sala/área.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="codigo">Código *</label>
                        <?php if ($isEdit): ?>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" readonly>
                        <div class="form-text">Gerado no cadastro — não pode ser alterado.</div>
                        <?php else: ?>
                        <input type="text" id="codigo" class="form-control text-uppercase bg-light" value="" readonly placeholder="Selecione o grupo e o site">
                        <input type="hidden" name="codigo" value="">
                        <div class="form-text" id="codigo_hint">Gerado automaticamente (prefixo + 5 dígitos, por site).</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="patrimonio">Patrimônio</label>
                        <input type="text" name="patrimonio" id="patrimonio" class="form-control" value="<?= htmlspecialchars($item['patrimonio'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach (['Ativo','Inativo','Baixado','Bloqueado'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($item['status'] ?? 'Ativo') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="localizacao">Localização (sala / área)</label>
                        <input type="text" name="localizacao" id="localizacao" class="form-control" value="<?= htmlspecialchars($item['localizacao'] ?? '') ?>" placeholder="Ex.: SALA TI">
                        <div class="form-text">Exibida junto com o site (ex.: Laboratório Tiaraju — SALA TI).</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="adms_department_id">Departamento</label>
                        <select name="adms_department_id" id="adms_department_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (int)($item['adms_department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="responsavel_adms_user_id">Responsável</label>
                        <select name="responsavel_adms_user_id" id="responsavel_adms_user_id" class="form-select">
                            <option value="">— Nenhum (fila geral) —</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (int)($item['responsavel_adms_user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <h6 class="text-muted text-uppercase small mb-3 mt-2">Características</h6>
                <div class="row">
                    <div class="col-md-3 mb-3"><label class="form-label" for="fabricante">Fabricante</label><input type="text" name="fabricante" id="fabricante" class="form-control" value="<?= htmlspecialchars($item['fabricante'] ?? '') ?>"></div>
                    <?php
                    $agentes = $this->data['agentes_extintor'] ?? [];
                    $caps = $this->data['capacidades_comuns'] ?? [];
                    $modeloAtual = trim((string)($item['modelo'] ?? ''));
                    $capAtual = trim((string)($item['capacidade'] ?? ''));
                    $agenteConhecido = $modeloAtual !== '' && in_array($modeloAtual, $agentes, true);
                    $capConhecida = $capAtual !== '' && in_array($capAtual, $caps, true);
                    $agenteSelect = $agenteConhecido ? $modeloAtual : ($modeloAtual !== '' ? 'Outro' : '');
                    $capSelect = $capConhecida ? $capAtual : ($capAtual !== '' ? 'Outro' : '');
                    ?>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="modelo_select">Tipo (agente)</label>
                        <select id="modelo_select" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($agentes as $ag): ?>
                            <option value="<?= htmlspecialchars($ag) ?>" <?= $agenteSelect === $ag ? 'selected' : '' ?>><?= htmlspecialchars($ag) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="modelo" id="modelo" class="form-control mt-2<?= $agenteSelect === 'Outro' ? '' : ' d-none' ?>"
                               value="<?= htmlspecialchars($agenteSelect === 'Outro' ? $modeloAtual : ($agenteConhecido ? $modeloAtual : '')) ?>"
                               placeholder="Descreva o tipo / agente">
                        <div class="form-text">Ex.: Pó ABC, CO₂, Água. Use “Outro” se não estiver na lista.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="capacidade_select">Capacidade</label>
                        <select id="capacidade_select" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($caps as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $capSelect === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="capacidade" id="capacidade" class="form-control mt-2<?= $capSelect === 'Outro' ? '' : ' d-none' ?>"
                               value="<?= htmlspecialchars($capSelect === 'Outro' ? $capAtual : ($capConhecida ? $capAtual : '')) ?>"
                               placeholder="Ex.: 6 kg, 10 L">
                        <div class="form-text">Carga nominal (kg) ou litragem (L).</div>
                    </div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="numero_serie">Nº série</label><input type="text" name="numero_serie" id="numero_serie" class="form-control" value="<?= htmlspecialchars($item['numero_serie'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="data_fabricacao">Data fabricação</label><input type="date" name="data_fabricacao" id="data_fabricacao" class="form-control" value="<?= htmlspecialchars($item['data_fabricacao'] ?? '') ?>"></div>
                </div>
                <div class="row" id="bloco_recarga" style="display:none;">
                    <div class="col-12"><h6 class="text-muted text-uppercase small mb-3 mt-1">Recarga / validade de carga</h6></div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_recarga">Data recarga</label>
                        <input type="date" name="data_recarga" id="data_recarga" class="form-control" value="<?= htmlspecialchars($item['data_recarga'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_proxima_recarga">Próxima recarga</label>
                        <input type="date" name="data_proxima_recarga" id="data_proxima_recarga" class="form-control" value="<?= htmlspecialchars($item['data_proxima_recarga'] ?? '') ?>">
                        <div class="form-text" id="hint_proxima_recarga">Calculada automaticamente pela validade do grupo (pode ajustar).</div>
                    </div>
                </div>
                <h6 class="text-muted text-uppercase small mb-3 mt-2">Vistorias</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="periodicidade_meses">Periodicidade *</label>
                        <select name="periodicidade_meses" id="periodicidade_meses" class="form-select" required>
                            <?php foreach ($periodicidades as $meses => $label): ?>
                            <option value="<?= (int)$meses ?>" <?= (int)($item['periodicidade_meses'] ?? ($this->data['settings_defaults']['periodicidade_meses_padrao'] ?? 1)) === (int)$meses ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="dia_previsto_vistoria">Dia da vistoria</label>
                        <select name="dia_previsto_vistoria" id="dia_previsto_vistoria" class="form-select">
                            <option value="">Padrão do módulo</option>
                            <?php foreach ($this->data['dias'] ?? [] as $d => $label): ?>
                            <option value="<?= (int)$d ?>" <?= (int)($item['dia_previsto_vistoria'] ?? 0) === (int)$d ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Dia do mês em que o cron abre a vistoria e define o prazo.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_referencia_inspecao">Mês referência (1ª competência)</label>
                        <input type="date" name="data_referencia_inspecao" id="data_referencia_inspecao" class="form-control" value="<?= htmlspecialchars($item['data_referencia_inspecao'] ?? '') ?>">
                        <div class="form-text">Opcional. Se vazio, usa data de cadastro.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-check mt-4 pt-1">
                            <input class="form-check-input" type="checkbox" name="vistoria_automatica" id="vistoria_automatica" value="1"
                                <?= ($item['vistoria_automatica'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="vistoria_automatica">Gerar vistorias automaticamente</label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <p class="form-text mb-0">
                            Padrões globais em
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-equipamento-settings">Configurações de vistorias</a>.
                        </p>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var tipoSelect = document.getElementById('adms_sst_equipamento_tipo_id');
    var blocoRecarga = document.getElementById('bloco_recarga');
    var dataRecarga = document.getElementById('data_recarga');
    var dataProxima = document.getElementById('data_proxima_recarga');
    var hintProxima = document.getElementById('hint_proxima_recarga');
    var codigo = document.getElementById('codigo');
    var hint = document.getElementById('codigo_hint');
    var siteSelect = document.getElementById('empresa_contratante');
    var previews = <?= json_encode($this->data['codigo_previews'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    var proximaManual = false;

    function validadeMeses() {
        if (!tipoSelect) return 12;
        var opt = tipoSelect.options[tipoSelect.selectedIndex];
        var m = opt ? parseInt(opt.getAttribute('data-validade-meses') || '12', 10) : 12;
        return m > 0 ? m : 12;
    }

    function addMonths(ymd, months) {
        var parts = (ymd || '').split('-');
        if (parts.length !== 3) return '';
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(d.getTime())) return '';
        var day = d.getDate();
        d.setMonth(d.getMonth() + months);
        // Ajuste fim de mês (ex.: 31/01 + 1 mês)
        if (d.getDate() < day) {
            d.setDate(0);
        }
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dd;
    }

    function calcProxima(force) {
        if (!dataRecarga || !dataProxima) return;
        if (!dataRecarga.value) return;
        if (proximaManual && !force) return;
        var meses = validadeMeses();
        dataProxima.value = addMonths(dataRecarga.value, meses);
        if (hintProxima) {
            hintProxima.textContent = 'Calculada: data da recarga + ' + meses + ' meses (validade do grupo). Pode ajustar.';
        }
    }

    function toggleRecarga() {
        if (!tipoSelect || !blocoRecarga) return;
        var opt = tipoSelect.options[tipoSelect.selectedIndex];
        var on = opt && opt.value && opt.getAttribute('data-controla-recarga') === '1';
        blocoRecarga.style.display = on ? '' : 'none';
        if (!on) {
            if (dataRecarga && !isEdit) dataRecarga.value = '';
            if (dataProxima && !isEdit) dataProxima.value = '';
            proximaManual = false;
        } else {
            calcProxima(false);
            if (hintProxima) {
                hintProxima.textContent = 'Calculada: data da recarga + ' + validadeMeses() + ' meses (validade do grupo). Pode ajustar.';
            }
        }
    }

    function refreshCodigo() {
        if (isEdit || !codigo || !tipoSelect) return;
        var opt = tipoSelect.options[tipoSelect.selectedIndex];
        var site = siteSelect ? siteSelect.value : '';
        if (!opt || !opt.value) {
            codigo.value = '';
            if (hint) hint.textContent = 'Gerado automaticamente (prefixo + 5 dígitos, por site).';
            return;
        }
        if (!site) {
            codigo.value = '';
            if (hint) hint.textContent = 'Selecione o site para ver o próximo código deste grupo.';
            return;
        }
        var byTipo = previews[opt.value] || {};
        var preview = byTipo[site] || '';
        var prefixo = (opt.getAttribute('data-prefixo') || '').toUpperCase();
        if (preview) {
            codigo.value = preview;
            if (hint) hint.textContent = 'Prévia do próximo código neste site (confirmado ao salvar).';
        } else if (prefixo.length === 3) {
            codigo.value = prefixo + '?????';
            if (hint) hint.textContent = 'O número sequencial deste site será definido ao salvar.';
        } else {
            codigo.value = '';
            if (hint) hint.textContent = 'Grupo sem prefixo válido. Cadastre o prefixo em Tipos de equipamento.';
        }
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', function () {
            proximaManual = false;
            refreshCodigo();
            toggleRecarga();
            calcProxima(true);
        });
        refreshCodigo();
        toggleRecarga();
    }
    if (siteSelect) {
        siteSelect.addEventListener('change', refreshCodigo);
    }
    if (dataRecarga) {
        dataRecarga.addEventListener('change', function () {
            proximaManual = false;
            calcProxima(true);
        });
    }
    if (dataProxima) {
        dataProxima.addEventListener('input', function () {
            proximaManual = true;
        });
    }

    // Tipo (agente) e Capacidade: select + campo “Outro”
    function wireSelectOutro(selectId, inputId) {
        var sel = document.getElementById(selectId);
        var inp = document.getElementById(inputId);
        if (!sel || !inp) return;
        function sync() {
            var v = sel.value;
            if (v === 'Outro') {
                inp.classList.remove('d-none');
                if (!inp.value || inp.dataset.fromSelect === '1') {
                    // mantém valor livre já digitado
                }
                inp.dataset.fromSelect = '0';
            } else if (v === '') {
                inp.classList.add('d-none');
                inp.value = '';
                inp.dataset.fromSelect = '1';
            } else {
                inp.classList.add('d-none');
                inp.value = v;
                inp.dataset.fromSelect = '1';
            }
        }
        sel.addEventListener('change', sync);
        // Ao carregar com valor conhecido no select, espelha no hidden/input
        if (sel.value && sel.value !== 'Outro') {
            inp.value = sel.value;
            inp.dataset.fromSelect = '1';
        }
        sync();
    }
    wireSelectOutro('modelo_select', 'modelo');
    wireSelectOutro('capacidade_select', 'capacidade');
})();
</script>
