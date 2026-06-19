<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'] ?? [];
$epis = $this->data['epis'] ?? [];
$epiLocked = !empty($this->data['epi_locked']);
$epiLockedItem = $this->data['epi_locked_item'] ?? null;
$multiMode = !empty($this->data['multi_mode']);
$saldoAtual = $this->data['saldo_atual'] ?? null;
$casEstoqueJson = $this->data['cas_estoque_por_epi_json'] ?? '{}';
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
                </div>

                <h6 class="text-muted text-uppercase small mb-3">Itens da movimentação</h6>
                <div id="itensContainer">
                    <div class="row g-2 align-items-end item-row mb-2 border-bottom pb-3">
                        <div class="col-md-4">
                            <label class="form-label">EPI *</label>
                            <select name="itens[0][adms_sst_epi_id]" class="form-select epi-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($epis as $ep): ?>
                                <option value="<?= (int)$ep['id'] ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?> (saldo: <?= (int)($ep['estoque_atual'] ?? 0) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Qtd *</label>
                            <input type="number" name="itens[0][quantidade]" class="form-control qty-input" value="1" min="1" required>
                        </div>
                        <div class="col-md-2 ca-field-wrap">
                            <label class="form-label ca-label">Nº CA *</label>
                            <select class="form-select ca-select d-none" data-name="itens[0][ca_numero]"></select>
                            <input type="text" name="itens[0][ca_numero]" class="form-control ca-input text-uppercase" maxlength="50" placeholder="Informe o CA">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Validade CA</label>
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
                    <div class="col-md-4" id="wrapQty">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" name="quantidade" id="qtyInput" class="form-control" min="1" value="<?= (int)($item['quantidade'] ?? 1) ?>" required>
                    </div>
                    <div class="col-md-4 d-none" id="wrapSaldoNovo">
                        <label class="form-label">Saldo contado (inventário) *</label>
                        <input type="number" name="saldo_novo" class="form-control" min="0" value="<?= $saldoAtual !== null ? (int)$saldoAtual : '' ?>">
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <?php endif; ?>

                <div class="alert alert-info small mt-3 mb-0" id="caHint">
                    <strong>Saída:</strong> selecione o CA em estoque. <strong>Entrada / Devolução:</strong> informe o CA do lote. O estoque mínimo é controlado pelo cadastro do EPI.
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

    function usaSelectCa(tipo) {
        return tipo === 'Saída';
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

    function popularSelectCa(select, epiId, selected) {
        select.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecione CA em estoque...';
        select.appendChild(placeholder);
        const lotes = casEstoquePorEpi[epiId] || [];
        lotes.forEach(function (l) {
            const opt = document.createElement('option');
            opt.value = l.ca_numero;
            opt.textContent = l.ca_numero + ' (saldo: ' + l.saldo + ')';
            opt.dataset.validade = l.ca_validade || '';
            opt.dataset.saldo = String(l.saldo);
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
            popularSelectCa(select, epiId, keepValue || input.value || select.value);
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
        const validade = wrap ? wrap.querySelector('.ca-validade-input') : document.getElementById('caValidade');
        const qty = wrap ? wrap.closest('.item-row')?.querySelector('.qty-input') : document.getElementById('qtyInput');
        const opt = select.options[select.selectedIndex];
        if (validade && opt && opt.dataset.validade) {
            validade.value = opt.dataset.validade;
            validade.readOnly = true;
        }
        if (qty && opt && opt.dataset.saldo) {
            qty.max = opt.dataset.saldo;
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
            container.querySelectorAll('.item-row').forEach(refreshRow);
        });

        document.getElementById('btnAddItem').addEventListener('click', function () {
            const div = document.createElement('div');
            div.innerHTML = tpl.replace(/itens\[0\]/g, 'itens[' + idx + ']');
            const row = div.firstElementChild;
            row.querySelector('.remove-row').classList.remove('d-none');
            row.querySelector('.epi-select').value = '';
            row.querySelector('.ca-input').value = '';
            const val = row.querySelector('.ca-validade-input');
            if (val) { val.value = ''; val.readOnly = false; }
            const sel = row.querySelector('.ca-select');
            if (sel) { sel.innerHTML = ''; sel.dataset.name = 'itens[' + idx + '][ca_numero]'; delete sel.dataset.bound; }
            container.appendChild(row);
            idx++;
            bindRow(row);
        });

        bindRow(container.querySelector('.item-row'));
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

    if (caSelect) bindCaSelect(caSelect);

    function refreshSingle() {
        const t = tipo.value;
        const isAjuste = t === 'Ajuste';
        wrapSaldo.classList.toggle('d-none', !isAjuste);
        wrapQty.classList.toggle('d-none', isAjuste);
        wrapCa.classList.toggle('d-none', isAjuste);
        wrapCaVal.classList.toggle('d-none', isAjuste);
        caHint.classList.toggle('d-none', isAjuste);
        qtyInput.required = !isAjuste;
        wrapSaldo.querySelector('input').required = isAjuste;
        if (!isAjuste) {
            const epiId = getEpiIdFromRow(null);
            syncCaField(wrapCa, t, epiId, caInput.value || (caSelect ? caSelect.value : ''));
            if (caSelect && caSelect.value) onCaSelectChange(caSelect);
        }
    }

    tipo.addEventListener('change', refreshSingle);
    if (epiSelect) epiSelect.addEventListener('change', refreshSingle);
    refreshSingle();
})();
</script>
