<?php

declare(strict_types=1);

use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\RhConversoesAdmissaoRepository;

$o = $this->data['oferta'] ?? [];
$form = $this->data['form'] ?? [];
$pendencias = $this->data['pendencias_docs'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
$ofertaId = (int) ($o['id'] ?? 0);
$candidatoId = (int) ($o['rh_candidato_id'] ?? 0);
$deps = $this->data['listDepartments'] ?? [];
$pos = $this->data['listPositions'] ?? [];
$supervisors = $this->data['listSupervisors'] ?? [];
$podeConverter = $pendencias === [];
$baseAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
$modoAtual = (string) ($form['modo'] ?? RhConversoesAdmissaoRepository::MODO_CRIAR);
$isCriar = $modoAtual === RhConversoesAdmissaoRepository::MODO_CRIAR;
$empVal = (string) ($form['empresa_contratante'] ?? '');
$empresas = UserFormHelper::empresaContratanteOptions();
$convertFlash = $this->data['convert_flash'] ?? null;
$flashCode = is_array($convertFlash) ? (string) ($convertFlash['code'] ?? '') : '';
$flashIsEmail = $flashCode === 'email_exists';
$flashUserId = is_array($convertFlash) ? (int) ($convertFlash['user_id'] ?? 0) : 0;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3 mb-0">Converter oferta #<?= $ofertaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto flex-shrink-0">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($baseAdm . '/rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Oferta</a>
            </li>
            <li class="breadcrumb-item active">Converter</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (is_array($convertFlash) && !empty($convertFlash['message'])): ?>
        <div class="alert <?= $flashIsEmail ? 'alert-warning' : 'alert-danger' ?> border-0 shadow-sm mb-4 d-flex gap-3 align-items-start">
            <div class="rounded-circle <?= $flashIsEmail ? 'bg-warning bg-opacity-25 text-warning' : 'bg-danger bg-opacity-10 text-danger' ?> d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:2.5rem;height:2.5rem;">
                <i class="fas <?= $flashIsEmail ? 'fa-user-friends' : 'fa-exclamation-circle' ?>"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="h6 mb-1"><?= htmlspecialchars((string) ($convertFlash['title'] ?? 'Atenção'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="small text-muted mb-0">
                    <?= htmlspecialchars((string) $convertFlash['message'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if ($flashIsEmail && $flashUserId > 0): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge rounded-pill text-bg-light border">
                            <i class="fas fa-hashtag me-1"></i>Usuário #<?= $flashUserId ?>
                        </span>
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
                            Modo Vincular selecionado
                        </span>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= htmlspecialchars($baseAdm . '/view-user/' . $flashUserId, ENT_QUOTES, 'UTF-8') ?>"
                           target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt me-1"></i>Ver usuário
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0f9ff 45%, #f8fafc 100%);">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:3.25rem;height:3.25rem;">
                        <i class="fas fa-user-check fa-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                            <span class="badge rounded-pill text-bg-light border text-muted">Conversão</span>
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Oferta aceita</span>
                            <?php if ($podeConverter): ?>
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Docs OK</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle">Docs pendentes</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="h4 mb-1 text-truncate"><?= htmlspecialchars((string) ($o['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="text-muted small">
                            <i class="fas fa-briefcase me-1"></i><?= htmlspecialchars((string) ($o['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            Cria ou vincula o colaborador no sistema, marca o candidato como contratado e inicia o onboarding.
                        </p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary btn-sm"
                       href="<?= htmlspecialchars($baseAdm . '/rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-arrow-left me-1"></i>Voltar à oferta
                    </a>
                    <?php if ($candidatoId > 0): ?>
                        <a class="btn btn-outline-secondary btn-sm"
                           href="<?= htmlspecialchars($baseAdm . '/rh-candidatos-view/' . $candidatoId, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fas fa-user me-1"></i>Candidato
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

            <?php if (!$podeConverter): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex gap-3 align-items-start">
            <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:2.5rem;height:2.5rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="h6 mb-1">Ainda faltam documentos obrigatórios</h3>
                <p class="small mb-2 text-muted">Aprove estes itens na checklist da oferta antes de criar o colaborador:</p>
                <ul class="mb-3 small">
                    <?php foreach ($pendencias as $p): ?>
                        <li><?= htmlspecialchars((string) $p, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-sm btn-warning"
                   href="<?= htmlspecialchars($baseAdm . '/rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-clipboard-check me-1"></i>Abrir checklist da oferta
                </a>
            </div>
        </div>
    <?php endif; ?>

    <form id="formRhOfertaConvert" method="post" action="<?= htmlspecialchars($baseAdm . '/rh-ofertas-convert/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>" class="mb-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="vstack gap-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h3 class="h6 mb-0"><i class="fas fa-exchange-alt me-2 text-success"></i>Modo da conversão</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="border rounded-3 p-3 h-100 w-100 d-block <?= $isCriar ? 'border-success bg-success-subtle' : '' ?>"
                                   style="cursor:pointer;" for="modo_criar">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="form[modo]" id="modo_criar"
                                           value="<?= RhConversoesAdmissaoRepository::MODO_CRIAR ?>"
                                        <?= $isCriar ? 'checked' : '' ?>>
                                    <span class="form-check-label fw-semibold">Criar novo usuário</span>
                                </div>
                                <div class="small text-muted mt-2 ps-4">
                                    Gera login, senha inicial e vínculo organizacional a partir dos dados do candidato.
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="border rounded-3 p-3 h-100 w-100 d-block <?= !$isCriar ? 'border-success bg-success-subtle' : '' ?>"
                                   style="cursor:pointer;" for="modo_vincular">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="form[modo]" id="modo_vincular"
                                           value="<?= RhConversoesAdmissaoRepository::MODO_VINCULAR ?>"
                                        <?= !$isCriar ? 'checked' : '' ?>>
                                    <span class="form-check-label fw-semibold">Vincular usuário existente</span>
                                </div>
                                <div class="small text-muted mt-2 ps-4">
                                    Use quando o colaborador já possui conta no sistema (informe o ID).
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bloco-criar" class="vstack gap-4" style="<?= $isCriar ? '' : 'display:none' ?>">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h3 class="h6 mb-0"><i class="fas fa-id-card me-2 text-success"></i>Conta de acesso</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
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
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="form[enviar_boas_vindas_email]" value="1" id="enviar_boas_vindas_email"
                                        <?= !empty($form['enviar_boas_vindas_email']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enviar_boas_vindas_email">Marcar preferência de boas-vindas por e-mail</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h3 class="h6 mb-0"><i class="fas fa-sitemap me-2 text-success"></i>Vínculo organizacional</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
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
                                <label class="form-label" for="empresa_contratante">Empresa contratante <span class="text-danger">*</span></label>
                                <select class="form-select" name="form[empresa_contratante]" id="empresa_contratante" required>
                                    <option value="" <?= $empVal === '' ? 'selected' : '' ?>>Selecione...</option>
                                    <?php foreach ($empresas as $slug => $empLabel): ?>
                                        <option value="<?= htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $empVal === (string) $slug ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $empLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Filiais ativas do cadastro.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bloco-vincular" class="card border-0 shadow-sm" style="<?= $isCriar ? 'display:none' : '' ?>">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h3 class="h6 mb-0"><i class="fas fa-link me-2 text-success"></i>Usuário existente</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="adms_user_id">ID do usuário</label>
                            <input class="form-control" type="number" min="1" name="form[adms_user_id]" id="adms_user_id"
                                   value="<?= htmlspecialchars((string) ($form['adms_user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Consulte o ID em Usuários / Colaboradores.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h3 class="h6 mb-0"><i class="fas fa-sticky-note me-2 text-success"></i>Observações</h3>
                </div>
                <div class="card-body">
                    <label class="form-label" for="observacoes">Observações da conversão</label>
                    <textarea class="form-control" name="form[observacoes]" id="observacoes" rows="3"
                              placeholder="Opcional — registro interno do RH"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                        <?php if ($podeConverter): ?>
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:2.5rem;height:2.5rem;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-success">Tudo certo para converter</div>
                                    <div class="small text-muted mb-0">
                                        Documentos aprovados. Ao confirmar, o candidato vira colaborador e o onboarding é iniciado.
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:2.5rem;height:2.5rem;">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Conversão bloqueada</div>
                                    <div class="small text-muted mb-0">
                                        Aprove os documentos obrigatórios na oferta para liberar o botão Converter.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-secondary"
                               href="<?= htmlspecialchars($baseAdm . '/rh-ofertas-view/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
                            <button type="button"
                                    class="btn btn-primary"
                                    id="btnAbrirConfirmConversao"
                                    <?= $podeConverter ? '' : 'disabled' ?>>
                                <i class="fas fa-user-check me-1"></i>Converter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalConfirmConversao" tabindex="-1" aria-labelledby="modalConfirmConversaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:2.75rem;height:2.75rem;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h2 class="modal-title h5 mb-0" id="modalConfirmConversaoLabel">Confirmar conversão</h2>
                        <div class="small text-muted">Oferta #<?= $ofertaId ?></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3">
                    Deseja converter
                    <strong><?= htmlspecialchars((string) ($o['candidato_nome'] ?? 'este candidato'), ENT_QUOTES, 'UTF-8') ?></strong>
                    em colaborador?
                </p>
                <div class="rounded-3 border bg-light p-3 small">
                    <div class="mb-2">
                        <span class="text-muted">Vaga</span><br>
                        <strong><?= htmlspecialchars((string) ($o['vaga_titulo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <ul class="mb-0 ps-3 text-muted">
                        <li>Cria ou vincula a conta no sistema</li>
                        <li>Marca o candidato como <em>contratado</em></li>
                        <li>Inicia o plano de onboarding</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarConversao">
                    <i class="fas fa-check me-1"></i>Sim, converter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formRhOfertaConvert');
    const criar = document.getElementById('modo_criar');
    const vincular = document.getElementById('modo_vincular');
    const blocoCriar = document.getElementById('bloco-criar');
    const blocoVincular = document.getElementById('bloco-vincular');
    const empresa = document.getElementById('empresa_contratante');
    const btnAbrir = document.getElementById('btnAbrirConfirmConversao');
    const btnConfirm = document.getElementById('btnConfirmarConversao');
    const modalEl = document.getElementById('modalConfirmConversao');
    const cards = [criar, vincular].filter(Boolean).map(function (el) {
        return el.closest('label');
    });

    function sync() {
        const isVincular = !!(vincular && vincular.checked);
        if (blocoCriar) blocoCriar.style.display = isVincular ? 'none' : '';
        if (blocoVincular) blocoVincular.style.display = isVincular ? '' : 'none';
        if (empresa) {
            if (isVincular) {
                empresa.removeAttribute('required');
            } else {
                empresa.setAttribute('required', 'required');
            }
        }
        cards.forEach(function (card) {
            if (!card) return;
            const radio = card.querySelector('input[type="radio"]');
            const checked = !!(radio && radio.checked);
            card.classList.toggle('border-success', checked);
            card.classList.toggle('bg-success-subtle', checked);
        });
    }

    function showModal() {
        if (!modalEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }

    function hideModal() {
        if (!modalEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
            return;
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    if (criar) criar.addEventListener('change', sync);
    if (vincular) vincular.addEventListener('change', sync);
    sync();

    if (btnAbrir && form) {
        btnAbrir.addEventListener('click', function () {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                form.reportValidity();
                return;
            }
            showModal();
        });
    }

    if (btnConfirm && form) {
        btnConfirm.addEventListener('click', function () {
            hideModal();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    if (modalEl) {
        modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (btn) {
            btn.addEventListener('click', hideModal);
        });
    }
});
</script>
