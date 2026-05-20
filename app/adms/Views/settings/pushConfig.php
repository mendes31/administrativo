<?php

use App\adms\Helpers\CSRFHelper;

$config = $this->data['push_config'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_push_config');
$csrfGenerate = $this->data['csrf_generate_token'] ?? CSRFHelper::generateCSRFToken('form_push_vapid_generate');
$csrfTest = $this->data['csrf_test_token'] ?? CSRFHelper::generateCSRFToken('form_push_test');
$csrfPushSubscribe = $this->data['csrf_push_subscribe'] ?? CSRFHelper::generateCSRFToken('form_push_subscribe');

$hasPublicKey = trim((string) ($config['vapid_public_key'] ?? '')) !== '';
$hasPrivateKey = trim((string) ($config['vapid_private_key'] ?? '')) !== '';
$defaultSubject = 'mailto:' . (string) ($_ENV['EMAIL_TI'] ?? 'chamados@tiaraju.com.br');
?>

<div class="container-fluid px-2 px-sm-3 px-md-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">
            <i class="fas fa-bell me-2"></i>Configuração Push (PWA)
        </h2>
        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center mb-3 mt-3">
            <?php
            $log_resumo = $this->data['log_resumo'] ?? [];
            $log_btn_class = 'btn btn-outline-info btn-sm';
            include __DIR__ . '/../partials/button_log_alteracoes.php';
            ?>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item">Configurações</li>
                <li class="breadcrumb-item active">Push (PWA)</li>
            </ol>
        </div>
    </div>

    <div class="row g-3 g-md-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Parâmetros VAPID</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-push-config">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-3">
                            <label class="form-label" for="vapid_subject">Subject VAPID *</label>
                            <input type="text"
                                   class="form-control"
                                   id="vapid_subject"
                                   name="vapid_subject"
                                   required
                                   placeholder="<?= htmlspecialchars($defaultSubject, ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars($config['vapid_subject'] ?? $defaultSubject, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">
                                Identificador do remetente push. Use <code>mailto:seu-email@dominio</code> ou uma URL <code>https://</code>.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chave pública VAPID</label>
                            <textarea class="form-control font-monospace small" rows="3" readonly><?= htmlspecialchars($config['vapid_public_key'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">
                                <?= $hasPublicKey ? 'Chave configurada. Gere um novo par apenas se necessário.' : 'Nenhuma chave configurada. Use o botão ao lado para gerar.' ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chave privada VAPID</label>
                            <input type="text"
                                   class="form-control font-monospace"
                                   readonly
                                   value="<?= $hasPrivateKey ? '******** (armazenada com segurança)' : 'Não configurada' ?>">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_enabled"
                                   id="push_is_enabled"
                                   value="1"
                                   <?= !empty($config['is_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="push_is_enabled">
                                Ativar notificações push (Web Push / PWA)
                            </label>
                        </div>

                        <?php if (!empty($this->data['buttonPermission']) && in_array('SavePushConfig', $this->data['buttonPermission'], true)): ?>
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-success btn-lg flex-md-grow-1">
                                <i class="fas fa-save me-2"></i>Salvar Configuração
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <?php if (!empty($this->data['buttonPermission']) && in_array('GeneratePushVapidKeys', $this->data['buttonPermission'], true)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-key me-2"></i>Chaves VAPID</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Gere um par de chaves VAPID para autenticar o envio de notificações push aos navegadores dos usuários.
                    </p>
                    <form method="POST"
                          action="<?= $_ENV['URL_ADM'] ?>generate-push-vapid-keys"
                          onsubmit="return confirm('Gerar novas chaves VAPID? Dispositivos já inscritos precisarão reativar as notificações em Meu Perfil.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfGenerate, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="fas fa-sync-alt me-2"></i>Gerar par de chaves
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['buttonPermission']) && in_array('TestPushNotification', $this->data['buttonPermission'], true)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Testar envio</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Envia teste para <strong>este navegador</strong> (se ativo em Meu Perfil). Se não houver inscrição aqui, envia para todos os dispositivos cadastrados do seu usuário.
                    </p>
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>test-push-notification" id="formPushTest">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTest, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="test_endpoint" id="pushTestEndpoint" value="">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-paper-plane me-2"></i>Enviar notificação de teste
                        </button>
                    </form>
                    <p class="small text-muted mt-2 mb-0" id="pushTestDeviceHint"></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Como funciona</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Configure VAPID e ative o push nesta tela.</li>
                        <li>Cada colaborador ativa as notificações em <strong>Meu Perfil</strong>.</li>
                        <li>O Service Worker recebe o push mesmo com o app em segundo plano (PWA).</li>
                        <li>As notificações in-app (sino) continuam independentes desta funcionalidade.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var urlAdm = <?= json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), JSON_UNESCAPED_SLASHES) ?>;
    var csrfSubscribe = <?= json_encode($csrfPushSubscribe, JSON_UNESCAPED_UNICODE) ?>;
    var hint = document.getElementById('pushTestDeviceHint');
    var endpointInput = document.getElementById('pushTestEndpoint');
    var testForm = document.getElementById('formPushTest');

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        if (hint) {
            hint.textContent = 'Push não suportado neste navegador.';
        }
        return;
    }

    function buildPayload(registration, subscription) {
        var json = subscription.toJSON();
        var encodings = registration.pushManager.supportedContentEncodings || [];
        if (encodings.length > 0) {
            json.contentEncoding = encodings[0];
        }
        return json;
    }

    function syncSubscription(registration, subscription) {
        return fetch(urlAdm + '/push-subscribe/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: csrfSubscribe,
                subscription: buildPayload(registration, subscription)
            })
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    throw new Error((data && data.message) || 'Falha ao sincronizar push.');
                }
                return data;
            });
        });
    }

    function refreshTestHint(registration, subscription) {
        if (!hint || !endpointInput) {
            return;
        }
        if (!subscription || !subscription.endpoint) {
            endpointInput.value = '';
            hint.textContent = 'Modo: teste em todos os dispositivos cadastrados. Ative em Meu Perfil neste aparelho para incluir este navegador.';
            return;
        }
        var endpoint = subscription.endpoint;
        fetch(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (data.endpointRegistered) {
                endpointInput.value = endpoint;
                hint.textContent = 'Modo: teste somente neste navegador/dispositivo.';
            } else {
                endpointInput.value = '';
                hint.textContent = 'Sincronizando push deste navegador com o servidor…';
                return syncSubscription(registration, subscription).then(function () {
                    endpointInput.value = endpoint;
                    hint.textContent = 'Push sincronizado. Modo: teste somente neste navegador/dispositivo.';
                }).catch(function () {
                    hint.textContent = 'Abra Meu Perfil neste aparelho, toque Ativar notificações, e teste de novo.';
                });
            }
        }).catch(function () {
            hint.textContent = 'Não foi possível verificar inscrição push neste navegador.';
        });
    }

    var registrationPromise = navigator.serviceWorker.register(urlAdm + '/service-worker.js')
        .then(function () { return navigator.serviceWorker.ready; });

    registrationPromise.then(function (registration) {
        return registration.pushManager.getSubscription().then(function (subscription) {
            refreshTestHint(registration, subscription);
            return { registration: registration, subscription: subscription };
        });
    }).catch(function () {
        if (hint) {
            hint.textContent = 'Não foi possível registrar o Service Worker.';
        }
    });

    if (testForm) {
        testForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitBtn = testForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            registrationPromise.then(function (registration) {
                return registration.pushManager.getSubscription().then(function (subscription) {
                    if (subscription) {
                        return syncSubscription(registration, subscription).then(function () {
                            if (endpointInput) {
                                endpointInput.value = subscription.endpoint;
                            }
                        });
                    }
                });
            }).then(function () {
                testForm.submit();
            }).catch(function () {
                testForm.submit();
            }).finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });
    }
})();
</script>
