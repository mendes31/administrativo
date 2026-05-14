<?php
if (!isset($sapPickerSearchUrl)) {
    $sapPickerSearchUrl = '';
}
if (!isset($sapPickerConn)) {
    $sapPickerConn = '';
}
$sapPickerCardInputId = isset($sapPickerCardInputId) ? (string) $sapPickerCardInputId : 'card_code';
$sapPickerCardNameDisplayId = isset($sapPickerCardNameDisplayId) ? (string) $sapPickerCardNameDisplayId : 'card_name_display';
$sapBpPickerModalTitle = isset($sapBpPickerModalTitle) ? (string) $sapBpPickerModalTitle : 'Lista de parceiros de negócio';
$sapBpPickerSearchLabel = isset($sapBpPickerSearchLabel) ? (string) $sapBpPickerSearchLabel : 'Procurar';
$sapBpPickerSelectButton = isset($sapBpPickerSelectButton) ? (string) $sapBpPickerSelectButton : 'Selecionar';
?>
<div class="modal fade" id="salesPortalBpPickerModal" tabindex="-1" aria-labelledby="salesPortalBpPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="salesPortalBpPickerLabel"><?= htmlspecialchars($sapBpPickerModalTitle, ENT_QUOTES, 'UTF-8'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-2">
                <p class="small text-muted mb-2">Clientes no SAP B1 — escolha uma linha para preencher o <code>CardCode</code><?= $sapPickerCardNameDisplayId !== '' ? ' e o nome no formulário' : ''; ?>.</p>
                <label class="form-label small mb-0" for="salesPortalBpPickerQuery"><?= htmlspecialchars($sapBpPickerSearchLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control form-control-sm" id="salesPortalBpPickerQuery" maxlength="60" placeholder="Vazio = primeiros clientes; ou filtre por código / nome" autocomplete="off">
                    <button type="button" class="btn btn-primary btn-sm" id="salesPortalBpPickerSearchBtn">Pesquisar</button>
                </div>
                <div id="salesPortalBpPickerStatus" class="small text-muted mb-1"></div>
                <div class="table-responsive" style="max-height: 320px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                        <tr>
                            <th>Código do PN</th>
                            <th>Nome do PN</th>
                            <th style="width:5rem"></th>
                        </tr>
                        </thead>
                        <tbody id="salesPortalBpPickerBody">
                        <tr><td colspan="3" class="text-muted text-center py-3">Introduza um termo e clique em Pesquisar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var searchUrl = <?= json_encode($sapPickerSearchUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var connSuffix = <?= json_encode($sapPickerConn, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var cardInputId = <?= json_encode($sapPickerCardInputId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var nameDisplayId = <?= json_encode($sapPickerCardNameDisplayId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var selectBtnLabel = <?= json_encode($sapBpPickerSelectButton, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var cardInput = document.getElementById(cardInputId);
    if (!cardInput || !searchUrl) { return; }

    var modalEl = document.getElementById('salesPortalBpPickerModal');
    var queryEl = document.getElementById('salesPortalBpPickerQuery');
    var btnSearch = document.getElementById('salesPortalBpPickerSearchBtn');
    var statusEl = document.getElementById('salesPortalBpPickerStatus');
    var tbody = document.getElementById('salesPortalBpPickerBody');
    var openBtn = document.getElementById('salesPortalBpPickerOpen');
    if (!modalEl || !queryEl || !btnSearch || !statusEl || !tbody) { return; }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal ? new bootstrap.Modal(modalEl) : null;

    function setStatus(msg, isErr) {
        statusEl.textContent = msg || '';
        statusEl.className = 'small mb-1 ' + (isErr ? 'text-danger' : 'text-muted');
    }

    function renderRows(partners) {
        tbody.innerHTML = '';
        if (!partners || partners.length === 0) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="3" class="text-muted text-center py-3">Nenhum resultado.</td>';
            tbody.appendChild(tr);
            return;
        }
        partners.forEach(function (p) {
            var code = (p.CardCode || '').toString();
            var name = (p.CardName || '').toString();
            var tr = document.createElement('tr');
            var tdCode = document.createElement('td');
            tdCode.innerHTML = '<code>' + escapeHtml(code) + '</code>';
            var tdName = document.createElement('td');
            tdName.textContent = name;
            var tdAct = document.createElement('td');
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-primary';
            b.textContent = selectBtnLabel;
            b.addEventListener('click', function () {
                cardInput.value = code;
                if (nameDisplayId) {
                    var nameDisplay = document.getElementById(nameDisplayId);
                    if (nameDisplay) {
                        nameDisplay.value = name;
                    }
                }
                if (modal) { modal.hide(); }
            });
            tdAct.appendChild(b);
            tr.appendChild(tdCode);
            tr.appendChild(tdName);
            tr.appendChild(tdAct);
            tbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]);
        });
    }

    function runSearch() {
        var q = (queryEl.value || '').trim();
        setStatus('A pesquisar…', false);
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>';
        var url = searchUrl + '?q=' + encodeURIComponent(q) + connSuffix;
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text().then(function (t) { try { return JSON.parse(t); } catch (e) { throw new Error('Resposta inválida'); } }); })
            .then(function (data) {
                if (!data.success) {
                    setStatus(data.error || 'Erro na pesquisa.', true);
                    renderRows([]);
                    return;
                }
                setStatus((data.partners && data.partners.length) ? (data.partners.length + ' resultado(s).') : 'Nenhum resultado.', false);
                renderRows(data.partners || []);
            })
            .catch(function () {
                setStatus('Falha de rede ou servidor.', true);
                renderRows([]);
            });
    }

    if (openBtn && modal) {
        openBtn.addEventListener('click', function () {
            queryEl.value = (cardInput.value || '').trim();
            setStatus('', false);
            modal.show();
        });
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
