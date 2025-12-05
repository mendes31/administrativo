<?php
use App\adms\Helpers\CSRFHelper;
$request = $this->data['request'];
?>
<form action="<?php echo $_ENV['URL_ADM']; ?>approve-employee-request-hr/<?= $request['id'] ?>" method="POST" class="mt-2">
    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_approve_request_hr'); ?>">
    <div class="d-flex gap-2">
        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
            <i class="fas fa-check me-1"></i>Aprovar
        </button>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectHRModal">
            <i class="fas fa-times me-1"></i>Rejeitar
        </button>
    </div>
</form>

<!-- Modal para Rejeição -->
<div class="modal fade" id="rejectHRModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo $_ENV['URL_ADM']; ?>approve-employee-request-hr/<?= $request['id'] ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_approve_request_hr'); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeitar Solicitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Motivo da Rejeição <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">Confirmar Rejeição</button>
                </div>
            </form>
        </div>
    </div>
</div>

