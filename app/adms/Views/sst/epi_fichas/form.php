<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$epis = $this->data['epis'] ?? [];
$users = $this->data['users'] ?? [];
$preselectEpiId = (int) ($item['adms_sst_epi_id'] ?? 0);
$casEstoqueJson = $this->data['cas_estoque_por_epi_json'] ?? '{}';
$vidaUtilJson = $this->data['vida_util_por_epi_json'] ?? '{}';
$csrfToken = CSRFHelper::generateCSRFToken('sst_epi_fichas_form');
$bloqueioAtivo = !empty($this->data['bloqueio_treinamento_ativo']);
$podeIgnorarBloqueio = !empty($this->data['pode_ignorar_bloqueio_treinamento']);
$impedimentosPreview = $this->data['impedimentos_treinamento_preview'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-file-signature me-2"></i>Nova ficha de entrega de EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-fichas">Fichas de EPI</a></li>
            <li class="breadcrumb-item active">Nova</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-create-epi-ficha" id="formEpiFicha">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="adms_user_id">Colaborador *</label>
                        <select name="adms_user_id" id="adms_user_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= ((int)($item['adms_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="data_entrega">Data da entrega *</label>
                        <input type="date" name="data_entrega" id="data_entrega" class="form-control" value="<?= htmlspecialchars($item['data_entrega'] ?? date('Y-m-d')) ?>" required>
                    </div>
                </div>
                <?php if ($bloqueioAtivo && $impedimentosPreview !== []): ?>
                <div class="alert alert-danger py-2">
                    <strong>Treinamento SST impede entrega de EPI:</strong>
                    <ul class="mb-0 small">
                        <?php foreach ($impedimentosPreview as $imp): ?>
                        <li><?= htmlspecialchars($imp['treinamento_nome'] ?? '') ?> — <?= htmlspecialchars($imp['situacao_label'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($podeIgnorarBloqueio): ?>
                    <div class="form-check mt-2 mb-0">
                        <input class="form-check-input" type="checkbox" name="confirmar_bypass_bloqueio_treinamento" value="1" id="confirmar_bypass_bloqueio_treinamento">
                        <label class="form-check-label small" for="confirmar_bypass_bloqueio_treinamento">Liberar entrega mesmo com pendência (administrador)</label>
                    </div>
                    <?php endif; ?>
                </div>
                <?php elseif ($bloqueioAtivo): ?>
                <p class="small text-muted mb-3">Com bloqueio SST ativo, colaboradores com treinamento vencido ou pendente não recebem nova ficha de EPI.</p>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="observacoes">Observações gerais</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"></textarea>
                </div>

                <h5 class="mb-3">EPIs desta entrega</h5>
                <div id="itensContainer">
                    <div class="row g-2 align-items-end item-row mb-2">
                        <div class="col-md-3">
                            <label class="form-label">EPI *</label>
                            <select name="itens[0][adms_sst_epi_id]" class="form-select epi-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($epis as $ep): ?>
                                <option value="<?= (int)$ep['id'] ?>" data-vida-util="<?= (int)($ep['periodicidade_troca_dias'] ?? 0) ?>" <?= $preselectEpiId === (int)($ep['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ep['nome'] ?? '') ?> (saldo: <?= (int)($ep['estoque_atual'] ?? 0) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 wrap-tamanho">
                            <label class="form-label">Tamanho / nº</label>
                            <select name="itens[0][tamanho]" class="form-select tamanho-select">
                                <option value="">—</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Qtde</label>
                            <input type="number" name="itens[0][quantidade]" class="form-control qty-input" value="1" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">CA em estoque *</label>
                            <select name="itens[0][ca_utilizado]" class="form-select ca-select" required>
                                <option value="">Selecione o EPI primeiro...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prev. substituição</label>
                            <input type="date" name="itens[0][data_prevista_troca]" class="form-control prev-troca-input">
                            <div class="form-text prev-troca-hint small text-muted"></div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row d-none" title="Remover"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="btnAddItem"><i class="fas fa-plus me-1"></i> Adicionar EPI</button>

                <div class="alert alert-info small mb-0">
                    A <strong>prev. substituição</strong> é calculada pela <strong>vida útil</strong> do EPI (tempo de uso previsto no cadastro),
                    limitada à <strong>validade do CA</strong> do lote — usa-se a data mais próxima. Pode ajustar manualmente para uma data anterior.
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Gerar ficha e notificar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-fichas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const casEstoquePorEpi = <?= $casEstoqueJson ?>;
    const vidaUtilPorEpi = <?= $vidaUtilJson ?>;
    let idx = 1;
    const container = document.getElementById('itensContainer');
    const dataEntregaInput = document.getElementById('data_entrega');
    const tpl = container.querySelector('.item-row').outerHTML;

    function addDays(isoDate, days) {
        const d = new Date(isoDate + 'T12:00:00');
        d.setDate(d.getDate() + days);
        return d.toISOString().slice(0, 10);
    }

    function formatBr(iso) {
        if (!iso) return '';
        const p = iso.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    function calcPrevistaTroca(row) {
        const dataEntrega = dataEntregaInput.value;
        const epiId = row.querySelector('.epi-select')?.value || '';
        const selCa = row.querySelector('.ca-select');
        const prevInput = row.querySelector('.prev-troca-input');
        const hint = row.querySelector('.prev-troca-hint');
        if (!prevInput || !dataEntrega || !epiId) return null;

        const candidatas = [];
        const dias = parseInt(vidaUtilPorEpi[epiId] || '0', 10);
        let porVidaUtil = null;
        if (dias > 0) {
            porVidaUtil = addDays(dataEntrega, dias);
            candidatas.push(porVidaUtil);
        }
        let validadeCa = null;
        if (selCa && selCa.value) {
            const opt = selCa.options[selCa.selectedIndex];
            validadeCa = opt?.dataset?.validade || null;
            if (validadeCa) candidatas.push(validadeCa);
        }
        if (candidatas.length === 0) {
            if (hint) hint.textContent = dias <= 0 ? 'Cadastre a vida útil do EPI para cálculo automático.' : '';
            return null;
        }
        candidatas.sort();
        const result = candidatas[0];
        if (hint) {
            const parts = [];
            if (porVidaUtil) parts.push('vida útil: ' + formatBr(porVidaUtil));
            if (validadeCa) parts.push('validade CA: ' + formatBr(validadeCa));
            hint.textContent = parts.length ? 'Limite: ' + parts.join(' · ') : '';
        }
        return result;
    }

    function refreshPrevista(row, force) {
        const prevInput = row.querySelector('.prev-troca-input');
        if (!prevInput) return;
        if (!force && prevInput.dataset.manual === '1') return;
        const calc = calcPrevistaTroca(row);
        if (calc) {
            prevInput.value = calc;
            prevInput.dataset.auto = calc;
        }
    }

    function metaEpi(epiId) {
        const raw = casEstoquePorEpi[epiId];
        if (!raw) return {controla_tamanho: false, grade: [], lotes: [], cas: []};
        if (Array.isArray(raw)) return {controla_tamanho: false, grade: [], lotes: [], cas: raw};
        return raw;
    }

    function popularTamanhoSelect(row) {
        const selEpi = row.querySelector('.epi-select');
        const selTam = row.querySelector('.tamanho-select');
        const wrapTam = row.querySelector('.wrap-tamanho');
        if (!selEpi || !selTam) return;
        const epiId = selEpi.value;
        const meta = metaEpi(epiId);
        const prev = selTam.value;
        selTam.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        if (!epiId) {
            ph.textContent = '—';
            selTam.appendChild(ph);
            selTam.required = false;
            if (wrapTam) wrapTam.classList.remove('d-none');
            return;
        }
        if (!meta.controla_tamanho) {
            ph.textContent = '—';
            selTam.appendChild(ph);
            selTam.required = false;
            selTam.value = '';
            if (wrapTam) wrapTam.classList.add('d-none');
            return;
        }
        if (wrapTam) wrapTam.classList.remove('d-none');
        ph.textContent = 'Selecione...';
        selTam.appendChild(ph);
        const vistos = {};
        (meta.lotes || []).forEach(function (l) {
            const t = (l.tamanho || '').toString();
            if (!t || vistos[t]) return;
            vistos[t] = true;
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (prev === t) opt.selected = true;
            selTam.appendChild(opt);
        });
        selTam.required = true;
        if (!prev && selTam.options.length === 2) selTam.selectedIndex = 1;
    }

    function popularCaSelect(row) {
        const selEpi = row.querySelector('.epi-select');
        const selCa = row.querySelector('.ca-select');
        const selTam = row.querySelector('.tamanho-select');
        const qty = row.querySelector('.qty-input');
        if (!selEpi || !selCa) return;
        const epiId = selEpi.value;
        const prevCa = selCa.value;
        const tam = selTam ? selTam.value : '';
        const meta = metaEpi(epiId);
        selCa.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = epiId ? 'Selecione CA em estoque...' : 'Selecione o EPI primeiro...';
        selCa.appendChild(ph);
        if (!epiId) return;
        if (meta.controla_tamanho && !tam) {
            ph.textContent = 'Selecione o tamanho primeiro...';
            if (qty) qty.removeAttribute('max');
            return;
        }
        let lotes = meta.controla_tamanho
            ? (meta.lotes || []).filter(function (l) { return String(l.tamanho || '') === tam; })
            : (meta.cas || []);
        if (lotes.length === 0) {
            ph.textContent = 'Nenhum CA em estoque';
            if (qty) qty.removeAttribute('max');
            return;
        }
        lotes.forEach(function (l) {
            const opt = document.createElement('option');
            opt.value = l.ca_numero;
            let label = l.ca_numero + ' (saldo: ' + l.saldo + ')';
            if (l.ca_validade) label += ' · val. ' + formatBr(l.ca_validade);
            opt.textContent = label;
            opt.dataset.saldo = String(l.saldo);
            opt.dataset.validade = l.ca_validade || '';
            if (prevCa === l.ca_numero) opt.selected = true;
            selCa.appendChild(opt);
        });
        if (!prevCa && lotes.length === 1) selCa.selectedIndex = 1;
        if (selCa.value && qty) {
            const opt = selCa.options[selCa.selectedIndex];
            if (opt?.dataset?.saldo) qty.max = opt.dataset.saldo;
        }
        refreshPrevista(row, true);
    }

    function bindRow(row) {
        const selEpi = row.querySelector('.epi-select');
        const selCa = row.querySelector('.ca-select');
        const selTam = row.querySelector('.tamanho-select');
        const prevInput = row.querySelector('.prev-troca-input');
        selEpi.addEventListener('change', function () {
            popularTamanhoSelect(row);
            popularCaSelect(row);
        });
        if (selTam) {
            selTam.addEventListener('change', function () { popularCaSelect(row); });
        }
        if (selCa) {
            selCa.addEventListener('change', function () {
                const qty = row.querySelector('.qty-input');
                const opt = selCa.options[selCa.selectedIndex];
                if (qty && opt?.dataset?.saldo) qty.max = opt.dataset.saldo;
                if (prevInput) prevInput.dataset.manual = '';
                refreshPrevista(row, true);
            });
        }
        if (prevInput) {
            prevInput.addEventListener('change', function () {
                const auto = prevInput.dataset.auto || '';
                prevInput.dataset.manual = (prevInput.value && prevInput.value !== auto) ? '1' : '';
            });
        }
        const rm = row.querySelector('.remove-row');
        if (rm) rm.addEventListener('click', function () { row.remove(); });
        popularTamanhoSelect(row);
        popularCaSelect(row);
    }

    dataEntregaInput.addEventListener('change', function () {
        container.querySelectorAll('.item-row').forEach(function (row) {
            if (row.querySelector('.prev-troca-input')?.dataset.manual !== '1') {
                refreshPrevista(row, true);
            }
        });
    });

    document.getElementById('btnAddItem').addEventListener('click', function () {
        const div = document.createElement('div');
        div.innerHTML = tpl.replace(/itens\[0\]/g, 'itens[' + idx + ']');
        const row = div.firstElementChild;
        row.querySelector('.remove-row').classList.remove('d-none');
        row.querySelector('.epi-select').value = '';
        row.querySelector('.ca-select').innerHTML = '<option value="">Selecione o EPI primeiro...</option>';
        const tamSel = row.querySelector('.tamanho-select');
        if (tamSel) tamSel.innerHTML = '<option value="">—</option>';
        const prev = row.querySelector('.prev-troca-input');
        if (prev) { prev.value = ''; prev.dataset.manual = ''; delete prev.dataset.auto; }
        const hint = row.querySelector('.prev-troca-hint');
        if (hint) hint.textContent = '';
        container.appendChild(row);
        idx++;
        bindRow(row);
    });

    bindRow(container.querySelector('.item-row'));

    const selUser = document.getElementById('adms_user_id');
    if (selUser) {
        selUser.addEventListener('change', function () {
            const id = this.value;
            const base = '<?= $_ENV['URL_ADM']; ?>sst-create-epi-ficha';
            window.location.href = id ? base + '?adms_user_id=' + encodeURIComponent(id) : base;
        });
    }
})();
</script>
