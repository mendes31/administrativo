<?php

use App\adms\Helpers\CSRFHelper;
// Exibe alertas de sucesso/erro
include './app/adms/Views/partials/alerts.php';
$form = $this->data['form'] ?? null;

// Definir exemplos fixos conforme o nível
$exemplos = [
    'Baixo' => 'abcd',
    'Médio' => 'Abcde1',
    'Elevado' => 'Abcde12@',
    'Customizado' => ''
];
$nivel = $form->nivel_seguranca ?? 'Baixo';
$exemplo_senha = $nivel !== 'Customizado' ? $exemplos[$nivel] : ($form->exemplo_senha ?? '');
?>
<div class="col-lg-10 mx-auto">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-4">Administração de Senhas</h3>
        </div>
        <div class="card-body">
            <div id="alert-area"></div>
            <form id="formPasswordPolicy" method="POST" action="<?= $_ENV['URL_ADM'] ?>ajax-password-policy">
                <input type="hidden" name="id" value="<?= $form->id ?? '' ?>">
                <div class="container-fluid">
                    <div class="row g-4 align-items-start">
                        <div class="col-md-6">
                            <div class="container-fluid">
                                <div class="row mb-3 align-items-center">
                                    <label for="vencimento_dias" class="col-md-6 col-form-label text-end">Vencimento após</label>
                                    <div class="col-auto">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;display:inline-block;" name="vencimento_dias" id="vencimento_dias" value="<?= $form->vencimento_dias ?? '' ?>">
                                        <span class="ms-1 unidade-campo">Dias</span>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="comprimento_minimo" class="col-md-6 col-form-label text-end">Comprimento mínimo</label>
                                    <div class="col-auto">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;display:inline-block;" name="comprimento_minimo" id="comprimento_minimo" value="<?= $form->comprimento_minimo ?? '' ?>">
                                        <span class="ms-1 unidade-campo">Caracteres</span>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="min_maiusculas" class="col-md-6 col-form-label text-end">Nº mínimo de caracteres maiúsculos</label>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="min_maiusculas" id="min_maiusculas" value="<?= $form->min_maiusculas ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="min_minusculas" class="col-md-6 col-form-label text-end">Nº mínimo de caracteres minúsculos</label>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="min_minusculas" id="min_minusculas" value="<?= $form->min_minusculas ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="min_digitos" class="col-md-6 col-form-label text-end">Nº mínimo de dígitos</label>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="min_digitos" id="min_digitos" value="<?= $form->min_digitos ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="min_nao_alfanumericos" class="col-md-6 col-form-label text-end">Nº mínimo de caracteres não alfanuméricos</label>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="min_nao_alfanumericos" id="min_nao_alfanumericos" value="<?= $form->min_nao_alfanumericos ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="historico_senhas" class="col-md-6 col-form-label text-end">Senha não pode coincidir</label>
                                    <div class="col-auto">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;display:inline-block;" name="historico_senhas" id="historico_senhas" value="<?= $form->historico_senhas ?? '' ?>">
                                        <span class="ms-1 unidade-campo">Senhas anteriores</span>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="tentativas_bloqueio" class="col-md-6 col-form-label text-end">Autenticações antes da conta ser bloqueada</label>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="tentativas_bloqueio" id="tentativas_bloqueio" value="<?= $form->tentativas_bloqueio ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="exemplo_senha" class="col-md-6 col-form-label text-end">Exemplo de senha</label>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm w-auto" style="max-width:120px;" name="exemplo_senha" id="exemplo_senha" value="<?= $form->exemplo_senha ?? '' ?>" <?= ($form->nivel_seguranca ?? '') === 'Customizado' ? '' : 'readonly' ?>>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <label for="nivel_seguranca" class="col-md-6 col-form-label text-end">Nível de segurança</label>
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm w-auto" style="max-width:120px;" name="nivel_seguranca" id="nivel_seguranca">
                                            <option value="Baixo" <?= ($form->nivel_seguranca ?? '') === 'Baixo' ? 'selected' : '' ?>>Baixo</option>
                                            <option value="Médio" <?= ($form->nivel_seguranca ?? '') === 'Médio' ? 'selected' : '' ?>>Médio</option>
                                            <option value="Elevado" <?= ($form->nivel_seguranca ?? '') === 'Elevado' ? 'selected' : '' ?>>Elevado</option>
                                            <option value="Customizado" <?= ($form->nivel_seguranca ?? '') === 'Customizado' ? 'selected' : '' ?>>Customizado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="bloqueio_temporario" name="bloqueio_temporario" value="Sim" <?= (isset($form->bloqueio_temporario) && $form->bloqueio_temporario == 'Sim') ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="bloqueio_temporario">Bloqueio temporário?</label>
                                </div>
                            </div>
                            <div class="mb-3" id="tentativas_bloqueio_temporario_box" style="<?= ($form->bloqueio_temporario ?? 'Não') === 'Sim' ? '' : 'display:none;' ?>;">
                                <label class="form-label" for="tentativas_bloqueio_temporario">Tentativas antes do bloqueio temporário</label>
                                <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="tentativas_bloqueio_temporario" id="tentativas_bloqueio_temporario" value="<?= $form->tentativas_bloqueio_temporario ?? 3 ?>" min="1" required>
                            </div>
                            <div class="mb-3" id="campo_tempo_bloqueio" style="display:<?= (isset($form->bloqueio_temporario) && $form->bloqueio_temporario == 'Sim') ? 'block' : 'none' ?>;">
                                <label class="form-label" for="tempo_bloqueio_temporario">Tempo de bloqueio temporário (minutos)</label>
                                <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="tempo_bloqueio_temporario" id="tempo_bloqueio_temporario" value="<?= $form->tempo_bloqueio_temporario ?? 15 ?>">
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notificar_usuario_bloqueio" name="notificar_usuario_bloqueio" value="Sim" <?= (isset($form->notificar_usuario_bloqueio) && $form->notificar_usuario_bloqueio == 'Sim') ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="notificar_usuario_bloqueio">Notificar usuário ao bloquear?</label>
                                </div>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notificar_admins_bloqueio" name="notificar_admins_bloqueio" value="Sim" <?= (isset($form->notificar_admins_bloqueio) && $form->notificar_admins_bloqueio == 'Sim') ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="notificar_admins_bloqueio">Notificar todos admins ao bloquear?</label>
                                </div>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="forcar_logout_troca_senha" name="forcar_logout_troca_senha" value="Sim" <?= (isset($form->forcar_logout_troca_senha) && $form->forcar_logout_troca_senha == 'Sim') ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="forcar_logout_troca_senha">Forçar logout ao trocar senha?</label>
                                </div>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="expirar_sessao_por_tempo" name="expirar_sessao_por_tempo" value="Sim" <?= (isset($form->expirar_sessao_por_tempo) && $form->expirar_sessao_por_tempo == 'Sim') ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="expirar_sessao_por_tempo">Expirar sessão por tempo?</label>
                                </div>
                            </div>
                            <div class="mb-3" id="campo_tempo_expiracao_sessao" style="display:<?= (isset($form->expirar_sessao_por_tempo) && $form->expirar_sessao_por_tempo == 'Sim') ? 'block' : 'none' ?>;">
                                <label class="form-label" for="tempo_expiracao_sessao">Tempo de expiração da sessão (minutos)</label>
                                <input type="number" class="form-control form-control-sm w-auto" style="max-width:90px;" name="tempo_expiracao_sessao" id="tempo_expiracao_sessao" value="<?= $form->tempo_expiracao_sessao ?? 30 ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button type="submit" id="btnSalvar" class="btn btn-success">Salvar Configurações</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nivelSelect = document.getElementById('nivel_seguranca');
    const vencimentoInput = document.getElementById('vencimento_dias');
    const vencimentoUnidade = document.getElementById('vencimento-unidade');
    const exemploSenhaInput = document.getElementById('exemplo_senha');

    // Valores padrão para cada nível
    const niveis = {
        'Baixo': {
            vencimento_dias: '',
            comprimento_minimo: 4,
            min_maiusculas: 0,
            min_minusculas: 0,
            min_digitos: 0,
            min_nao_alfanumericos: 0,
            historico_senhas: 0,
            tentativas_bloqueio: 5,
            exemplo_senha: 'abcd',
        },
        'Médio': {
            vencimento_dias: 90,
            comprimento_minimo: 6,
            min_maiusculas: 1,
            min_minusculas: 0,
            min_digitos: 1,
            min_nao_alfanumericos: 0,
            historico_senhas: 3,
            tentativas_bloqueio: 3,
            exemplo_senha: 'Abcde1',
        },
        'Elevado': {
            vencimento_dias: 30,
            comprimento_minimo: 8,
            min_maiusculas: 1,
            min_minusculas: 1,
            min_digitos: 1,
            min_nao_alfanumericos: 1,
            historico_senhas: 5,
            tentativas_bloqueio: 3,
            exemplo_senha: 'Abcde12@',
        },
        'Customizado': null // Não altera nada
    };

    function toggleCamposNivel() {
        const nivel = nivelSelect.value;
        if (nivel === 'Baixo') {
            vencimentoInput.value = '';
            vencimentoInput.disabled = true;
            if (vencimentoUnidade) vencimentoUnidade.style.display = 'none';
        } else {
            vencimentoInput.disabled = false;
            if (vencimentoUnidade) vencimentoUnidade.style.display = '';
        }
        if (nivel === 'Customizado') {
            exemploSenhaInput.readOnly = false;
        } else {
            exemploSenhaInput.readOnly = true;
        }
    }

    function atualizarCamposPorNivel() {
        const nivel = nivelSelect.value;
        if (niveis[nivel] && nivel !== 'Customizado') {
            for (const campo in niveis[nivel]) {
                const input = document.getElementById(campo);
                if (input) input.value = niveis[nivel][campo];
            }
        }
        toggleCamposNivel();
    }

    nivelSelect.addEventListener('change', function() {
        atualizarCamposPorNivel();
    });
    // Ao carregar a página, só ajusta os campos de acordo com o nível, mas mantém valores do banco
    toggleCamposNivel();

    // Controle do toggle de bloqueio temporário
    const toggleBloqueio = document.getElementById('bloqueio_temporario');
    const campoTempo = document.getElementById('campo_tempo_bloqueio');
    const tentativasBox = document.getElementById('tentativas_bloqueio_temporario_box');
    function atualizarCamposBloqueioTemporario() {
        if (toggleBloqueio.checked) {
            campoTempo.style.display = 'block';
            if (tentativasBox) tentativasBox.style.display = '';
        } else {
            campoTempo.style.display = 'none';
            if (tentativasBox) tentativasBox.style.display = 'none';
        }
    }
    if (toggleBloqueio) {
        toggleBloqueio.addEventListener('change', atualizarCamposBloqueioTemporario);
        atualizarCamposBloqueioTemporario();
    }

    // Controle do toggle de expiração de sessão por tempo
    const toggleExpirarSessao = document.getElementById('expirar_sessao_por_tempo');
    const campoTempoExpiracao = document.getElementById('campo_tempo_expiracao_sessao');
    function atualizarCampoTempoExpiracao() {
        if (toggleExpirarSessao && campoTempoExpiracao) {
            campoTempoExpiracao.style.display = toggleExpirarSessao.checked ? 'block' : 'none';
        }
    }
    if (toggleExpirarSessao) {
        toggleExpirarSessao.addEventListener('change', atualizarCampoTempoExpiracao);
        atualizarCampoTempoExpiracao();
    }

    // Após salvar, recarregar a página para exibir os novos valores
    const form = document.getElementById('formPasswordPolicy');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvar');
        btnSalvar.disabled = true;
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            btnSalvar.disabled = false;
            if (data.success) {
                location.reload();
            } else {
                document.getElementById('alert-area').innerHTML = `<div class='alert alert-danger'>${data.message}</div>`;
            }
        })
        .catch(() => {
            btnSalvar.disabled = false;
            document.getElementById('alert-area').innerHTML = `<div class='alert alert-danger'>Erro ao salvar. Tente novamente.</div>`;
        });
    });
});
</script>
<style>
    @media (min-width: 992px) {
        .card .row.g-4 > .col-md-6 {
            border-right: 1px solid #eee;
            padding-right: 32px;
        }
        .card .row.g-4 > .col-md-6:last-child {
            border-right: none;
            padding-left: 32px;
        }
    }
    .card .form-label {
        font-weight: 500;
    }
    .card .form-control, .card .form-select {
        min-width: 80px;
    }
    .unidade-campo {
        font-size: 0.95em;
        color: #555;
        vertical-align: middle;
    }
</style> 