<?php
use App\adms\Helpers\CSRFHelper;

$deps = $this->data['departments'] ?? [];
$urlAdm = $_ENV['URL_ADM'] ?? '';
?>
<div class="container-fluid px-4 company-event-form">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-1 d-flex flex-column flex-md-row align-items-md-center gap-2">
                <h2 class="mt-3 text-success fw-bold mb-0 mobile-hide-page-title" style="font-size: 1.7rem; letter-spacing: -1px;">
                    Novo evento corporativo
                </h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0 mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events" class="text-decoration-none">Eventos corporativos</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Novo</li>
                    </ol>
                </nav>
            </div>

            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 18px; background: #fff;">
                <div class="card-body p-4">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <form method="post" enctype="multipart/form-data" style="max-width: 700px; margin: 0 auto;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRFHelper::generateCSRFToken('company_event_create')); ?>">

                        <div class="row g-3 mb-3" style="margin-bottom: 2.5rem !important;">
                            <div class="col-md-8">
                                <label for="title" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg rounded-3" id="title" name="title"
                                       required maxlength="255" placeholder="Digite o título do evento">
                            </div>
                            <div class="col-md-4">
                                <label for="department_id" class="form-label fw-semibold">Departamento</label>
                                <select class="form-select form-select-lg rounded-3" id="department_id" name="department_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($deps as $d): ?>
                                        <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name'] ?? ''); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label fw-semibold">Local</label>
                                <input type="text" class="form-control form-control-lg rounded-3" id="location" name="location"
                                       maxlength="255" placeholder="Local do evento">
                            </div>
                            <div class="col-md-3">
                                <label for="max_guests_per_user" class="form-label fw-semibold">Máx. convidados</label>
                                <input type="number" class="form-control form-control-lg rounded-3" id="max_guests_per_user"
                                       name="max_guests_per_user" min="0" value="0">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                                    <label class="form-check-label fw-semibold text-success" for="ativo">
                                        <i class="fas fa-check me-1"></i>Ativo
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="starts_at" class="form-label fw-semibold">Início <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="starts_at" name="starts_at" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ends_at" class="form-label fw-semibold">Fim <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="ends_at" name="ends_at" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="publish_at" class="form-label fw-semibold">Publicar a partir de</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="publish_at" name="publish_at">
                            </div>
                            <div class="col-md-6">
                                <label for="expire_at" class="form-label fw-semibold">Expirar visibilidade em</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="expire_at" name="expire_at">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="rsvp_deadline" class="form-label fw-semibold">Prazo confirmação (RSVP)</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="rsvp_deadline" name="rsvp_deadline">
                            </div>
                            <div class="col-md-6">
                                <label for="cancellation_deadline" class="form-label fw-semibold">Prazo cancelamento</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="cancellation_deadline" name="cancellation_deadline">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Descrição</label>
                            <textarea class="form-control form-control-lg rounded-3" id="description" name="description" rows="6"
                                      placeholder="Descreva o evento para os colaboradores"></textarea>
                        </div>

                        <div class="row g-3 mb-3 mt-3">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_rsvp" id="requires_rsvp">
                                    <label class="form-check-label fw-semibold" for="requires_rsvp">
                                        <i class="fas fa-user-check me-1"></i>Exige confirmação de presença
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allows_guests" id="allows_guests">
                                    <label class="form-check-label fw-semibold" for="allows_guests">
                                        <i class="fas fa-users me-1"></i>Permite convidados
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mb-3">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-lg rounded-3 px-4 d-none d-md-inline-block"
                                    onclick="window.location.href='<?php echo htmlspecialchars($urlAdm); ?>list-company-events'">
                                Cancelar
                            </button>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-lg rounded-3 px-4 d-inline d-md-none"
                                    onclick="window.history.back();">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-success btn-lg rounded-3 px-4 fw-bold">
                                Salvar evento
                            </button>
                        </div>

                        <?php
                        $eventImages = [];
                        $eventAnexo = null;
                        $maxImages = 6;
                        $layoutMode = 'create';
                        include __DIR__ . '/partials/media_upload_fields.php';
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/form_styles.php'; ?>
