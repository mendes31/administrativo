<?php

declare(strict_types=1);

use App\adms\Models\Repository\RhConversoesAdmissaoRepository;

$o = $this->data['oferta'] ?? [];
$form = $this->data['form'] ?? [];
$pendencias = $this->data['pendencias_docs'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
$ofertaId = (int) ($o['id'] ?? 0);
$deps = $this->data['listDepartments'] ?? [];
$pos = $this->data['listPositions'] ?? [];
$supervisors = $this->data['listSupervisors'] ?? [];
$podeConverter = $pendencias === [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Converter oferta #<?= $ofertaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">Oferta</a>
            </li>
            <li class="breadcrumb-item active">Converter</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-3">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <p class="mb-2">
                Candidato: <strong><?= htmlspecialchars((string) ($o['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                · Vaga: <strong><?= htmlspecialchars((string) ($o['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
            <p class="small text-muted">
                A conversão cria ou vincula um registro em <code>adms_users</code> (fachada atual de Pessoa/Conta),
                marca o candidato como <em>contratado</em> e grava auditoria. Não cria tabelas Pessoa/Vínculo físicas neste incremento.
            </p>

            <?php if (!$podeConverter): ?>
                <div class="alert alert-warning">
                    Documentos obrigatórios pendentes de aprovação:
                    <ul class="mb-0">
                        <?php foreach ($pendencias as $p): ?>
                            <li><?= htmlspecialchars((string) $p, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-convert/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">
                    <label class="form-label">Modo</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="form[modo]" id="modo_criar" value="<?= RhConversoesAdmissaoRepository::MODO_CRIAR ?>"
                            <?= ($form['modo'] ?? '') === RhConversoesAdmissaoRepository::MODO_CRIAR ? 'checked' : '' ?>>
                        <label class="form-check-label" for="modo_criar">Criar novo usuário</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="form[modo]" id="modo_vincular" value="<?= RhConversoesAdmissaoRepository::MODO_VINCULAR ?>"
                            <?= ($form['modo'] ?? '') === RhConversoesAdmissaoRepository::MODO_VINCULAR ? 'checked' : '' ?>>
                        <label class="form-check-label" for="modo_vincular">Vincular usuário existente</label>
                    </div>
                </div>

                <div id="bloco-criar" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nome</label>
                        <input class="form-control" type="text" name="form[name]" id="name"
                               value="<?= htmlspecialchars((string) ($form['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">E-mail</label>
                        <input class="form-control" type="email" name="form[email]" id="email"
                               value="<?= htmlspecialchars((string) ($form['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="username">Usuário (login)</label>
                        <input class="form-control" type="text" name="form[username]" id="username"
                               value="<?= htmlspecialchars((string) ($form['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password">Senha inicial</label>
                        <input class="form-control" type="password" name="form[password]" id="password" autocomplete="new-password">
                        <div class="form-text">Mínimo 8 caracteres; pedirá troca no próximo login.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="data_admissao">Data de admissão</label>
                        <input class="form-control" type="date" name="form[data_admissao]" id="data_admissao"
                               value="<?= htmlspecialchars((string) ($form['data_admissao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="user_department_id">Departamento</label>
                        <select class="form-select" name="form[user_department_id]" id="user_department_id">
                            <option value="">Selecione…</option>
                            <?php foreach ($deps as $d): ?>
                                <option value="<?= (int) ($d['id'] ?? 0) ?>" <?= (string) ($form['user_department_id'] ?? '') === (string) ($d['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($d['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="user_position_id">Cargo</label>
                        <select class="form-select" name="form[user_position_id]" id="user_position_id">
                            <option value="">Selecione…</option>
                            <?php foreach ($pos as $p): ?>
                                <option value="<?= (int) ($p['id'] ?? 0) ?>" <?= (string) ($form['user_position_id'] ?? '') === (string) ($p['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="immediate_supervisor_id">Gestor imediato</label>
                        <select class="form-select" name="form[immediate_supervisor_id]" id="immediate_supervisor_id">
                            <option value="">Opcional…</option>
                            <?php foreach ($supervisors as $s): ?>
                                <option value="<?= (int) ($s['id'] ?? 0) ?>" <?= (string) ($form['immediate_supervisor_id'] ?? '') === (string) ($s['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="matricula">Matrícula</label>
                        <input class="form-control" type="text" name="form[matricula]" id="matricula"
                               value="<?= htmlspecialchars((string) ($form['matricula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="empresa_contratante">Empresa contratante</label>
                        <input class="form-control" type="text" name="form[empresa_contratante]" id="empresa_contratante"
                               value="<?= htmlspecialchars((string) ($form['empresa_contratante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="form[enviar_boas_vindas_email]" value="1" id="enviar_boas_vindas_email"
                                <?= !empty($form['enviar_boas_vindas_email']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enviar_boas_vindas_email">Marcar preferência de boas-vindas por e-mail</label>
                        </div>
                    </div>
                </div>

                <div id="bloco-vincular" class="row g-3 mt-1" style="display:none">
                    <div class="col-md-4">
                        <label class="form-label" for="adms_user_id">ID do usuário existente</label>
                        <input class="form-control" type="number" min="1" name="form[adms_user_id]" id="adms_user_id"
                               value="<?= htmlspecialchars((string) ($form['adms_user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label" for="observacoes">Observações da conversão</label>
                    <textarea class="form-control" name="form[observacoes]" id="observacoes" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" <?= $podeConverter ? '' : 'disabled' ?>
                            onclick="return confirm('Confirmar conversão desta oferta em colaborador?');">
                        Converter
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const criar = document.getElementById('modo_criar');
    const vincular = document.getElementById('modo_vincular');
    const blocoCriar = document.getElementById('bloco-criar');
    const blocoVincular = document.getElementById('bloco-vincular');
    function sync() {
        const isVincular = vincular && vincular.checked;
        if (blocoCriar) blocoCriar.style.display = isVincular ? 'none' : '';
        if (blocoVincular) blocoVincular.style.display = isVincular ? '' : 'none';
    }
    if (criar) criar.addEventListener('change', sync);
    if (vincular) vincular.addEventListener('change', sync);
    sync();
})();
</script>
