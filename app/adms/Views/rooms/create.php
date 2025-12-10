<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_create_meeting_room');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Sala de Reunião</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-door-open me-2"></i>Nova Sala de Reunião</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="col-md-6">
                    <label for="name" class="form-label">Nome da Sala <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required 
                           placeholder="Ex: Sala Executiva">
                </div>
                
                <div class="col-md-6">
                    <label for="capacity" class="form-label">Capacidade (pessoas) <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" id="capacity" class="form-control" 
                           min="1" value="1" required>
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="3" 
                              placeholder="Descrição da sala..."></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="image" class="form-label">Imagem da Sala</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 5MB</small>
                    <div id="imagePreview" class="mt-2" style="display: none;">
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="building" class="form-label">Bloco/Prédio</label>
                    <input type="text" name="building" id="building" class="form-control" 
                           placeholder="Ex: Bloco A">
                </div>
                
                <div class="col-md-4">
                    <label for="floor" class="form-label">Andar</label>
                    <input type="text" name="floor" id="floor" class="form-control" 
                           placeholder="Ex: 3º Andar">
                </div>
                
                <div class="col-md-4">
                    <label for="location" class="form-label">Localização Completa</label>
                    <input type="text" name="location" id="location" class="form-control" 
                           placeholder="Ex: Bloco A - 3º Andar">
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="active" selected>Ativa</option>
                        <option value="inactive">Inativa</option>
                        <option value="maintenance">Em Manutenção</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="requires_approval" id="requires_approval" 
                               class="form-check-input" value="1">
                        <label class="form-check-label" for="requires_approval">
                            Requer aprovação para reserva
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="min_advance_booking_hours" class="form-label">Antecedência Mínima (horas)</label>
                    <input type="number" name="min_advance_booking_hours" id="min_advance_booking_hours" 
                           class="form-control" min="0" placeholder="Ex: 2">
                    <small class="form-text text-muted">Tempo mínimo antes da reunião para fazer reserva</small>
                </div>
                
                <div class="col-md-4">
                    <label for="max_advance_booking_days" class="form-label">Antecedência Máxima (dias)</label>
                    <input type="number" name="max_advance_booking_days" id="max_advance_booking_days" 
                           class="form-control" min="1" placeholder="Ex: 30">
                    <small class="form-text text-muted">Tempo máximo antes da reunião para fazer reserva</small>
                </div>
                
                <div class="col-md-4">
                    <label for="booking_duration_limit_hours" class="form-label">Duração Máxima (horas)</label>
                    <input type="number" name="booking_duration_limit_hours" id="booking_duration_limit_hours" 
                           class="form-control" min="1" placeholder="Ex: 4">
                    <small class="form-text text-muted">Duração máxima permitida para uma reserva</small>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview da imagem antes do upload
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});
</script>

