<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;

$csrfToken = CSRFHelper::generateCSRFToken('sst_encaminhamento_aso');
$prefillUserId = (int) ($this->data['prefill_user_id'] ?? 0);
$prefillCategoria = (string) ($this->data['prefill_categoria'] ?? '');
$dataHoje = date('Y-m-d');
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-file-export me-2"></i>Encaminhamento ASO</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos">ASOs</a></li>
            <li class="breadcrumb-item active">Encaminhamento</li>
        </ol>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Gera o documento de autorização para realização do ASO e exames complementares conforme a matriz de riscos.
                Exames <strong>obrigatórios</strong> entram automaticamente; marque os <strong>recomendados</strong> que a empresa deseja incluir.
            </p>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-export-encaminhamento-aso-pdf" target="_blank" id="form-encaminhamento-aso">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="adms_user_id">Colaborador *</label>
                        <select name="adms_user_id" id="adms_user_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" <?= $prefillUserId === (int) $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="categoria_aso">Categoria ASO *</label>
                        <select name="categoria_aso" id="categoria_aso" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (SstCategoriaAsoHelper::all() as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $prefillCategoria === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_encaminhamento">Data</label>
                        <input type="date" name="data_encaminhamento" id="data_encaminhamento" class="form-control" value="<?= $dataHoje ?>">
                    </div>
                </div>

                <div id="wrap-exames-pacote" class="d-none">
                    <hr>
                    <h6 class="text-muted text-uppercase small">Exames do encaminhamento</h6>
                    <div id="lista-obrigatorios" class="mb-3"></div>
                    <div id="lista-recomendados"></div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success" id="btn-gerar-pdf" disabled>
                        <i class="fas fa-file-pdf me-1"></i> Gerar PDF
                    </button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const userSel = document.getElementById('adms_user_id');
    const catSel = document.getElementById('categoria_aso');
    const wrap = document.getElementById('wrap-exames-pacote');
    const listaObr = document.getElementById('lista-obrigatorios');
    const listaRec = document.getElementById('lista-recomendados');
    const btnPdf = document.getElementById('btn-gerar-pdf');

    function renderLista(container, titulo, itens, obrigatorio) {
        if (!container) return;
        if (!Array.isArray(itens) || itens.length === 0) {
            container.innerHTML = '<p class="small text-muted mb-2">' + titulo + ': nenhum.</p>';
            return;
        }
        let html = '<p class="fw-semibold small mb-2">' + titulo + '</p><ul class="list-unstyled mb-0">';
        itens.forEach(ex => {
            const id = parseInt(ex.adms_sst_exame_id, 10);
            const nome = ex.exame_nome || ('Exame #' + id);
            if (obrigatorio) {
                html += '<li class="mb-1"><span class="badge bg-primary me-1">Obrigatório</span> ' + nome + '</li>';
            } else {
                html += '<li class="mb-1"><label class="form-check-label"><input type="checkbox" class="form-check-input me-1" name="recomendados[]" value="' + id + '"> <span class="badge bg-secondary me-1">Recomendado</span> ' + nome + '</label></li>';
            }
        });
        html += '</ul>';
        container.innerHTML = html;
    }

    function carregarPacote() {
        const userId = userSel?.value;
        const tipo = catSel?.value;
        if (!userId || !tipo) {
            wrap?.classList.add('d-none');
            if (btnPdf) btnPdf.disabled = true;
            return;
        }
        const url = '<?= $_ENV['URL_ADM'] ?>sst-pacote-exames-aso?adms_user_id=' + encodeURIComponent(userId) + '&tipo=' + encodeURIComponent(tipo);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    wrap?.classList.add('d-none');
                    if (btnPdf) btnPdf.disabled = true;
                    return;
                }
                renderLista(listaObr, 'Obrigatórios (inclusos no PDF)', data.obrigatorios || data.exames || [], true);
                renderLista(listaRec, 'Recomendados (opcionais — marque para incluir)', data.recomendados || [], false);
                wrap?.classList.remove('d-none');
                if (btnPdf) btnPdf.disabled = false;
            })
            .catch(() => {
                wrap?.classList.add('d-none');
                if (btnPdf) btnPdf.disabled = true;
            });
    }

    userSel?.addEventListener('change', carregarPacote);
    catSel?.addEventListener('change', carregarPacote);
    carregarPacote();
})();
</script>
