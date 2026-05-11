<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\ImageHelper;
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Salas de Reunião</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Salas</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
            <span><i class="fas fa-door-open me-2"></i>Listar Salas de Reunião</span>
            <span class="rooms-card-header-actions d-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-meeting-room" class="btn btn-success btn-sm mb-1"><i class="fa-solid fa-plus"></i> Nova Sala</a>
                <?php } ?>
                <?php if (in_array('RoomCalendar', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>room-calendar" class="btn btn-primary btn-sm mb-1"><i class="fas fa-calendar"></i> Calendário</a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="active" <?= (($this->data['filters']['status'] ?? '') === 'active') ? 'selected' : '' ?>>Ativa</option>
                        <option value="inactive" <?= (($this->data['filters']['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inativa</option>
                        <option value="maintenance" <?= (($this->data['filters']['status'] ?? '') === 'maintenance') ? 'selected' : '' ?>>Em Manutenção</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="building" class="form-label mb-1">Bloco/Prédio</label>
                    <select name="building" id="building" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['buildings'] ?? []) as $building): ?>
                            <option value="<?= htmlspecialchars($building) ?>" <?= (($this->data['filters']['building'] ?? '') === $building) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($building) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="floor" class="form-label mb-1">Andar</label>
                    <select name="floor" id="floor" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['floors'] ?? []) as $floor): ?>
                            <option value="<?= htmlspecialchars($floor) ?>" <?= (($this->data['filters']['floor'] ?? '') === $floor) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($floor) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="min_capacity" class="form-label mb-1">Capacidade Mín.</label>
                    <input type="number" name="min_capacity" id="min_capacity" class="form-control" 
                           min="1" value="<?= htmlspecialchars($this->data['filters']['min_capacity'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label mb-1">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Nome, descrição ou localização..." 
                           value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms?limpar=1" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>

            <?php if (empty($this->data['rooms'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma sala encontrada.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($this->data['rooms'] as $room): ?>
                        <?php
                        $statusClass = match($room['status']) {
                            'active' => 'success',
                            'inactive' => 'secondary',
                            'maintenance' => 'warning',
                            default => 'secondary'
                        };
                        $statusText = match($room['status']) {
                            'active' => 'Ativa',
                            'inactive' => 'Inativa',
                            'maintenance' => 'Em Manutenção',
                            default => $room['status']
                        };
                        
                        $location = !empty($room['location']) 
                            ? $room['location'] 
                            : trim(($room['building'] ?? '') . ' ' . ($room['floor'] ?? ''), ' -');
                        ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm room-card" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;">
                                <div class="card-img-top position-relative" style="height: 200px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <?php
                                    echo ImageHelper::displayImage(
                                        $room['image'] ?? null,
                                        [
                                            'alt' => htmlspecialchars($room['name']),
                                            'class' => 'w-100 h-100',
                                            'style' => 'object-fit: cover;'
                                        ],
                                        'default_room.png',
                                        'rooms'
                                    );
                                    ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-2 fw-bold"><?= htmlspecialchars($room['name']) ?></h5>
                                    
                                    <div class="mb-2">
                                        <?php if (!empty($location)): ?>
                                            <div class="d-flex align-items-center text-muted small mb-1">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <span><?= htmlspecialchars($location) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center text-muted small">
                                            <i class="fas fa-users me-2"></i>
                                            <span><?= $room['capacity'] ?> pessoas</span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($room['description'])): ?>
                                        <p class="card-text text-muted small flex-grow-1" style="font-size: 0.85rem;">
                                            <?= htmlspecialchars(mb_substr($room['description'], 0, 80)) ?><?= mb_strlen($room['description']) > 80 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-auto pt-2">
                                        <?php if ($room['status'] === 'active'): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= $room['id'] ?>" 
                                               class="btn btn-sm btn-success w-100 mb-2" title="Reservar esta sala">
                                                <i class="fas fa-calendar-plus me-1"></i>Reservar
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array('UpdateMeetingRoom', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary w-100 mb-2 js-open-import-bookings"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalImportRoomBookings"
                                                    data-room-id="<?= (int) $room['id'] ?>"
                                                    data-room-name="<?= htmlspecialchars($room['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    title="Baixar modelo e importar reservas desta sala">
                                                <i class="fas fa-file-import me-1"></i>Importar / modelo
                                            </button>
                                        <?php } ?>
                                        <div class="btn-group w-100" role="group">
                                            <?php if (in_array('ViewMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-meeting-room/<?= $room['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('UpdateMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-meeting-room/<?= $room['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('DeleteMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-meeting-room/<?= $room['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Tem certeza que deseja excluir esta sala?');" 
                                                   title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($this->data['pagination'])): ?>
                    <div class="mt-4">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (in_array('UpdateMeetingRoom', $this->data['buttonPermission'] ?? [], true)) { ?>
<div class="modal fade" id="modalImportRoomBookings" tabindex="-1" aria-labelledby="modalImportRoomBookingsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportRoomBookingsLabel">Importar reservas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Sala: <strong id="importModalRoomName"></strong></p>
                <p class="small mb-3">1) Baixe o modelo (CSV com separador <strong>;</strong>). 2) Preencha as linhas. 3) Envie o arquivo. Linhas com conflito de horário na sala serão ignoradas.</p>
                <a href="#" class="btn btn-outline-primary w-100 mb-3" id="importModalDownloadTemplate" download>
                    <i class="fas fa-download me-2"></i>Baixar modelo para preenchimento
                </a>
                <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>import-room-bookings" enctype="multipart/form-data" id="formImportRoomBookings">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_import_room_bookings'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="room_id" id="importModalRoomId" value="">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Arquivo (.csv, .xlsx)</label>
                        <input type="file" class="form-control" name="import_file" id="import_file" accept=".csv,.xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-2"></i>Importar agora
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var base = <?php echo json_encode(rtrim($_ENV['URL_ADM'] ?? '', '/') . '/', JSON_HEX_TAG | JSON_HEX_APOS); ?>;
    var modalEl = document.getElementById('modalImportRoomBookings');
    if (!modalEl) return;
    modalEl.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn || !btn.classList || !btn.classList.contains('js-open-import-bookings')) return;
        var rid = btn.getAttribute('data-room-id') || '';
        var rname = btn.getAttribute('data-room-name') || '';
        document.getElementById('importModalRoomId').value = rid;
        document.getElementById('importModalRoomName').textContent = rname;
        var dl = document.getElementById('importModalDownloadTemplate');
        dl.href = base + 'import-room-bookings?room_id=' + encodeURIComponent(rid) + '&template=1';
    });
})();
</script>
<?php } ?>

<style>
.room-card {
    cursor: pointer;
}
.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}
.room-card .btn-group .btn {
    flex: 1;
}
</style>

