<?php

use App\adms\Helpers\CSRFHelper;

$ticket = $this->data['ticket'] ?? [];
$messages = $this->data['messages'] ?? [];
$statusLog = $this->data['status_log'] ?? [];
$id = $ticket['id'] ?? '';
$code = $ticket['code'] ?? '';

$csrf_token_delete = CSRFHelper::generateCSRFToken('form_delete_ticket');
$csrf_token_reply = CSRFHelper::generateCSRFToken('sac_reply_form');
$csrf_token_transfer = CSRFHelper::generateCSRFToken('sac_transfer_form');

$statusBadges = [
    'Aberto' => 'primary',
    'Em análise' => 'info',
    'Em atendimento' => 'warning',
    'Aguardando cliente' => 'secondary',
    'Resolvido' => 'success',
    'Encerrado' => 'dark',
];

$priorityBadges = [
    'Baixa' => 'secondary',
    'Média' => 'primary',
    'Alta' => 'warning',
    'Urgente' => 'danger',
];

$isResolved = in_array($ticket['status'] ?? '', ['Resolvido', 'Encerrado']);

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-ticket-alt me-2"></i>#<?= htmlspecialchars($code) ?></h2>

        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-tickets" class="text-decoration-none">Chamados</a></li>
            <li class="breadcrumb-item">#<?= htmlspecialchars($code) ?></li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Chamado #<?= htmlspecialchars($code) ?></span>
            <span class="ms-sm-auto d-sm-flex flex-row flex-wrap">
                <?php if (in_array('SacListTickets', $this->data['buttonPermission'])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>sac-list-tickets" class="btn btn-info btn-sm me-1 mb-1"><i class="fa-solid fa-list-ul"></i> Listar</a>
                <?php endif; ?>
                <?php if (in_array('SacUpdateTicket', $this->data['buttonPermission'])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>sac-update-ticket/<?= $id ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-regular fa-pen-to-square"></i> Editar</a>
                <?php endif; ?>
                <?php if (in_array('ListLogAlteracoes', $this->data['buttonPermission'] ?? [])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-log-alteracoes?entity=sac_tickets&entity_id=<?= $id ?>" class="btn btn-outline-info btn-sm me-1 mb-1"><i class="fas fa-history"></i> Log</a>
                <?php endif; ?>
                <?php if (in_array('SacUpdateTicket', $this->data['buttonPermission'])): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm me-1 mb-1" data-bs-toggle="modal" data-bs-target="#modalTransfer"><i class="fas fa-exchange-alt"></i> Transferir</button>
                <?php endif; ?>
                <?php if (in_array('SacDeleteTicket', $this->data['buttonPermission'])): ?>
                    <form id="formDelete<?= $id ?>" action="<?= $_ENV['URL_ADM'] ?>sac-delete-ticket" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token_delete ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                    </form>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($ticket)): ?>
                <div class="row">
                    <!-- Left column: Ticket details -->
                    <div class="col-md-8">
                        <h4 class="mb-3"><?= htmlspecialchars($ticket['subject'] ?? '') ?></h4>

                        <div class="mb-3">
                            <strong>Descrição:</strong>
                            <div class="border rounded p-3 bg-light mt-1"><?= nl2br(htmlspecialchars($ticket['description'] ?? '')) ?></div>
                        </div>

                        <dl class="row">
                            <dt class="col-sm-3">Cliente:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($ticket['client_razao_social'] ?? $ticket['client_nome_fantasia'] ?? '') ?></dd>

                            <dt class="col-sm-3">Produto:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($ticket['product'] ?? '—') ?></dd>

                            <?php if (!empty($ticket['batch'])): ?>
                            <dt class="col-sm-3">Lote:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($ticket['batch']) ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-3">Categoria:</dt>
                            <dd class="col-sm-9">
                                <?php if (!empty($ticket['category_name'])): ?>
                                    <span class="badge" style="background-color:<?= htmlspecialchars($ticket['category_color'] ?? '#6c757d') ?>"><?= htmlspecialchars($ticket['category_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-3">Canal:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($ticket['channel'] ?? '—') ?></dd>
                        </dl>
                    </div>

                    <!-- Right column: Status & SLA -->
                    <div class="col-md-4">
                        <div class="card border-light shadow-sm mb-3">
                            <div class="card-body text-center">
                                <h5 class="mb-2">Status</h5>
                                <span class="badge bg-<?= $statusBadges[$ticket['status'] ?? ''] ?? 'secondary' ?> fs-6 px-3 py-2">
                                    <?= htmlspecialchars($ticket['status'] ?? '') ?>
                                </span>
                            </div>
                        </div>

                        <dl class="row small">
                            <dt class="col-6">Prioridade:</dt>
                            <dd class="col-6"><span class="badge bg-<?= $priorityBadges[$ticket['priority'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['priority'] ?? '') ?></span></dd>

                            <dt class="col-6">Atendente:</dt>
                            <dd class="col-6"><?= htmlspecialchars($ticket['assigned_name'] ?? '—') ?></dd>

                            <dt class="col-6">Departamento:</dt>
                            <dd class="col-6"><?= htmlspecialchars($ticket['department_name'] ?? '—') ?></dd>

                            <dt class="col-6">Criado em:</dt>
                            <dd class="col-6"><?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?></dd>

                            <dt class="col-6">Atualizado:</dt>
                            <dd class="col-6"><?= !empty($ticket['updated_at']) ? date('d/m/Y H:i', strtotime($ticket['updated_at'])) : '' ?></dd>
                        </dl>

                        <!-- SLA Indicators -->
                        <?php if (!empty($ticket['sla_response_deadline'])): ?>
                            <div class="alert <?= !empty($ticket['sla_response_breached']) ? 'alert-danger' : 'alert-info' ?> py-2 px-3 small">
                                <strong>SLA Resposta:</strong> <?= date('d/m/Y H:i', strtotime($ticket['sla_response_deadline'])) ?>
                                <?php if (!empty($ticket['sla_response_breached'])): ?>
                                    <span class="badge bg-danger ms-1">Violado</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($ticket['sla_resolution_deadline'])): ?>
                            <div class="alert <?= !empty($ticket['sla_resolution_breached']) ? 'alert-danger' : 'alert-info' ?> py-2 px-3 small">
                                <strong>SLA Resolução:</strong> <?= date('d/m/Y H:i', strtotime($ticket['sla_resolution_deadline'])) ?>
                                <?php if (!empty($ticket['sla_resolution_breached'])): ?>
                                    <span class="badge bg-danger ms-1">Violado</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">Chamado não encontrado.</div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Anexos do Chamado -->
    <?php
    $ticketAttachments = $this->data['attachments'] ?? [];
    if (!empty($ticket) && !empty($ticketAttachments)):
    ?>
        <div class="card mb-4 border-light shadow">
            <div class="card-header fw-semibold"><i class="fas fa-paperclip me-2"></i>Anexos (<?= count($ticketAttachments) ?>)</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($ticketAttachments as $att):
                        $ext = strtolower(pathinfo($att['file_name'] ?? '', PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                        $iconMap = [
                            'pdf' => 'fa-file-pdf text-danger',
                            'doc' => 'fa-file-word text-primary', 'docx' => 'fa-file-word text-primary',
                            'xls' => 'fa-file-excel text-success', 'xlsx' => 'fa-file-excel text-success', 'csv' => 'fa-file-csv text-success',
                            'zip' => 'fa-file-archive text-warning', 'rar' => 'fa-file-archive text-warning',
                            'ppt' => 'fa-file-powerpoint text-danger', 'pptx' => 'fa-file-powerpoint text-danger',
                            'txt' => 'fa-file-alt text-secondary',
                        ];
                        $icon = $iconMap[$ext] ?? 'fa-file text-secondary';
                        $size = (int)($att['file_size'] ?? 0);
                        $sizeLabel = $size > 1048576 ? number_format($size / 1048576, 1) . ' MB' : number_format($size / 1024, 1) . ' KB';
                        $fileUrl = $_ENV['URL_ADM'] . '../' . ($att['file_path'] ?? '');
                    ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card h-100 border-light shadow-sm">
                                <?php if ($isImage): ?>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($fileUrl) ?>" class="card-img-top" style="height:140px;object-fit:cover;" alt="<?= htmlspecialchars($att['file_name'] ?? '') ?>">
                                    </a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="text-decoration-none d-flex align-items-center justify-content-center bg-light" style="height:140px;">
                                        <i class="fas <?= $icon ?> fa-3x"></i>
                                    </a>
                                <?php endif; ?>
                                <div class="card-body p-2">
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="text-decoration-none small fw-semibold d-block text-truncate" title="<?= htmlspecialchars($att['file_name'] ?? '') ?>">
                                        <?= htmlspecialchars($att['file_name'] ?? 'Arquivo') ?>
                                    </a>
                                    <small class="text-muted"><?= $sizeLabel ?> &middot; <?= !empty($att['created_at']) ? date('d/m/Y H:i', strtotime($att['created_at'])) : '' ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Timeline de Mensagens -->
    <?php if (!empty($ticket)): ?>
        <div class="card mb-4 border-light shadow">
            <div class="card-header fw-semibold"><i class="fas fa-comments me-2"></i>Mensagens</div>
            <div class="card-body">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isSystem = ($msg['sender_type'] ?? '') === 'system';
                        $isInternal = !empty($msg['is_internal_note']);
                        $borderColor = $isSystem ? '' : ($isInternal ? 'border-start border-4 border-warning' : 'border-start border-4 border-primary');
                        ?>
                        <div class="card mb-3 <?= $borderColor ?> <?= $isSystem ? 'bg-light' : '' ?>">
                            <div class="card-body <?= $isSystem ? 'text-center fst-italic' : '' ?>">
                                <?php if (!$isSystem): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong><?= htmlspecialchars($msg['sender_name'] ?? 'Sistema') ?></strong>
                                        <small class="text-muted"><?= !empty($msg['created_at']) ? date('d/m/Y H:i', strtotime($msg['created_at'])) : '' ?></small>
                                    </div>
                                    <?php if ($isInternal): ?>
                                        <span class="badge bg-warning text-dark mb-2"><i class="fas fa-lock me-1"></i>Nota interna</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted d-block mb-1"><?= !empty($msg['created_at']) ? date('d/m/Y H:i', strtotime($msg['created_at'])) : '' ?></small>
                                <?php endif; ?>

                                <div><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></div>

                                <?php if (!empty($msg['attachments'])): ?>
                                    <div class="mt-2 pt-2 border-top">
                                        <small class="text-muted"><i class="fas fa-paperclip me-1"></i>Anexos:</small>
                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                            <?php foreach ($msg['attachments'] as $att):
                                                $attExt = strtolower(pathinfo($att['file_name'] ?? '', PATHINFO_EXTENSION));
                                                $attIsImg = in_array($attExt, ['jpg','jpeg','png','gif','webp']);
                                                $attIconMap = [
                                                    'pdf' => 'fa-file-pdf text-danger', 'doc' => 'fa-file-word text-primary', 'docx' => 'fa-file-word text-primary',
                                                    'xls' => 'fa-file-excel text-success', 'xlsx' => 'fa-file-excel text-success', 'csv' => 'fa-file-csv text-success',
                                                    'zip' => 'fa-file-archive text-warning', 'rar' => 'fa-file-archive text-warning',
                                                ];
                                                $attIcon = $attIconMap[$attExt] ?? 'fa-file text-secondary';
                                                $attUrl = $_ENV['URL_ADM'] . '../' . ($att['file_path'] ?? '');
                                            ?>
                                                <a href="<?= htmlspecialchars($attUrl) ?>" target="_blank" class="text-decoration-none border rounded p-1 d-inline-flex align-items-center bg-white" title="<?= htmlspecialchars($att['file_name'] ?? '') ?>">
                                                    <?php if ($attIsImg): ?>
                                                        <img src="<?= htmlspecialchars($attUrl) ?>" class="rounded" style="width:36px;height:36px;object-fit:cover;" alt="">
                                                    <?php else: ?>
                                                        <i class="fas <?= $attIcon ?> fa-lg mx-1"></i>
                                                    <?php endif; ?>
                                                    <span class="small ms-1 me-1 text-truncate" style="max-width:120px;"><?= htmlspecialchars($att['file_name'] ?? 'Arquivo') ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">Nenhuma mensagem registrada.</p>
                <?php endif; ?>

                <!-- Reply form -->
                <?php if (in_array('SacReplyTicket', $this->data['buttonPermission'] ?? [])): ?>
                    <hr>
                    <form action="<?= $_ENV['URL_ADM'] ?>sac-reply-ticket/<?= $id ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token_reply ?>">

                        <div class="mb-3">
                            <label for="message" class="form-label fw-semibold">Responder</label>
                            <textarea name="message" id="message" class="form-control" rows="4" required placeholder="Digite sua resposta..."></textarea>
                        </div>

                        <div class="row align-items-center mb-3">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input type="checkbox" name="is_internal_note" id="is_internal_note" class="form-check-input" value="1">
                                    <label for="is_internal_note" class="form-check-label">Nota interna</label>
                                </div>
                            </div>
                            <div class="col">
                                <input type="file" name="attachments[]" class="form-control form-control-sm" multiple>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Enviar Resposta</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Log -->
        <?php if (!empty($statusLog)): ?>
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-exchange-alt me-2"></i>Histórico de Status</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 small">
                            <thead>
                                <tr>
                                    <th>De</th>
                                    <th>Para</th>
                                    <th>Por</th>
                                    <th>Data</th>
                                    <th>Obs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusLog as $log): ?>
                                    <tr>
                                        <td><span class="badge bg-<?= $statusBadges[$log['from_status'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($log['from_status'] ?? '') ?></span></td>
                                        <td><span class="badge bg-<?= $statusBadges[$log['to_status'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($log['to_status'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($log['changed_by_name'] ?? '') ?></td>
                                        <td><?= !empty($log['created_at']) ? date('d/m/Y H:i', strtotime($log['created_at'])) : '' ?></td>
                                        <td><?= htmlspecialchars($log['notes'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Satisfaction -->
        <?php if ($isResolved): ?>
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-star me-2"></i>Avaliação de Satisfação</div>
                <div class="card-body">
                    <?php if (!empty($ticket['satisfaction_rating'])): ?>
                        <div class="text-center">
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star fa-2x <?= $i <= (int)$ticket['satisfaction_rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($ticket['satisfaction_comment'])): ?>
                                <p class="text-muted">"<?= htmlspecialchars($ticket['satisfaction_comment']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form action="<?= $_ENV['URL_ADM'] ?>sac-rate-ticket/<?= $id ?>" method="POST" class="text-center">
                            <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('sac_rating_form') ?>">
                            <p class="mb-2">Avalie o atendimento:</p>
                            <div class="mb-3 sac-rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" class="d-none" required>
                                    <label for="star<?= $i ?>" class="fs-2 text-muted sac-star-label" style="cursor:pointer;" data-value="<?= $i ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                            <div class="mb-3">
                                <textarea name="comment" class="form-control" rows="2" placeholder="Comentário (opcional)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Enviar Avaliação</button>
                        </form>
                        <script>
                        document.querySelectorAll('.sac-star-label').forEach(function(label) {
                            label.addEventListener('click', function() {
                                var val = parseInt(this.dataset.value);
                                document.querySelectorAll('.sac-star-label').forEach(function(l, idx) {
                                    l.classList.toggle('text-warning', idx < val);
                                    l.classList.toggle('text-muted', idx >= val);
                                });
                            });
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Transfer Modal -->
    <div class="modal fade" id="modalTransfer" tabindex="-1" aria-labelledby="modalTransferLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $_ENV['URL_ADM'] ?>sac-transfer-ticket/<?= $id ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token_transfer ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTransferLabel"><i class="fas fa-exchange-alt me-2"></i>Transferir Chamado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="transfer_assigned_user_id" class="form-label">Atendente</label>
                            <select name="assigned_user_id" id="transfer_assigned_user_id" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($this->data['users'] ?? [] as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="transfer_department_id" class="form-label">Departamento</label>
                            <select name="department_id" id="transfer_department_id" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($this->data['departments'] ?? [] as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="transfer_notes" class="form-label">Motivo da transferência</label>
                            <textarea name="notes" id="transfer_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-exchange-alt me-1"></i>Transferir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
