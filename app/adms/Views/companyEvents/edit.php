<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;

$ev = $this->data['event'] ?? [];
$deps = $this->data['departments'] ?? [];
$id = (int)($ev['id'] ?? 0);
$urlAdm = $_ENV['URL_ADM'] ?? '';
$fmt = static function ($v): string {
    if (empty($v)) {
        return '';
    }
    return date('Y-m-d\TH:i', strtotime((string)$v));
};
?>
<div class="container-fluid px-4 company-event-form">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Editar evento corporativo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events" class="text-decoration-none">Eventos corporativos</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar evento corporativo</h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRFHelper::generateCSRFToken('company_event_update')); ?>">

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3" style="margin-bottom: 2.5rem !important;">
                            <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255"
                                   value="<?php echo TextEncodingHelper::escape($ev['title'] ?? ''); ?>"
                                   placeholder="Digite o título do evento">
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Local</label>
                            <input type="text" class="form-control" id="location" name="location" maxlength="255"
                                   value="<?php echo TextEncodingHelper::escape($ev['location'] ?? ''); ?>"
                                   placeholder="Local do evento">
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control form-control-lg rounded-3" id="description" name="description" rows="10"
                                      placeholder="Descreva o evento"><?php echo TextEncodingHelper::escape($ev['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3 mt-2">
                            <label for="department_id" class="form-label">Departamento</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Todos</option>
                                <?php foreach ($deps as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>" <?php echo ((int)($ev['department_id'] ?? 0) === (int)$d['id']) ? 'selected' : ''; ?>>
                                        <?php echo TextEncodingHelper::escape($d['name'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 row g-2">
                            <div class="col-12">
                                <label for="starts_at" class="form-label">Início <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" required
                                       value="<?php echo htmlspecialchars($fmt($ev['starts_at'] ?? null)); ?>">
                            </div>
                            <div class="col-12">
                                <label for="ends_at" class="form-label">Fim <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" required
                                       value="<?php echo htmlspecialchars($fmt($ev['ends_at'] ?? null)); ?>">
                            </div>
                        </div>

                        <div class="mb-3 row g-2">
                            <div class="col-md-6">
                                <label for="publish_at" class="form-label">Publicar em</label>
                                <input type="datetime-local" class="form-control" id="publish_at" name="publish_at"
                                       value="<?php echo htmlspecialchars($fmt($ev['publish_at'] ?? null)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="expire_at" class="form-label">Expira em</label>
                                <input type="datetime-local" class="form-control" id="expire_at" name="expire_at"
                                       value="<?php echo htmlspecialchars($fmt($ev['expire_at'] ?? null)); ?>">
                            </div>
                        </div>

                        <div class="mb-3 row g-2">
                            <div class="col-12">
                                <label for="rsvp_deadline" class="form-label">Prazo confirmação</label>
                                <input type="datetime-local" class="form-control" id="rsvp_deadline" name="rsvp_deadline"
                                       value="<?php echo htmlspecialchars($fmt($ev['rsvp_deadline'] ?? null)); ?>">
                            </div>
                            <div class="col-12">
                                <label for="cancellation_deadline" class="form-label">Prazo cancelamento</label>
                                <input type="datetime-local" class="form-control" id="cancellation_deadline" name="cancellation_deadline"
                                       value="<?php echo htmlspecialchars($fmt($ev['cancellation_deadline'] ?? null)); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="max_guests_per_user" class="form-label">Máx. convidados / colaborador</label>
                            <input type="number" class="form-control" id="max_guests_per_user" name="max_guests_per_user"
                                   min="0" value="<?php echo (int)($ev['max_guests_per_user'] ?? 0); ?>">
                        </div>

                        <?php
                        $eventImages = $this->data['event_images'] ?? [];
                        $eventAnexo = $ev['anexo'] ?? null;
                        $maxImages = 6;
                        $layoutMode = 'edit';
                        include __DIR__ . '/partials/media_upload_fields.php';
                        ?>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requires_rsvp" id="requires_rsvp"
                                    <?php echo !empty($ev['requires_rsvp']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="requires_rsvp">
                                    <i class="fas fa-user-check me-1"></i>Exige confirmação de presença
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allows_guests" id="allows_guests"
                                    <?php echo !empty($ev['allows_guests']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allows_guests">
                                    <i class="fas fa-users me-1"></i>Permite convidados
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo"
                                    <?php echo !empty($ev['ativo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">
                                    <i class="fas fa-check text-success me-1"></i>Ativo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Atualizar
                    </button>
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>view-company-event/<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-eye me-2"></i>Visualizar
                    </a>
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events"
                       class="btn btn-outline-secondary d-none d-md-inline-block">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="button"
                            class="btn btn-outline-secondary d-inline d-md-none"
                            onclick="window.history.back();">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/form_styles.php'; ?>
