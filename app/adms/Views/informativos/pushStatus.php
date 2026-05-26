<?php
$informativo = $this->data['informativo'];
$pushUsers   = $this->data['push_users'] ?? [];
$summary     = $this->data['push_summary'] ?? ['total' => 0, 'sent' => 0, 'no_subscription' => 0, 'pending' => 0];
$infId       = (int) ($informativo['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Status de Entrega Push</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none">Informativos</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $infId; ?>" class="text-decoration-none">Visualizar</a>
            </li>
            <li class="breadcrumb-item active">Status Push</li>
        </ol>
    </div>

    <!-- Cabeçalho do Informativo -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-bell me-2"></i>
                <?php echo htmlspecialchars($informativo['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </h5>
        </div>
        <div class="card-body py-2">
            <div class="row small">
                <div class="col-md-4">
                    <strong>Categoria:</strong>
                    <?php echo htmlspecialchars($informativo['categoria_nome'] ?? $informativo['categoria'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="col-md-4">
                    <strong>Departamento:</strong>
                    <?php echo htmlspecialchars($informativo['department_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="col-md-4">
                    <strong>Publicado em:</strong>
                    <?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?>
                </div>
            </div>
        </div>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Cards de resumo -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-2">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0"><?php echo $summary['total']; ?></h3>
                    <small>Total Elegíveis</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0"><?php echo $summary['sent']; ?></h3>
                    <small>Push Entregue</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0"><?php echo $summary['pending']; ?></h3>
                    <small>Pendentes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0"><?php echo $summary['no_subscription']; ?></h3>
                    <small>Sem Inscrição</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões -->
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <a href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $infId; ?>" class="btn btn-secondary btn-sm d-none d-md-inline-block">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Informativo
            </a>
            <button type="button" class="btn btn-secondary btn-sm d-inline d-md-none" onclick="window.history.back();" aria-label="Voltar">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </button>
        </div>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFilterAll" onclick="filterPushStatus('')">
                Todos <span class="badge bg-primary"><?php echo $summary['total']; ?></span>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnFilterSent" onclick="filterPushStatus('ENTREGUE')">
                <i class="fas fa-check me-1"></i>Entregue <span class="badge bg-success"><?php echo $summary['sent']; ?></span>
            </button>
            <button type="button" class="btn btn-outline-warning btn-sm" id="btnFilterPending" onclick="filterPushStatus('PENDENTE')">
                <i class="fas fa-clock me-1"></i>Pendente <span class="badge bg-warning text-dark"><?php echo $summary['pending']; ?></span>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFilterNoSub" onclick="filterPushStatus('SEM INSCRIÇÃO')">
                <i class="fas fa-ban me-1"></i>Sem inscrição <span class="badge bg-secondary"><?php echo $summary['no_subscription']; ?></span>
            </button>
        </div>
    </div>

    <!-- Tabela Desktop -->
    <div class="card border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-users me-1"></i>Usuários e Dispositivos</span>
            <div class="ms-auto">
                <input type="text" id="pushStatusSearch" class="form-control form-control-sm" placeholder="Buscar por nome ou email..." oninput="searchPushStatus()" style="min-width:220px;">
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Desktop -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered mb-0" id="pushStatusTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" style="min-width:200px;">Usuário</th>
                            <th class="ps-3" style="min-width:130px;">Departamento</th>
                            <th class="ps-3 text-center" style="min-width:130px;">Status Push</th>
                            <th class="ps-3" style="min-width:140px;">Enviado em</th>
                            <th class="ps-3" style="min-width:200px;">Dispositivos Inscritos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pushUsers === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Nenhum usuário elegível encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pushUsers as $row): ?>
                                <tr data-status="<?php echo htmlspecialchars($row['push_status']); ?>"
                                    data-user="<?php echo htmlspecialchars(mb_strtolower($row['name'] . ' ' . $row['email'])); ?>">
                                    <td class="ps-3 align-middle">
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                    </td>
                                    <td class="ps-3 align-middle">
                                        <?php echo htmlspecialchars($row['department']); ?>
                                    </td>
                                    <td class="ps-3 align-middle text-center">
                                        <?php
                                        $badgeClass = match ($row['push_status']) {
                                            'ENTREGUE' => 'bg-success',
                                            'PENDENTE' => 'bg-warning text-dark',
                                            'SEM INSCRIÇÃO' => 'bg-secondary',
                                            default => 'bg-light text-dark',
                                        };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($row['push_status']); ?>
                                        </span>
                                    </td>
                                    <td class="ps-3 align-middle"><?php echo htmlspecialchars($row['push_sent_at']); ?></td>
                                    <td class="ps-3 align-middle">
                                        <?php if ($row['devices'] === []): ?>
                                            <span class="text-muted small">Nenhum dispositivo inscrito</span>
                                        <?php else: ?>
                                            <?php foreach ($row['devices'] as $dev): ?>
                                                <div class="small">
                                                    <i class="fas fa-mobile-alt me-1 text-primary"></i>
                                                    <?php echo htmlspecialchars($dev['label']); ?>
                                                    <span class="text-muted">(<?php echo htmlspecialchars($dev['updated_at']); ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile -->
            <div class="d-block d-md-none p-3">
                <?php if ($pushUsers === []): ?>
                    <div class="text-center text-muted py-3">Nenhum usuário elegível encontrado.</div>
                <?php else: ?>
                    <?php foreach ($pushUsers as $row): ?>
                        <?php
                        $badgeClass = match ($row['push_status']) {
                            'ENTREGUE' => 'bg-success',
                            'PENDENTE' => 'bg-warning text-dark',
                            'SEM INSCRIÇÃO' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        };
                        ?>
                        <div class="card mb-2 shadow-sm push-status-card" style="border-radius:10px;"
                             data-status="<?php echo htmlspecialchars($row['push_status']); ?>"
                             data-user="<?php echo htmlspecialchars(mb_strtolower($row['name'] . ' ' . $row['email'])); ?>">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                                        <?php if ($row['department'] !== ''): ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['push_status']); ?></span>
                                </div>
                                <?php if ($row['push_sent_at'] !== '—'): ?>
                                    <div class="mt-1 small text-muted">
                                        <i class="fas fa-paper-plane me-1"></i>Enviado: <?php echo htmlspecialchars($row['push_sent_at']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($row['devices'] !== []): ?>
                                    <div class="mt-1">
                                        <?php foreach ($row['devices'] as $dev): ?>
                                            <div class="small">
                                                <i class="fas fa-mobile-alt me-1 text-primary"></i>
                                                <?php echo htmlspecialchars($dev['label']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let activePushFilter = '';

function filterPushStatus(status) {
    activePushFilter = status;
    applyPushFilters();

    document.querySelectorAll('[id^="btnFilter"]').forEach(b => {
        b.classList.remove('active');
        b.classList.add('btn-outline-secondary', 'btn-outline-success', 'btn-outline-warning');
    });
}

function searchPushStatus() {
    applyPushFilters();
}

function applyPushFilters() {
    const q = (document.getElementById('pushStatusSearch')?.value || '').toLowerCase().trim();
    const status = activePushFilter;

    document.querySelectorAll('#pushStatusTable tbody tr[data-status]').forEach(tr => {
        const matchStatus = !status || tr.dataset.status === status;
        const matchSearch = !q || (tr.dataset.user || '').includes(q);
        tr.style.display = (matchStatus && matchSearch) ? '' : 'none';
    });

    document.querySelectorAll('.push-status-card[data-status]').forEach(card => {
        const matchStatus = !status || card.dataset.status === status;
        const matchSearch = !q || (card.dataset.user || '').includes(q);
        card.style.display = (matchStatus && matchSearch) ? '' : 'none';
    });
}
</script>
