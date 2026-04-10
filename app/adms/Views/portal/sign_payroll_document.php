<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$doc = isset($this->data['doc']) && is_array($this->data['doc']) ? $this->data['doc'] : [];
$csrf = (string)($this->data['csrf_token'] ?? '');
$infoOnly = $this->data['info_only'] ?? null;
$docId = (int)($doc['id'] ?? 0);
$title = (string)($doc['title'] ?? '');
$ver = (int)($doc['document_version'] ?? 1);
$hash = (string)($doc['file_hash_sha256'] ?? '');
$hashShort = $hash !== '' ? substr($hash, 0, 12) . '…' : '—';
$auth = (string)($doc['signature_auth_snapshot'] ?? 'none');
$sigStatus = (string)($doc['signature_status'] ?? '');

$authHelp = match ($auth) {
    'password' => 'Será pedida a sua palavra-passe de acesso ao sistema.',
    'otp_whatsapp' => 'Enviaremos um código de 6 dígitos por WhatsApp (número do cadastro).',
    'otp_email' => 'Enviaremos um código de 6 dígitos para o seu e-mail cadastrado.',
    'otp_whatsapp_fallback_email' => 'Tentamos WhatsApp primeiro; se falhar, o código é enviado por e-mail.',
    default => 'Basta confirmar o botão abaixo.',
};

$showOtp = str_contains($auth, 'otp');
$showPassword = $auth === 'password';
$showSimple = $auth === 'none';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
                <h2 class="mt-3 mb-0">Confirmar recebimento</h2>
                <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="text-decoration-none">Meus documentos</a></li>
                    <li class="breadcrumb-item active">Ciência</li>
                </ol>
            </div>

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-md-4">
                    <h3 class="h6 fw-semibold mb-3"><?= htmlspecialchars($title) ?></h3>
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><strong>Versão:</strong> <?= (int)$ver ?></li>
                        <li><strong>Hash (SHA-256, resumo):</strong> <code class="small"><?= htmlspecialchars($hashShort) ?></code></li>
                    </ul>

                    <?php if ($infoOnly !== null): ?>
                        <?php
                        $isSignedMsg = $sigStatus === 'signed';
                        ?>
                        <div class="alert <?= $isSignedMsg ? 'alert-success' : 'alert-info' ?> mb-0"><?= htmlspecialchars($infoOnly) ?></div>
                        <?php if ($isSignedMsg): ?>
                            <a href="<?= htmlspecialchars($urlAdm) ?>payroll-signature-receipt/<?= $docId ?>" class="btn btn-outline-primary btn-sm mt-3" target="_blank" rel="noopener">Baixar comprovante (PDF)</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="small mb-3"><?= htmlspecialchars($authHelp) ?></p>

                        <form method="post" action="<?= htmlspecialchars($urlAdm) ?>sign-payroll-document/<?= $docId ?>" class="d-flex flex-column gap-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                            <?php if ($showSimple): ?>
                                <input type="hidden" name="action" value="confirm_simple">
                                <button type="submit" class="btn btn-primary">Confirmo o recebimento deste documento</button>
                            <?php endif; ?>

                            <?php if ($showPassword): ?>
                                <input type="hidden" name="action" value="confirm_password">
                                <div>
                                    <label class="form-label" for="pwd_sign">Palavra-passe</label>
                                    <input type="password" class="form-control" id="pwd_sign" name="password" required autocomplete="current-password">
                                </div>
                                <button type="submit" class="btn btn-primary">Confirmar com palavra-passe</button>
                            <?php endif; ?>

                            <?php if ($showOtp): ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="submit" name="action" value="request_otp" class="btn btn-outline-primary btn-sm">Enviar código</button>
                                </div>
                                <div>
                                    <label class="form-label" for="otp_code">Código de 6 dígitos</label>
                                    <input type="text" class="form-control" id="otp_code" name="otp_code" inputmode="numeric" maxlength="12" pattern="[0-9]*" autocomplete="one-time-code" placeholder="000000">
                                </div>
                                <button type="submit" name="action" value="verify_otp" class="btn btn-primary">Validar código e confirmar</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $docId ?>" class="btn btn-link btn-sm px-0" target="_blank" rel="noopener">Abrir PDF para conferência</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
