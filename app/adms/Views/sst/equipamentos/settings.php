<?php
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
$settings = $this->data['settings'] ?? [];
$periodicidades = $this->data['periodicidades'] ?? [];
$dias = $this->data['dias'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-cog me-2"></i>Configurações de vistorias</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active">Configurações</li>
        </ol>
    </div>

    <div class="alert alert-info">
        <strong>Como funciona:</strong> o cron roda <strong>diariamente</strong>. Cada equipamento abre a vistoria no
        <strong>dia previsto</strong> configurado no cadastro (ou no padrão abaixo). Esse dia também é o prazo sugerido
        para execução. Se o cron não rodar no dia exato, a vistoria é criada nos dias seguintes do mesmo mês.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-equipamento-settings">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_token'] ?? '') ?>">

                <h6 class="text-muted text-uppercase small mb-3">Padrões do módulo</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="dia_previsto_padrao">Dia previsto padrão *</label>
                        <select name="dia_previsto_padrao" id="dia_previsto_padrao" class="form-select" required>
                            <?php foreach ($dias as $d => $label): ?>
                            <option value="<?= $d ?>" <?= (int)($settings['dia_previsto_padrao'] ?? 1) === $d ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Dia do mês para abrir e executar vistorias de equipamentos sem dia próprio (1–28).</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="periodicidade_meses_padrao">Periodicidade padrão *</label>
                        <select name="periodicidade_meses_padrao" id="periodicidade_meses_padrao" class="form-select" required>
                            <?php foreach ($periodicidades as $meses => $label): ?>
                            <option value="<?= (int)$meses ?>" <?= (int)($settings['periodicidade_meses_padrao'] ?? 1) === (int)$meses ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Valor inicial ao cadastrar novo equipamento (pode ser alterado no cadastro).</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="dias_tolerancia_vencimento">Tolerância de vencimento (dias)</label>
                        <input type="number" min="0" max="30" name="dias_tolerancia_vencimento" id="dias_tolerancia_vencimento"
                               class="form-control" value="<?= (int)($settings['dias_tolerancia_vencimento'] ?? 0) ?>">
                        <div class="form-text">Dias após a data prevista antes de marcar como <em>Vencida</em>.</div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="gerar_vistoria_na_criacao" id="gerar_vistoria_na_criacao" value="1"
                                <?= !empty($settings['gerar_vistoria_na_criacao']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gerar_vistoria_na_criacao">
                                Gerar 1ª vistoria ao cadastrar equipamento ativo (competência atual)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
