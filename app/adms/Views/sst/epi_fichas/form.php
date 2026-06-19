<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$epis = $this->data['epis'] ?? [];
$users = $this->data['users'] ?? [];
$casPorEpiJson = $this->data['cas_por_epi_json'] ?? '{}';
$csrfToken = CSRFHelper::generateCSRFToken('sst_epi_fichas_form');
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
                <div class="mb-3">
                    <label class="form-label" for="observacoes">Observações gerais</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"></textarea>
                </div>

                <h5 class="mb-3">EPIs desta entrega</h5>
                <div id="itensContainer">
                    <div class="row g-2 align-items-end item-row mb-2">
                        <div class="col-md-4">
                            <label class="form-label">EPI *</label>
                            <select name="itens[0][adms_sst_epi_id]" class="form-select epi-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($epis as $ep): ?>
                                <option value="<?= (int)$ep['id'] ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Qtde</label>
                            <input type="number" name="itens[0][quantidade]" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">CA utilizado *</label>
                            <input type="text" name="itens[0][ca_utilizado]" class="form-control ca-input text-uppercase" placeholder="Nº CA" required list="caList0">
                            <datalist id="caList0"></datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prev. substituição</label>
                            <input type="date" name="itens[0][data_prevista_troca]" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row d-none" title="Remover"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="btnAddItem"><i class="fas fa-plus me-1"></i> Adicionar EPI</button>

                <div class="alert alert-info small">
                    Informe o CA do lote entregue ao colaborador. Sugestões vêm das últimas entradas de estoque desse EPI.
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Gerar ficha e notificar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-fichas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const casPorEpi = <?= $casPorEpiJson ?>;
    let idx = 1;
    const container = document.getElementById('itensContainer');
    const tpl = container.querySelector('.item-row').outerHTML;

    function refreshCaList(row) {
        const sel = row.querySelector('.epi-select');
        const ca = row.querySelector('.ca-input');
        const list = row.querySelector('datalist');
        if (!sel || !ca || !list) return;
        const epiId = sel.value;
        list.innerHTML = '';
        (casPorEpi[epiId] || []).forEach(function (n) {
            const opt = document.createElement('option');
            opt.value = n;
            list.appendChild(opt);
        });
        if (!ca.value && list.options.length === 1) {
            ca.value = list.options[0].value;
        }
    }

    document.getElementById('btnAddItem').addEventListener('click', function () {
        const div = document.createElement('div');
        div.innerHTML = tpl.replace(/itens\[0\]/g, 'itens[' + idx + ']').replace(/caList0/g, 'caList' + idx);
        const row = div.firstElementChild;
        row.querySelector('.remove-row').classList.remove('d-none');
        row.querySelector('.epi-select').value = '';
        row.querySelector('.ca-input').value = '';
        container.appendChild(row);
        idx++;
        bindRow(row);
    });

    function bindRow(row) {
        const sel = row.querySelector('.epi-select');
        sel.addEventListener('change', function () { refreshCaList(row); });
        const rm = row.querySelector('.remove-row');
        if (rm) rm.addEventListener('click', function () { row.remove(); });
        refreshCaList(row);
    }
    bindRow(container.querySelector('.item-row'));
})();
</script>
