<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'] ?? [];
$epis = $this->data['epis'] ?? [];
$epiLocked = !empty($this->data['epi_locked']);
$epiLockedItem = $this->data['epi_locked_item'] ?? null;
$multiMode = !empty($this->data['multi_mode']);
$saldoAtual = $this->data['saldo_atual'] ?? null;
$casEstoqueJson = $this->data['cas_estoque_por_epi_json'] ?? '{}';
$motivosSaida = $this->data['motivos_saida'] ?? [];
$motivosAjuste = $this->data['motivos_ajuste'] ?? [];
$csrf = CSRFHelper::generateCSRFToken('sst_epi_movimento_form');
$tipo = (string)($item['tipo_movimento'] ?? 'Entrada');
$epiLockedId = $epiLocked && $epiLockedItem ? (int)($epiLockedItem['id'] ?? 0) : 0;
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-dolly me-2"></i>Movimentação de estoque EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos">Movimentações EPI</a></li>
            <li class="breadcrumb-item active">Nova movimentação</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento" id="formMov">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <?php if ($epiLocked): ?>
                <input type="hidden" name="epi_locked_id" value="<?= $epiLockedId ?>">
                <input type="hidden" name="adms_sst_epi_id" value="<?= $epiLockedId ?>">
                <?php endif; ?>

                <?php if ($multiMode): ?>
                <h6 class="text-muted text-uppercase small mb-3">Dados gerais</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_movimento" id="tipoMov" class="form-select" required>
                            <?php foreach (['Entrada', 'Saída', 'Devolução'] as $t): ?>
                            <option value="<?= $t ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_movimento" class="form-control" value="<?= htmlspecialchars($item['data_movimento'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Documento (NF, pedido…)</label>
                        <input type="text" name="documento_ref" class="form-control" placeholder="Opcional — vale para todos os itens">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações gerais</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Opcional"></textarea>
                    </div>
                    <div class="col-md-4 d-none" id="wrapMotivoMulti">
                        <label class="form-label">Motivo *</label>
                        <select name="motivo" id="motivoMulti" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($motivosSaida as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 d-none" id="wrapJustMulti">
                        <label class="form-label">Justificativa *</label>
                        <textarea name="justificativa" id="justMulti" class="form-control" rows="2" placeholder="Obrigatório para Saída (ex.: CA vencido em ...)"></textarea>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3">Itens da movimentação</h6>
                <div id="itensContainer">
                    <div class="row g-2 align-items-end item-row mb-2 border-bottom pb-3">
                        <div class="col-md-3">
                            <label class="form-label">EPI *</label>
                            <select name="itens[0][adms_sst_epi_id]" class="form-select epi-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($epis as $ep): ?>
                                <option value="<?= (int)$ep['id'] ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?> (saldo: <?= (int)($ep['estoque_atual'] ?? 0) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Qtd *</label>
                            <input type="number" name="itens[0][quantidade]" class="form-control qty-input" value="1" min="1" required>
                        </div>
                        <div class="col-md-1 wrap-tamanho">
                            <label class="form-label">Tam.</label>
                            <select name="itens[0][tamanho]" class="form-select tamanho-select form-select-sm"></select>
                        </div>
                        <div class="col-md-2 valor-field-wrap">
                            <label class="form-label valor-label">Valor unit. R$ *</label>
                            <input type="text" name="itens[0][valor_unitario]" class="form-control valor-unit-input" inputmode="decimal" placeholder="0,00">
                        </div>
                        <div class="col-md-2 valor-field-wrap">
                            <label class="form-label">Total R$</label>
                            <input type="text" class="form-control valor-total-input" readonly tabindex="-1" placeholder="0,00">
                        </div>
                        <div class="col-md-2 ca-field-wrap">
                            <label class="form-label ca-label">Nº CA *</label>
                            <select class="form-select ca-select d-none" data-name="itens[0][ca_numero]"></select>
                            <input type="text" name="itens[0][ca_numero]" class="form-control ca-input text-uppercase" maxlength="50" placeholder="Informe o CA">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Validade</label>
                            <input type="date" name="itens[0][ca_validade]" class="form-control ca-validade-input">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row d-none" title="Remover"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="btnAddItem"><i class="fas fa-plus me-1"></i> Adicionar EPI</button>

                <?php else: ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">EPI *</label>
                        <?php if ($epiLocked && $epiLockedItem): ?>
                        <input type="text" class="form-control epi-readonly" data-epi-id="<?= $epiLockedId ?>" value="<?= htmlspecialchars($epiLockedItem['nome'] ?? '') ?> (saldo: <?= (int)$saldoAtual ?>)" readonly disabled>
                        <div class="form-text">Movimentação vinculada a este EPI. Para registrar vários itens, use <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento">Movimentações EPI</a>.</div>
                        <?php else: ?>
                        <select name="adms_sst_epi_id" id="epiSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($epis as $ep): ?>
                            <option value="<?= (int)$ep['id'] ?>" <?= ((int)($item['adms_sst_epi_id'] ?? 0) === (int)$ep['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ep['nome'] ?? '') ?> (saldo: <?= (int)($ep['estoque_atual'] ?? 0) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        <?php if ($epiLocked && $saldoAtual !== null): ?>
                        <div class="form-text">Saldo atual: <strong><?= (int)$saldoAtual ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_movimento" id="tipoMov" class="form-select" required>
                            <?php foreach (['Entrada', 'Saída', 'Devolução', 'Ajuste'] as $t): ?>
                            <option value="<?= $t ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="wrapQty">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" name="quantidade" id="qtyInput" class="form-control qty-input" min="1" value="<?= (int)($item['quantidade'] ?? 1) ?>" required>
                    </div>
                    <div class="col-md-2 d-none" id="wrapTamanho">
                        <label class="form-label" for="tamanhoSelect">Tamanho / nº *</label>
                        <select name="tamanho" id="tamanhoSelect" class="form-select tamanho-select"></select>
                    </div>
                    <div class="col-md-3 valor-field-wrap" id="wrapValorUnit">
                        <label class="form-label valor-label" for="valorUnitario">Valor unitário R$ *</label>
                        <input type="text" name="valor_unitario" id="valorUnitario" class="form-control valor-unit-input" inputmode="decimal" placeholder="0,00" value="<?= htmlspecialchars((string)($item['valor_unitario'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3 valor-field-wrap" id="wrapValorTotal">
                        <label class="form-label" for="valorTotal">Total R$</label>
                        <input type="text" id="valorTotal" class="form-control valor-total-input" readonly tabindex="-1" placeholder="0,00">
                    </div>
                    <div class="col-md-4 d-none" id="wrapSaldoNovo">
                        <label class="form-label">Saldo contado (inventário) *</label>
                        <input type="number" name="saldo_novo" class="form-control" min="0" value="<?= $saldoAtual !== null ? (int)$saldoAtual : '' ?>">
                    </div>
                    <div class="col-md-3" id="wrapData">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_movimento" class="form-control" value="<?= htmlspecialchars($item['data_movimento'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4 ca-field-wrap" id="wrapCa">
                        <label class="form-label ca-label" for="caNumero">Nº CA *</label>
                        <select id="caSelect" class="form-select ca-select d-none"></select>
                        <input type="text" name="ca_numero" id="caNumero" class="form-control ca-input text-uppercase" maxlength="50" placeholder="Informe o CA">
                    </div>
                    <div class="col-md-4" id="wrapCaVal">
                        <label class="form-label">Validade CA</label>
                        <input type="date" name="ca_validade" id="caValidade" class="form-control ca-validade-input">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Documento (NF, pedido…)</label>
                        <input type="text" name="documento_ref" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-4 d-none" id="wrapMotivo">
                        <label class="form-label" for="motivoMov">Motivo *</label>
                        <select name="motivo" id="motivoMov" class="form-select">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-none" id="wrapJustificativa">
                        <label class="form-label" for="justificativaMov">Justificativa *</label>
                        <textarea name="justificativa" id="justificativaMov" class="form-control" rows="2" placeholder="Descreva o motivo (obrigatório para Saída e Ajuste)"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <?php endif; ?>

                <div class="alert alert-info small mt-3 mb-0" id="caHint">
                    <strong>Entrada (EM):</strong> valor unitário obrigatório; atualiza a <em>média do CA</em>.
                    <strong>Saída (SM):</strong> baixa sem ficha (vencimento, descarte…); usa média do CA; exige motivo e justificativa.
                    <strong>Entrega (ET):</strong> pela ficha; usa média do CA.
                    <strong>Ajuste (AS):</strong> inventário (±); exige motivo e justificativa.
                    DOCNUM gerado automaticamente (EM/SM/ET/DS/AS).
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Registrar<?= $multiMode ? ' tudo' : '' ?></button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const casEstoquePorEpi = <?= $casEstoqueJson ?>;
    const multiMode = <?= $multiMode ? 'true' : 'false' ?>;
    const epiLockedId = <?= (int)$epiLockedId ?>;
    const motivosSaida = <?= json_encode(array_values($motivosSaida), JSON_UNESCAPED_UNICODE) ?>;
    const motivosAjuste = <?= json_encode(array_values($motivosAjuste), JSON_UNESCAPED_UNICODE) ?>;

    function usaSelectCa(tipo) {
        return tipo === 'Saída';
    }

    function metaEpi(epiId) {
        const raw = casEstoquePorEpi[epiId];
        if (!raw) return {controla_tamanho: false, grade: [], lotes: [], cas: []};
        if (Array.isArray(raw)) return {controla_tamanho: false, grade: [], lotes: [], cas: raw};
        return raw;
    }

    function popularTamanho(select, epiId, tipo, keep) {
        if (!select) return;
        const meta = metaEpi(epiId);
        const prev = keep || select.value || '';
        select.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'Selecione...';
        select.appendChild(ph);
        if (!epiId || !meta.controla_tamanho) {
            select.required = false;
            return;
        }
        select.required = true;
        const values = {};
        if (tipo === 'Entrada' || tipo === 'Ajuste') {
            (meta.grade || []).forEach(function (t) { values[t] = true; });
        }
        (meta.lotes || []).forEach(function (l) {
            if (l.tamanho) values[l.tamanho] = true;
        });
        Object.keys(values).forEach(function (t) {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (prev === t) opt.selected = true;
            select.appendChild(opt);
        });
        if (!prev && select.options.length === 2) select.selectedIndex = 1;
    }

    function toggleTamanhoWrap(wrap, epiId) {
        if (!wrap) return;
        const meta = metaEpi(epiId);
        wrap.classList.toggle('d-none', !meta.controla_tamanho);
        const sel = wrap.querySelector('.tamanho-select') || wrap;
        if (sel && sel.tagName === 'SELECT') sel.required = !!meta.controla_tamanho;
    }

    function fillMotivoSelect(select, tipo) {
        if (!select) return;
        const list = tipo === 'Ajuste' ? motivosAjuste : motivosSaida;
        const current = select.value;
        select.innerHTML = '<option value="">Selecione...</option>';
        list.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            if (m === current) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function syncJustificativaMulti(tipo) {
        const wrapM = document.getElementById('wrapMotivoMulti');
        const wrapJ = document.getElementById('wrapJustMulti');
        const sel = document.getElementById('motivoMulti');
        const just = document.getElementById('justMulti');
        const show = tipo === 'Saída';
        if (wrapM) wrapM.classList.toggle('d-none', !show);
        if (wrapJ) wrapJ.classList.toggle('d-none', !show);
        if (sel) {
            sel.required = show;
            if (show) fillMotivoSelect(sel, 'Saída');
            else sel.value = '';
        }
        if (just) {
            just.required = show;
            if (!show) just.value = '';
        }
    }

    function syncJustificativaSingle(tipo) {
        const wrapM = document.getElementById('wrapMotivo');
        const wrapJ = document.getElementById('wrapJustificativa');
        const sel = document.getElementById('motivoMov');
        const just = document.getElementById('justificativaMov');
        const show = tipo === 'Saída' || tipo === 'Ajuste';
        if (wrapM) wrapM.classList.toggle('d-none', !show);
        if (wrapJ) wrapJ.classList.toggle('d-none', !show);
        if (sel) {
            sel.required = show;
            if (show) fillMotivoSelect(sel, tipo);
            else sel.value = '';
        }
        if (just) {
            just.required = show;
            if (!show) just.value = '';
        }
    }

    function setValorReadonly(scope, readonly) {
        const unitEl = scope.querySelector('.valor-unit-input');
        if (!unitEl) return;
        unitEl.readOnly = !!readonly;
        if (readonly) {
            unitEl.classList.add('bg-light');
            unitEl.title = 'Custo médio do CA (calculado automaticamente)';
        } else {
            unitEl.classList.remove('bg-light');
            unitEl.title = '';
        }
    }

    function parseMoneyBr(raw) {
        if (raw === null || raw === undefined) return null;
        let s = String(raw).trim().replace(/[^\d,.\-]/g, '');
        if (!s) return null;
        if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else if (s.indexOf(',') >= 0) {
            s = s.replace(',', '.');
        }
        const n = parseFloat(s);
        return isNaN(n) ? null : Math.round(n * 100) / 100;
    }

    function formatMoneyBr(n) {
        if (n === null || n === undefined || isNaN(n)) return '';
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calcTotalInScope(scope) {
        const qtyEl = scope.querySelector('.qty-input') || scope.querySelector('#qtyInput');
        const unitEl = scope.querySelector('.valor-unit-input');
        const totalEl = scope.querySelector('.valor-total-input');
        if (!unitEl || !totalEl) return;
        const qty = parseInt(qtyEl && qtyEl.value ? qtyEl.value : '0', 10) || 0;
        const unit = parseMoneyBr(unitEl.value);
        totalEl.value = (unit !== null && qty > 0) ? formatMoneyBr(unit * qty) : '';
    }

    function bindValorCalc(scope) {
        const qtyEl = scope.querySelector('.qty-input') || scope.querySelector('#qtyInput');
        const unitEl = scope.querySelector('.valor-unit-input');
        if (qtyEl && !qtyEl.dataset.valorBound) {
            qtyEl.addEventListener('input', function () { calcTotalInScope(scope); });
            qtyEl.dataset.valorBound = '1';
        }
        if (unitEl && !unitEl.dataset.valorBound) {
            unitEl.addEventListener('input', function () { calcTotalInScope(scope); });
            unitEl.dataset.valorBound = '1';
        }
        calcTotalInScope(scope);
    }

    function syncValorRequired(tipo, scope) {
        const wraps = scope.querySelectorAll('.valor-field-wrap');
        const unitEl = scope.querySelector('.valor-unit-input');
        const label = scope.querySelector('.valor-label');
        const isAjuste = tipo === 'Ajuste';
        wraps.forEach(function (w) { w.classList.toggle('d-none', isAjuste); });
        if (!unitEl) return;
        if (isAjuste) {
            unitEl.required = false;
            unitEl.value = '';
            const totalEl = scope.querySelector('.valor-total-input');
            if (totalEl) totalEl.value = '';
            return;
        }
        const exigido = tipo === 'Entrada';
        unitEl.required = exigido;
        if (label) {
            label.textContent = exigido ? 'Valor unitário R$ *' : 'Valor unitário R$';
        }
        if (tipo === 'Entrada' && unitEl.placeholder === undefined) {
            unitEl.placeholder = '0,00';
        }
    }

    function getEpiIdFromRow(row) {
        const sel = row ? row.querySelector('.epi-select') : null;
        if (sel && sel.value) return sel.value;
        const epiSel = document.getElementById('epiSelect');
        if (epiSel && epiSel.value) return epiSel.value;
        if (epiLockedId > 0) return String(epiLockedId);
        const ro = document.querySelector('.epi-readonly');
        if (ro && ro.dataset.epiId) return ro.dataset.epiId;
        return '';
    }

    function popularSelectCa(select, epiId, selected, tamanhoFiltro) {
        select.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecione CA em estoque...';
        select.appendChild(placeholder);
        const meta = metaEpi(epiId);
        let lotes = meta.controla_tamanho ? (meta.lotes || []) : (meta.cas || []);
        if (meta.controla_tamanho && tamanhoFiltro) {
            lotes = lotes.filter(function (l) { return String(l.tamanho || '') === String(tamanhoFiltro); });
        }
        lotes.forEach(function (l) {
            const opt = document.createElement('option');
            opt.value = l.ca_numero;
            let label = l.ca_numero + ' (saldo: ' + l.saldo + ')';
            if (l.tamanho) label = l.tamanho + ' · ' + label;
            opt.textContent = label;
            opt.dataset.validade = l.ca_validade || '';
            opt.dataset.saldo = String(l.saldo);
            if (l.valor_unitario !== null && l.valor_unitario !== undefined) {
                opt.dataset.valor = String(l.valor_unitario);
            }
            if (selected && selected === l.ca_numero) opt.selected = true;
            select.appendChild(opt);
        });
        if (!selected && lotes.length === 1) {
            select.selectedIndex = 1;
        }
    }

    function syncCaField(wrap, tipo, epiId, keepValue) {
        const select = wrap.querySelector('.ca-select');
        const input = wrap.querySelector('.ca-input');
        const validade = wrap.querySelector('.ca-validade-input');
        const label = wrap.querySelector('.ca-label');
        if (!select || !input) return;

        if (usaSelectCa(tipo)) {
            const tamSel = wrap.closest('.item-row')?.querySelector('.tamanho-select')
                || document.getElementById('tamanhoSelect');
            popularSelectCa(select, epiId, keepValue || input.value || select.value, tamSel ? tamSel.value : '');
            select.classList.remove('d-none');
            input.classList.add('d-none');
            input.removeAttribute('name');
            input.removeAttribute('required');
            const fieldName = select.dataset.name || 'ca_numero';
            select.setAttribute('name', fieldName);
            select.required = true;
            if (label) label.textContent = 'CA em estoque *';
            if (select.value && validade) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.validade) {
                    validade.value = opt.dataset.validade;
                    validade.readOnly = true;
                }
            }
        } else {
            select.classList.add('d-none');
            select.removeAttribute('name');
            select.removeAttribute('required');
            input.classList.remove('d-none');
            const fieldName = select.dataset.name || 'ca_numero';
            input.setAttribute('name', fieldName);
            input.required = tipo !== 'Ajuste';
            if (label) label.textContent = 'Nº CA *';
            if (validade) {
                validade.readOnly = false;
            }
        }
    }

    function onCaSelectChange(select) {
        const wrap = select.closest('.ca-field-wrap');
        const row = wrap ? wrap.closest('.item-row') : null;
        const scope = row || document.getElementById('formMov');
        const validade = wrap ? wrap.querySelector('.ca-validade-input') : document.getElementById('caValidade');
        const qty = scope.querySelector('.qty-input') || document.getElementById('qtyInput');
        const unitEl = scope.querySelector('.valor-unit-input');
        const opt = select.options[select.selectedIndex];
        if (validade && opt && opt.dataset.validade) {
            validade.value = opt.dataset.validade;
            validade.readOnly = true;
        }
        if (qty && opt && opt.dataset.saldo) {
            qty.max = opt.dataset.saldo;
        }
        if (unitEl && opt && opt.dataset.valor) {
            unitEl.value = formatMoneyBr(parseFloat(opt.dataset.valor));
            calcTotalInScope(scope);
        }
    }

    function bindCaSelect(select) {
        select.addEventListener('change', function () { onCaSelectChange(select); });
    }

    if (multiMode) {
        let idx = 1;
        const container = document.getElementById('itensContainer');
        const tipoMov = document.getElementById('tipoMov');
        const tpl = container.querySelector('.item-row').outerHTML;

        function refreshRow(row) {
            const tipo = tipoMov.value;
            const epiId = getEpiIdFromRow(row);
            const wrap = row.querySelector('.ca-field-wrap');
            const input = row.querySelector('.ca-input');
            syncCaField(wrap, tipo, epiId, input ? input.value : '');
            const wrapTam = row.querySelector('.wrap-tamanho');
            const tamSel = row.querySelector('.tamanho-select');
            toggleTamanhoWrap(wrapTam, epiId);
            popularTamanho(tamSel, epiId, tipo, tamSel ? tamSel.value : '');
            if (tamSel && !tamSel.dataset.bound) {
                tamSel.addEventListener('change', function () { refreshRow(row); });
                tamSel.dataset.bound = '1';
            }
            syncValorRequired(tipo, row);
            setValorReadonly(row, tipo === 'Saída' || tipo === 'Devolução');
            bindValorCalc(row);
            const sel = row.querySelector('.ca-select');
            if (sel && !sel.dataset.bound) {
                bindCaSelect(sel);
                sel.dataset.bound = '1';
            }
            if (sel && sel.value) onCaSelectChange(sel);
        }

        function bindRow(row) {
            const sel = row.querySelector('.epi-select');
            if (sel) sel.addEventListener('change', function () { refreshRow(row); });
            const rm = row.querySelector('.remove-row');
            if (rm) rm.addEventListener('click', function () { row.remove(); });
            refreshRow(row);
        }

        tipoMov.addEventListener('change', function () {
            syncJustificativaMulti(tipoMov.value);
            container.querySelectorAll('.item-row').forEach(refreshRow);
        });

        document.getElementById('btnAddItem').addEventListener('click', function () {
            const div = document.createElement('div');
            div.innerHTML = tpl.replace(/itens\[0\]/g, 'itens[' + idx + ']');
            const row = div.firstElementChild;
            row.querySelector('.remove-row').classList.remove('d-none');
            row.querySelector('.epi-select').value = '';
            row.querySelector('.ca-input').value = '';
            const vu = row.querySelector('.valor-unit-input');
            if (vu) { vu.value = ''; delete vu.dataset.valorBound; }
            const vt = row.querySelector('.valor-total-input');
            if (vt) vt.value = '';
            const qty = row.querySelector('.qty-input');
            if (qty) delete qty.dataset.valorBound;
            const val = row.querySelector('.ca-validade-input');
            if (val) { val.value = ''; val.readOnly = false; }
            const sel = row.querySelector('.ca-select');
            if (sel) { sel.innerHTML = ''; sel.dataset.name = 'itens[' + idx + '][ca_numero]'; delete sel.dataset.bound; }
            container.appendChild(row);
            idx++;
            bindRow(row);
        });

        bindRow(container.querySelector('.item-row'));
        syncJustificativaMulti(tipoMov.value);
        return;
    }

    const tipo = document.getElementById('tipoMov');
    const wrapQty = document.getElementById('wrapQty');
    const wrapSaldo = document.getElementById('wrapSaldoNovo');
    const wrapCa = document.getElementById('wrapCa');
    const wrapCaVal = document.getElementById('wrapCaVal');
    const caHint = document.getElementById('caHint');
    const caInput = document.getElementById('caNumero');
    const caSelect = document.getElementById('caSelect');
    const qtyInput = document.getElementById('qtyInput');
    const epiSelect = document.getElementById('epiSelect');
    const formRoot = document.getElementById('formMov');

    if (caSelect) bindCaSelect(caSelect);
    bindValorCalc(formRoot);

    function refreshSingle() {
        const t = tipo.value;
        const isAjuste = t === 'Ajuste';
        const epiId = getEpiIdFromRow(null);
        const meta = metaEpi(epiId);
        const wrapTam = document.getElementById('wrapTamanho');
        const tamSel = document.getElementById('tamanhoSelect');
        toggleTamanhoWrap(wrapTam, epiId);
        popularTamanho(tamSel, epiId, t, tamSel ? tamSel.value : '');
        wrapSaldo.classList.toggle('d-none', !isAjuste);
        wrapQty.classList.toggle('d-none', isAjuste);
        const hideCa = isAjuste && !meta.controla_tamanho;
        wrapCa.classList.toggle('d-none', hideCa);
        wrapCaVal.classList.toggle('d-none', hideCa);
        if (caHint) caHint.classList.toggle('d-none', hideCa);
        qtyInput.required = !isAjuste;
        wrapSaldo.querySelector('input').required = isAjuste;
        syncValorRequired(t, formRoot);
        setValorReadonly(formRoot, t === 'Saída' || t === 'Devolução');
        syncJustificativaSingle(t);
        if (!hideCa) {
            const tipoCa = (t === 'Saída' || (isAjuste && meta.controla_tamanho)) ? 'Saída' : t;
            syncCaField(wrapCa, tipoCa, epiId, caInput.value || (caSelect ? caSelect.value : ''));
            if (caSelect && caSelect.value) onCaSelectChange(caSelect);
        }
        calcTotalInScope(formRoot);
    }

    tipo.addEventListener('change', refreshSingle);
    if (epiSelect) epiSelect.addEventListener('change', refreshSingle);
    const tamSelInit = document.getElementById('tamanhoSelect');
    if (tamSelInit) tamSelInit.addEventListener('change', refreshSingle);
    refreshSingle();
})();
</script>
