<?php
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0"><i class="fas fa-camera me-2"></i>Ler QR do equipamento</h2>
        <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias">Minhas vistorias</a></li>
            <li class="breadcrumb-item active">Ler QR</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3 p-md-4">
                    <p class="text-muted small mb-3">
                        Aponte a câmera para a etiqueta do equipamento. Ao reconhecer o código, o checklist da vistoria será aberto automaticamente.
                        No celular, permita o acesso à câmera quando o navegador solicitar.
                    </p>

                    <div id="sst-qr-insecure-hint" class="alert alert-warning small d-none mb-3">
                        <strong>Câmera bloqueada neste endereço.</strong>
                        Navegadores no celular só liberam a câmera em <strong>HTTPS</strong> ou em <code>localhost</code>.
                        Você está em <code id="sst-qr-current-origin"></code> (HTTP na rede local).
                        <ul class="mb-0 mt-2 ps-3">
                            <li><strong>Teste rápido:</strong> use o campo manual abaixo (cole o link ou token do QR).</li>
                            <li><strong>Teste com câmera no celular:</strong> acesse via HTTPS (produção, ngrok ou SSL no WAMP).</li>
                            <li><strong>No PC:</strong> <code>http://localhost/administrativo/sst-scan-equipamento</code> funciona com câmera.</li>
                        </ul>
                    </div>

                    <div id="sst-qr-reader-wrap" class="mx-auto mb-3" style="max-width: 420px;">
                        <div id="sst-qr-reader" class="rounded overflow-hidden border bg-dark"></div>
                    </div>

                    <div id="sst-qr-status" class="alert alert-secondary py-2 small mb-3" role="status">
                        Iniciando câmera…
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" id="sst-qr-toggle-cam" class="btn btn-outline-primary btn-sm d-none">
                            <i class="fas fa-sync-alt me-1"></i>Trocar câmera
                        </button>
                        <a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list me-1"></i>Ver fila de vistorias
                        </a>
                    </div>

                    <details class="small" id="sst-qr-manual-details">
                        <summary class="text-muted">Não conseguiu ler? Informe o código manualmente</summary>
                        <form id="sst-qr-manual-form" class="mt-2 row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label mb-1" for="sst-qr-manual-token">URL ou token do QR</label>
                                <input type="text" class="form-control form-control-sm" id="sst-qr-manual-token" placeholder="Cole o link ou o código de 32 caracteres" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Abrir vistoria</button>
                            </div>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var baseUrl = <?= json_encode($urlAdm, JSON_UNESCAPED_SLASHES) ?>;
    var scanPathPrefix = 'sst-scan-equipamento/';
    var readerElId = 'sst-qr-reader';
    var statusEl = document.getElementById('sst-qr-status');
    var toggleBtn = document.getElementById('sst-qr-toggle-cam');
    var scanner = null;
    var cameras = [];
    var cameraIndex = 0;
    var navigating = false;
    var insecureContext = !window.isSecureContext;

    function setStatus(msg, type) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'alert py-2 small mb-3 alert-' + (type || 'secondary');
    }

    function extractToken(raw) {
        raw = (raw || '').trim();
        if (!raw) return null;
        var m = raw.match(/\/sst-scan-equipamento\/([A-Za-z0-9]+)/);
        if (m) return m[1];
        if (/^[a-f0-9]{32}$/i.test(raw)) return raw;
        return null;
    }

    function goToToken(token) {
        if (!token || navigating) return;
        navigating = true;
        setStatus('QR reconhecido. Abrindo vistoria…', 'success');
        if (scanner && scanner.isScanning) {
            scanner.stop().finally(function () {
                window.location.href = baseUrl + scanPathPrefix + encodeURIComponent(token) + '?from=camera';
            }).catch(function () {
                window.location.href = baseUrl + scanPathPrefix + encodeURIComponent(token) + '?from=camera';
            });
            return;
        }
        window.location.href = baseUrl + scanPathPrefix + encodeURIComponent(token) + '?from=camera';
    }

    function onScanSuccess(decodedText) {
        var token = extractToken(decodedText);
        if (token) {
            goToToken(token);
        }
    }

    function pickBackCameraIndex(list) {
        for (var i = 0; i < list.length; i++) {
            var label = (list[i].label || '').toLowerCase();
            if (label.indexOf('back') >= 0 || label.indexOf('traseir') >= 0 || label.indexOf('rear') >= 0 || label.indexOf('environment') >= 0) {
                return i;
            }
        }
        return list.length > 1 ? list.length - 1 : 0;
    }

    function startCamera(index) {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('Leitor de QR indisponível neste navegador. Use o campo manual abaixo.', 'warning');
            return;
        }
        if (!cameras.length) {
            setStatus('Nenhuma câmera encontrada. Use o campo manual abaixo.', 'warning');
            return;
        }
        cameraIndex = ((index % cameras.length) + cameras.length) % cameras.length;
        var camId = cameras[cameraIndex].id;
        if (!scanner) {
            scanner = new Html5Qrcode(readerElId);
        }
        var config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
        var startPromise = scanner.isScanning
            ? scanner.stop().then(function () { return scanner.start(camId, config, onScanSuccess, function () {}); })
            : scanner.start(camId, config, onScanSuccess, function () {});
        startPromise.then(function () {
            setStatus('Câmera ativa. Aponte para o QR do equipamento.', 'info');
            if (cameras.length > 1 && toggleBtn) {
                toggleBtn.classList.remove('d-none');
            }
        }).catch(function (err) {
            setStatus('Não foi possível acessar a câmera: ' + (err && err.message ? err.message : 'permissão negada'), 'warning');
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            startCamera(cameraIndex + 1);
        });
    }

    var manualForm = document.getElementById('sst-qr-manual-form');
    if (manualForm) {
        manualForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = document.getElementById('sst-qr-manual-token');
            var token = extractToken(input ? input.value : '');
            if (!token) {
                setStatus('Cole a URL completa do QR ou o token de 32 caracteres.', 'danger');
                return;
            }
            goToToken(token);
        });
    }

    if (typeof Html5Qrcode === 'undefined') {
        setStatus('Biblioteca de leitura não carregou. Recarregue a página ou use o campo manual.', 'warning');
        return;
    }

    if (insecureContext) {
        var hint = document.getElementById('sst-qr-insecure-hint');
        var originEl = document.getElementById('sst-qr-current-origin');
        var wrap = document.getElementById('sst-qr-reader-wrap');
        var manualDetails = document.getElementById('sst-qr-manual-details');
        if (hint) hint.classList.remove('d-none');
        if (originEl) originEl.textContent = window.location.origin;
        if (wrap) wrap.classList.add('d-none');
        if (manualDetails) manualDetails.open = true;
        setStatus('Câmera indisponível em HTTP na rede local. Use o campo manual ou acesse via HTTPS/localhost.', 'warning');
        return;
    }

    Html5Qrcode.getCameras().then(function (list) {
        cameras = list || [];
        if (!cameras.length) {
            Html5Qrcode.getCameras({ facingMode: 'environment' }).then(function (list2) {
                cameras = list2 || [];
                if (!cameras.length) {
                    setStatus('Nenhuma câmera disponível. Use HTTPS ou o campo manual.', 'warning');
                    return;
                }
                startCamera(pickBackCameraIndex(cameras));
            }).catch(function () {
                setStatus('Permita o acesso à câmera ou use o campo manual.', 'warning');
            });
            return;
        }
        startCamera(pickBackCameraIndex(cameras));
    }).catch(function () {
        scanner = new Html5Qrcode(readerElId);
        var config = { fps: 10, qrbox: { width: 250, height: 250 } };
        scanner.start({ facingMode: 'environment' }, config, onScanSuccess, function () {})
            .then(function () { setStatus('Câmera ativa. Aponte para o QR do equipamento.', 'info'); })
            .catch(function (err) {
                setStatus('Não foi possível acessar a câmera: ' + (err && err.message ? err.message : 'verifique permissões'), 'warning');
            });
    });

    window.addEventListener('beforeunload', function () {
        if (scanner && scanner.isScanning) {
            scanner.stop().catch(function () {});
        }
    });
})();
</script>
