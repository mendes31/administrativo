<?php
if (!isset($sapItemPickerSearchUrl)) {
    $sapItemPickerSearchUrl = '';
}
if (!isset($sapItemPickerConn)) {
    $sapItemPickerConn = '';
}
?>
<div class="modal fade" id="salesPortalItemPickerModal" tabindex="-1" aria-labelledby="salesPortalItemPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="salesPortalItemPickerLabel">Lista de artigos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-2">
                <p class="small text-muted mb-2 mb-md-1">Artigos activos no SAP — vazio lista os primeiros; texto filtra por <code>ItemCode</code> / nome. No POST vai só o código (+ qtd. / preço opcional).</p>
                <label class="form-label small mb-0" for="salesPortalItemPickerQuery">Procurar</label>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control form-control-sm" id="salesPortalItemPickerQuery" maxlength="60" placeholder="Vazio = primeiros artigos; ou filtre por código / nome" autocomplete="off">
                    <button type="button" class="btn btn-primary btn-sm" id="salesPortalItemPickerSearchBtn">Pesquisar</button>
                </div>
                <div id="salesPortalItemPickerStatus" class="small text-muted mb-1"></div>
                <div class="table-responsive" style="max-height: 280px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th style="width:5rem"></th>
                        </tr>
                        </thead>
                        <tbody id="salesPortalItemPickerBody">
                        <tr><td colspan="3" class="text-muted text-center py-2">Abra a lupa para carregar a lista ou use Pesquisar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var searchUrl = <?= json_encode($sapItemPickerSearchUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var connSuffix = <?= json_encode($sapItemPickerConn, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var modalEl = document.getElementById('salesPortalItemPickerModal');
    var queryEl = document.getElementById('salesPortalItemPickerQuery');
    var btnSearch = document.getElementById('salesPortalItemPickerSearchBtn');
    var statusEl = document.getElementById('salesPortalItemPickerStatus');
    var pickerTbody = document.getElementById('salesPortalItemPickerBody');
    if (!modalEl || !queryEl || !btnSearch || !statusEl || !pickerTbody || !searchUrl) { return; }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal ? new bootstrap.Modal(modalEl) : null;
    var itemRowIndex = 0;

    function itemLinesTbody() {
        return document.getElementById('salesPortalLinesBody')
            || document.getElementById('salesPortalOrderLinesBody');
    }

    function lineInputs() {
        var tbody = itemLinesTbody();
        if (!tbody) {
            return { codes: [], descs: [] };
        }
        return {
            codes: tbody.querySelectorAll('input[name="line_item_code[]"]'),
            descs: tbody.querySelectorAll('input[data-sales-line-desc="1"]')
        };
    }

    function rowIndexFromPickerButton(btn) {
        var tr = btn.closest('tr');
        var tbody = itemLinesTbody();
        if (!tr || !tbody || tr.parentElement !== tbody) { return 0; }
        var rows = tbody.querySelectorAll('tr');
        return Array.prototype.indexOf.call(rows, tr);
    }

    function setStatus(msg, isErr) {
        statusEl.textContent = msg || '';
        statusEl.className = 'small mb-1 ' + (isErr ? 'text-danger' : 'text-muted');
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]);
        });
    }

    function renderRows(items) {
        pickerTbody.innerHTML = '';
        if (!items || items.length === 0) {
            var tr0 = document.createElement('tr');
            tr0.innerHTML = '<td colspan="3" class="text-muted text-center py-2">Nenhum resultado.</td>';
            pickerTbody.appendChild(tr0);
            return;
        }
        items.forEach(function (it) {
            var code = (it.ItemCode || '').toString();
            var name = (it.ItemName || '').toString();
            var tr = document.createElement('tr');
            var tdCode = document.createElement('td');
            tdCode.innerHTML = '<code>' + escapeHtml(code) + '</code>';
            var tdName = document.createElement('td');
            tdName.textContent = name;
            var tdAct = document.createElement('td');
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-primary';
            b.textContent = 'Selecionar';
            b.addEventListener('click', function () {
                var L = lineInputs();
                var ci = L.codes[itemRowIndex];
                var di = L.descs[itemRowIndex];
                if (ci) { ci.value = code; }
                if (di) { di.value = name; }
                if (modal) { modal.hide(); }
            });
            tdAct.appendChild(b);
            tr.appendChild(tdCode);
            tr.appendChild(tdName);
            tr.appendChild(tdAct);
            pickerTbody.appendChild(tr);
        });
    }

    function runSearch() {
        var q = (queryEl.value || '').trim();
        setStatus('A pesquisar…', false);
        pickerTbody.innerHTML = '<tr><td colspan="3" class="text-center py-2"><span class="spinner-border spinner-border-sm"></span></td></tr>';
        var url = searchUrl + '?q=' + encodeURIComponent(q) + connSuffix;
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text().then(function (t) { try { return JSON.parse(t); } catch (e) { throw new Error('Resposta inválida'); } }); })
            .then(function (data) {
                if (!data.success) {
                    setStatus(data.error || 'Erro na pesquisa.', true);
                    renderRows([]);
                    return;
                }
                setStatus((data.items && data.items.length) ? (data.items.length + ' resultado(s).') : 'Nenhum resultado.', false);
                renderRows(data.items || []);
            })
            .catch(function () {
                setStatus('Falha de rede ou servidor.', true);
                renderRows([]);
            });
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.salesPortalItemPickerOpen');
        if (!btn) { return; }
        itemRowIndex = rowIndexFromPickerButton(btn);
        if (itemRowIndex < 0) { itemRowIndex = 0; }
        var L = lineInputs();
        var cur = L.codes[itemRowIndex];
        queryEl.value = cur ? (cur.value || '').trim() : '';
        setStatus('', false);
        if (modal) { modal.show(); }
    });

    if (modal) {
        modalEl.addEventListener('shown.bs.modal', function () {
            queryEl.focus();
            runSearch();
        });
    }
    btnSearch.addEventListener('click', runSearch);
    queryEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); runSearch(); }
    });
})();
</script>
