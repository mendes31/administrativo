<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstExameResultadoHelper;
use App\adms\Helpers\SstExameTipoHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_exames_form');
$action = $isEdit ? 'sst-update-exame/' . (int)$item['id'] : 'sst-create-exame';
$resultadosSelecionados = $item['resultados_permitidos_list'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-stethoscope me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Exame Complementar</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-exames">Exames</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>" id="form-sst-exame">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h6 class="text-muted text-uppercase small mb-3">Dados básicos</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="codigo">Código interno</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" maxlength="20"
                               value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" placeholder="EX0001">
                        <div class="form-text">Identificação única no catálogo.</div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="tipo">Tipo do exame</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach (SstExameTipoHelper::all() as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo) ?>" <?= (($item['tipo'] ?? '') === $tipo) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="text-muted text-uppercase small mb-3">Controle de realização</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="periodicidade_meses">Periodicidade padrão (meses)</label>
                        <input type="number" name="periodicidade_meses" id="periodicidade_meses" class="form-control" min="1"
                               value="<?= htmlspecialchars((string)($item['periodicidade_meses'] ?? '')) ?>">
                        <div class="form-text">Usada apenas quando a matriz (cargo/risco) não definir periodicidade.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="possui_validade" id="possui_validade" class="form-check-input" value="1"
                                <?= !empty($item['possui_validade']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="possui_validade">Possui validade de resultado</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3" id="wrap-validade-meses">
                        <label class="form-label" for="validade_meses">Validade (meses)</label>
                        <input type="number" name="validade_meses" id="validade_meses" class="form-control" min="1"
                               value="<?= htmlspecialchars((string)($item['validade_meses'] ?? '')) ?>">
                    </div>
                </div>

                <hr>
                <h6 class="text-muted text-uppercase small mb-3">Resultado no lançamento</h6>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="exige_resultado" id="exige_resultado" class="form-check-input" value="1"
                                <?= !isset($item['exige_resultado']) || !empty($item['exige_resultado']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="exige_resultado">Exame exige resultado ao registrar no ASO</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3" id="wrap-resultados">
                        <label class="form-label d-block">Resultados esperados</label>
                        <div class="row">
                            <?php foreach (SstExameResultadoHelper::all() as $res): ?>
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="resultados_permitidos[]"
                                               id="res_<?= md5($res) ?>" value="<?= htmlspecialchars($res) ?>"
                                            <?= in_array($res, $resultadosSelecionados, true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="res_<?= md5($res) ?>"><?= htmlspecialchars($res) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-exames" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const possuiValidade = document.getElementById('possui_validade');
    const wrapValidade = document.getElementById('wrap-validade-meses');
    const exigeResultado = document.getElementById('exige_resultado');
    const wrapResultados = document.getElementById('wrap-resultados');

    function toggleValidade() {
        if (!wrapValidade) return;
        wrapValidade.style.display = possuiValidade?.checked ? '' : 'none';
    }

    function toggleResultados() {
        if (!wrapResultados) return;
        wrapResultados.style.display = exigeResultado?.checked ? '' : 'none';
    }

    possuiValidade?.addEventListener('change', toggleValidade);
    exigeResultado?.addEventListener('change', toggleResultados);
    toggleValidade();
    toggleResultados();
})();
</script>
