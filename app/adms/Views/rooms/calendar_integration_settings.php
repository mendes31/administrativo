<?php
use App\adms\Helpers\FormatHelper;
$s = $this->data['settings'] ?? [];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-3 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Integração calendário (Outlook / Google)</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a></li>
            <li class="breadcrumb-item">Integração</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <p class="text-muted small">Estes campos prepararam a futura ligação a Microsoft 365 / Google Calendar. A sincronização automática de eventos ainda não está ativa no código.</p>

            <form method="post" class="row g-3" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <div class="col-12">
                    <h5 class="border-bottom pb-2">Microsoft Outlook / 365</h5>
                </div>
                <div class="col-md-6 form-check ms-3">
                    <input class="form-check-input" type="checkbox" name="outlook_sync_enabled" id="outlook_sync_enabled" value="1"
                        <?= !empty($s['outlook_sync_enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="outlook_sync_enabled">Ativar integração (quando disponível)</label>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="outlook_tenant_id">Tenant ID (Azure AD)</label>
                    <input type="text" class="form-control" id="outlook_tenant_id" name="outlook_tenant_id"
                           value="<?= htmlspecialchars((string)($s['outlook_tenant_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="outlook_client_id">Application (client) ID</label>
                    <input type="text" class="form-control" id="outlook_client_id" name="outlook_client_id"
                           value="<?= htmlspecialchars((string)($s['outlook_client_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 mt-4">
                    <h5 class="border-bottom pb-2">Google Calendar</h5>
                </div>
                <div class="col-md-6 form-check ms-3">
                    <input class="form-check-input" type="checkbox" name="google_sync_enabled" id="google_sync_enabled" value="1"
                        <?= !empty($s['google_sync_enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="google_sync_enabled">Ativar integração (quando disponível)</label>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="google_client_id">OAuth Client ID</label>
                    <input type="text" class="form-control" id="google_client_id" name="google_client_id"
                           value="<?= htmlspecialchars((string)($s['google_client_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>admin-booking-dashboard" class="btn btn-outline-secondary">Voltar ao dashboard de reservas</a>
                </div>

                <?php if (!empty($s['updated_at'])): ?>
                    <div class="col-12 small text-muted">
                        Última alteração: <?= FormatHelper::formatDateTime((string) $s['updated_at'], 'd/m/Y H:i'); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
