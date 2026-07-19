<?php

declare(strict_types=1);

$v = $this->data['vinculo'] ?? [];
$form = $this->data['form'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
$candidaturaId = (int) ($v['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Oferta</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? ''), ENT_QUOTES, 'UTF-8') ?>rh-candidatos">Candidatos</a></li>
            <li class="breadcrumb-item active">Nova oferta</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <p class="mb-3">
                Candidato: <strong><?= htmlspecialchars((string) ($v['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                · Vaga: <strong><?= htmlspecialchars((string) ($v['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                · Status vínculo: <span class="badge bg-success"><?= htmlspecialchars((string) ($v['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </p>

            <form method="post" action="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-create/' . $candidaturaId, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="salario_oferecido">Salário oferecido</label>
                        <input type="text" class="form-control" name="form[salario_oferecido]" id="salario_oferecido"
                               value="<?= htmlspecialchars((string) ($form['salario_oferecido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ex.: 3500,00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tipo_contrato">Tipo de contrato</label>
                        <input type="text" class="form-control" name="form[tipo_contrato]" id="tipo_contrato"
                               value="<?= htmlspecialchars((string) ($form['tipo_contrato'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="data_inicio_prevista">Início previsto</label>
                        <input type="date" class="form-control" name="form[data_inicio_prevista]" id="data_inicio_prevista"
                               value="<?= htmlspecialchars((string) ($form['data_inicio_prevista'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="validade_ate">Validade da oferta</label>
                        <input type="date" class="form-control" name="form[validade_ate]" id="validade_ate"
                               value="<?= htmlspecialchars((string) ($form['validade_ate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observacoes">Observações / condições</label>
                        <textarea class="form-control" name="form[observacoes]" id="observacoes" rows="3"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar rascunho</button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos-view/' . (int) ($v['rh_candidato_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
