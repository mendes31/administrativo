<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamento_tipo_form');
$action = $isEdit ? 'sst-update-equipamento-tipo/' . (int)$item['id'] : 'sst-create-equipamento-tipo';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-layer-group me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> tipo de equipamento</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamento-tipos">Tipos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="codigo">Código *</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required>
                        <div class="form-text">Identificador interno do tipo (ex.: EXTINTOR).</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="prefixo">Prefixo *</label>
                        <input type="text" name="prefixo" id="prefixo" class="form-control text-uppercase" maxlength="3" minlength="3" pattern="[A-Za-z0-9]{3}" value="<?= htmlspecialchars($item['prefixo'] ?? '') ?>" required>
                        <div class="form-text">3 caracteres → EXT00001.</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= ($item['status'] ?? 'Ativo') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($item['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4 pt-1">
                            <input class="form-check-input" type="checkbox" name="controla_recarga" id="controla_recarga" value="1"
                                <?= !empty($item['controla_recarga']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="controla_recarga">Controla recarga / validade de carga</label>
                        </div>
                        <div class="form-text">Ex.: extintores. Habilita campos e histórico de recargas.</div>
                    </div>
                    <div class="col-md-3 mb-3" id="wrap_validade_recarga">
                        <label class="form-label" for="validade_recarga_meses">Validade padrão (meses)</label>
                        <input type="number" name="validade_recarga_meses" id="validade_recarga_meses" class="form-control" min="1" max="120"
                            value="<?= (int)($item['validade_recarga_meses'] ?? 12) ?>">
                        <div class="form-text">Usado para calcular a próxima recarga.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamento-tipos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var el = document.getElementById('prefixo');
    if (el) {
        el.addEventListener('input', function () {
            el.value = el.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 3);
        });
    }
    var chk = document.getElementById('controla_recarga');
    var wrap = document.getElementById('wrap_validade_recarga');
    function toggleValidade() {
        if (!wrap || !chk) return;
        wrap.style.display = chk.checked ? '' : 'none';
    }
    if (chk) {
        chk.addEventListener('change', toggleValidade);
        toggleValidade();
    }
})();
</script>
