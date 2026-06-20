<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamentos_form');
$action = $isEdit ? 'sst-update-equipamento/' . (int)$item['id'] : 'sst-create-equipamento';
$periodicidades = $this->data['periodicidades'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-fire-extinguisher me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> equipamento</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <h6 class="text-muted text-uppercase small mb-3">Identificação</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="codigo">Código *</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="patrimonio">Patrimônio</label>
                        <input type="text" name="patrimonio" id="patrimonio" class="form-control" value="<?= htmlspecialchars($item['patrimonio'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="adms_sst_equipamento_tipo_id">Tipo *</label>
                        <select name="adms_sst_equipamento_tipo_id" id="adms_sst_equipamento_tipo_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['tipos'] ?? [] as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= (int)($item['adms_sst_equipamento_tipo_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach (['Ativo','Inativo','Baixado'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($item['status'] ?? 'Ativo') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="localizacao">Localização</label>
                        <input type="text" name="localizacao" id="localizacao" class="form-control" value="<?= htmlspecialchars($item['localizacao'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="adms_department_id">Departamento</label>
                        <select name="adms_department_id" id="adms_department_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (int)($item['adms_department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="responsavel_adms_user_id">Responsável</label>
                        <select name="responsavel_adms_user_id" id="responsavel_adms_user_id" class="form-select">
                            <option value="">— Nenhum (fila geral) —</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (int)($item['responsavel_adms_user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <h6 class="text-muted text-uppercase small mb-3 mt-2">Características</h6>
                <div class="row">
                    <div class="col-md-3 mb-3"><label class="form-label" for="fabricante">Fabricante</label><input type="text" name="fabricante" id="fabricante" class="form-control" value="<?= htmlspecialchars($item['fabricante'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="modelo">Modelo</label><input type="text" name="modelo" id="modelo" class="form-control" value="<?= htmlspecialchars($item['modelo'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="capacidade">Capacidade</label><input type="text" name="capacidade" id="capacidade" class="form-control" value="<?= htmlspecialchars($item['capacidade'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="numero_serie">Nº série</label><input type="text" name="numero_serie" id="numero_serie" class="form-control" value="<?= htmlspecialchars($item['numero_serie'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="data_fabricacao">Data fabricação</label><input type="date" name="data_fabricacao" id="data_fabricacao" class="form-control" value="<?= htmlspecialchars($item['data_fabricacao'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="data_recarga">Data recarga</label><input type="date" name="data_recarga" id="data_recarga" class="form-control" value="<?= htmlspecialchars($item['data_recarga'] ?? '') ?>"></div>
                    <div class="col-md-3 mb-3"><label class="form-label" for="data_proxima_recarga">Próxima recarga</label><input type="date" name="data_proxima_recarga" id="data_proxima_recarga" class="form-control" value="<?= htmlspecialchars($item['data_proxima_recarga'] ?? '') ?>"></div>
                </div>
                <h6 class="text-muted text-uppercase small mb-3 mt-2">Vistorias</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="periodicidade_meses">Periodicidade *</label>
                        <select name="periodicidade_meses" id="periodicidade_meses" class="form-select" required>
                            <?php foreach ($periodicidades as $meses => $label): ?>
                            <option value="<?= (int)$meses ?>" <?= (int)($item['periodicidade_meses'] ?? ($this->data['settings_defaults']['periodicidade_meses_padrao'] ?? 1)) === (int)$meses ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="dia_previsto_vistoria">Dia da vistoria</label>
                        <select name="dia_previsto_vistoria" id="dia_previsto_vistoria" class="form-select">
                            <option value="">Padrão do módulo</option>
                            <?php foreach ($this->data['dias'] ?? [] as $d => $label): ?>
                            <option value="<?= (int)$d ?>" <?= (int)($item['dia_previsto_vistoria'] ?? 0) === (int)$d ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Dia do mês em que o cron abre a vistoria e define o prazo.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_referencia_inspecao">Mês referência (1ª competência)</label>
                        <input type="date" name="data_referencia_inspecao" id="data_referencia_inspecao" class="form-control" value="<?= htmlspecialchars($item['data_referencia_inspecao'] ?? '') ?>">
                        <div class="form-text">Opcional. Se vazio, usa data de cadastro.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-check mt-4 pt-1">
                            <input class="form-check-input" type="checkbox" name="vistoria_automatica" id="vistoria_automatica" value="1"
                                <?= ($item['vistoria_automatica'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="vistoria_automatica">Gerar vistorias automaticamente</label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <p class="form-text mb-0">
                            Padrões globais em
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-equipamento-settings">Configurações de vistorias</a>.
                        </p>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
