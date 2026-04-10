<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$doc = $this->data['doc'] ?? [];
$docId = (int)($this->data['doc_id'] ?? 0);
$csrf = (string)($this->data['csrf_token'] ?? '');
$ttlMin = (int)($this->data['ttl_minutes'] ?? 15);
$title = (string)($doc['title'] ?? 'Documento');
?>
<div class="container-fluid px-3 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <h2 class="mt-3 h5">Confirmar palavra-passe para download</h2>
            <p class="text-muted small">Este tipo de documento exige reautenticação antes de transferir o PDF. A permissão vale cerca de <?= (int)$ttlMin ?> minutos.</p>

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <p class="fw-semibold mb-3"><?= htmlspecialchars($title) ?></p>
                    <form method="post" action="<?= htmlspecialchars($urlAdm) ?>confirm-payroll-document-download/<?= $docId ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <div class="mb-3">
                            <label for="pwd_dl" class="form-label">Palavra-passe</label>
                            <input type="password" class="form-control" id="pwd_dl" name="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary">Continuar para download</button>
                        <a href="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="btn btn-link btn-sm">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
