<?php
use App\adms\Helpers\ImageHelper;
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Visualizar Sala de Reunião</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
            <span><i class="fas fa-door-open me-2"></i><?= htmlspecialchars($this->data['room']['name'] ?? 'Sala de Reunião') ?></span>
            <span class="rooms-card-header-actions d-flex flex-row flex-wrap gap-1">
                <?php if (in_array('ListMeetingRooms', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="btn btn-info btn-sm mb-1">
                        <i class="fa-solid fa-list-ul"></i> Listar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdateMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-meeting-room/<?= $this->data['room']['id'] ?>" class="btn btn-warning btn-sm mb-1">
                        <i class="fa-solid fa-edit"></i> Editar
                    </a>
                <?php } ?>
                <?php if (in_array('DeleteMeetingRoom', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>delete-meeting-room/<?= $this->data['room']['id'] ?>" 
                       class="btn btn-danger btn-sm mb-1"
                       onclick="return confirm('Tem certeza que deseja excluir esta sala?');">
                        <i class="fa-solid fa-trash"></i> Apagar
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Nome:</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($this->data['room']['name']) ?></dd>

                        <dt class="col-sm-3">Capacidade:</dt>
                        <dd class="col-sm-9"><?= $this->data['room']['capacity'] ?> pessoas</dd>

                        <?php if (!empty($this->data['room']['description'])): ?>
                            <dt class="col-sm-3">Descrição:</dt>
                            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($this->data['room']['description'])) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->data['room']['location'])): ?>
                            <dt class="col-sm-3">Localização:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($this->data['room']['location']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->data['room']['building'])): ?>
                            <dt class="col-sm-3">Bloco/Prédio:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($this->data['room']['building']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->data['room']['floor'])): ?>
                            <dt class="col-sm-3">Andar:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($this->data['room']['floor']) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            <?php
                            $statusClass = match($this->data['room']['status']) {
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'maintenance' => 'warning',
                                default => 'secondary'
                            };
                            $statusText = match($this->data['room']['status']) {
                                'active' => 'Ativa',
                                'inactive' => 'Inativa',
                                'maintenance' => 'Em Manutenção',
                                default => $this->data['room']['status']
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                        </dd>

                        <dt class="col-sm-3">Requer Aprovação:</dt>
                        <dd class="col-sm-9">
                            <?= ($this->data['room']['requires_approval'] ?? false) ? '<span class="badge bg-warning">Sim</span>' : '<span class="badge bg-success">Não</span>' ?>
                        </dd>

                        <?php if (!empty($this->data['room']['min_advance_booking_hours'])): ?>
                            <dt class="col-sm-3">Antecedência Mínima:</dt>
                            <dd class="col-sm-9"><?= $this->data['room']['min_advance_booking_hours'] ?> horas</dd>
                        <?php endif; ?>

                        <?php if (!empty($this->data['room']['max_advance_booking_days'])): ?>
                            <dt class="col-sm-3">Antecedência Máxima:</dt>
                            <dd class="col-sm-9"><?= $this->data['room']['max_advance_booking_days'] ?> dias</dd>
                        <?php endif; ?>

                        <?php if (!empty($this->data['room']['booking_duration_limit_hours'])): ?>
                            <dt class="col-sm-3">Duração Máxima:</dt>
                            <dd class="col-sm-9"><?= $this->data['room']['booking_duration_limit_hours'] ?> horas</dd>
                        <?php endif; ?>

                        <dt class="col-sm-3">Criado por:</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($this->data['room']['creator_name'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-3">Criado em:</dt>
                        <dd class="col-sm-9"><?= date('d/m/Y H:i:s', strtotime($this->data['room']['created_at'])) ?></dd>
                    </dl>
                </div>
                
                <div class="col-md-4">
                    <?php if (!empty($this->data['room']['image'])): ?>
                        <div class="mb-4">
                            <h5>Imagem da Sala</h5>
                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($this->data['room']['image']); ?>"
                                 class="img-fluid rounded shadow"
                                 alt="Imagem da sala"
                                 style="max-width: 100%; max-height: 400px; object-fit: cover;"
                                 onerror="this.style.display='none';">
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h5>Imagem da Sala</h5>
                            <div class="text-center p-4 border rounded bg-light">
                                <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                <p class="text-muted">Nenhuma imagem cadastrada</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

