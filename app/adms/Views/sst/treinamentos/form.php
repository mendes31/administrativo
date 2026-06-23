<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoAplicacaoHelper;
use App\adms\Helpers\SstTreinamentoDisplayHelper;
use App\adms\Helpers\SstTreinamentoNrHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_treinamentos_form');
$action = $isEdit ? 'sst-update-treinamento/' . (int)$item['id'] : 'sst-create-treinamento';
$aplicacaoSelecionada = $item['aplicacao_momentos'] ?? SstTreinamentoAplicacaoHelper::fromLegacyTipo($item['tipo'] ?? null);
if ($aplicacaoSelecionada === [] && !$isEdit) {
    $aplicacaoSelecionada = ['admissional'];
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-graduation-cap me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Treinamento SST</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos">Treinamentos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="codigo">Código interno</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase bg-light" maxlength="20"
                               value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" readonly
                               title="Gerado automaticamente pelo sistema (TR0001, TR0002...)">
                        <div class="form-text">Incremental — controlado pelo sistema.</div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="nr_referencia">NR referência</label>
                        <select name="nr_referencia" id="nr_referencia" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach (SstTreinamentoNrHelper::all() as $nr): ?>
                                <option value="<?= htmlspecialchars($nr) ?>" <?= (($item['nr_referencia'] ?? '') === $nr) ? 'selected' : '' ?>><?= htmlspecialchars($nr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label d-block">Quando exigir *</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach (SstTreinamentoAplicacaoHelper::all() as $key => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input aplicacao-check" type="checkbox" name="aplicacao[]" value="<?= htmlspecialchars($key) ?>"
                                           id="ap_<?= htmlspecialchars($key) ?>" <?= in_array($key, $aplicacaoSelecionada, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ap_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Ex.: Integração SST → Admissão; NR-35 → Admissão + Reciclagem.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modalidade">Modalidade</label>
                        <select name="modalidade" id="modalidade" class="form-select">
                            <?php foreach (['Presencial', 'EAD', 'Hibrido'] as $m): ?>
                                <option value="<?= $m ?>" <?= (($item['modalidade'] ?? 'Presencial') === $m) ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="carga_horaria_horas">Carga horária (horas)</label>
                        <input type="number" name="carga_horaria_horas" id="carga_horaria_horas" class="form-control" min="0.5" step="0.5"
                               value="<?= htmlspecialchars(SstTreinamentoDisplayHelper::cargaHorariaInputValue(isset($item['carga_horaria_minutos']) ? (int)$item['carga_horaria_minutos'] : null)) ?>">
                        <div class="form-text">Ex.: Integração SST = 2; NR-35 = 8; NR-10 = 40.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="validade_meses">Validade reciclagem (meses)</label>
                        <input type="number" name="validade_meses" id="validade_meses" class="form-control" min="0"
                               value="<?= htmlspecialchars((string)($item['validade_meses'] ?? '')) ?>">
                        <div class="form-text">0 = não possui reciclagem (ex.: Integração SST).</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="prazo_primeiro_dias">Prazo 1º treinamento (dias)</label>
                        <input type="number" name="prazo_primeiro_dias" id="prazo_primeiro_dias" class="form-control" min="0"
                               value="<?= htmlspecialchars((string)($item['prazo_primeiro_dias'] ?? '')) ?>">
                        <div class="form-text">1 = até 1 dia após admissão; 0 = conforme exposição imediata.</div>
                    </div>
                    <div class="col-md-4 mb-3">
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
                <?php if (!$isEdit): ?>
                <div class="alert alert-light border small mb-3">
                    Após cadastrar, vincule o treinamento ao <strong>cargo</strong> em
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-necessidade">Necessidades de treinamento</a>
                    ou ao <strong>GHE</strong> — o sistema gera pendências, vencimentos e reciclagens automaticamente na sincronização.
                </div>
                <?php endif; ?>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
