<?php
// View: Central de Notificações do Colaborador
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Notificações</h2>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Minhas Notificações</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['error'])): ?>
                <div class="alert alert-danger mb-3"> <?= htmlspecialchars($this->data['error']) ?> </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Título</th>
                            <th>Mensagem</th>
                            <th>Data</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['notifications'])): ?>
                            <?php foreach ($this->data['notifications'] as $item): ?>
                                <tr class="<?= $item['lida'] ? 'table-light' : 'table-warning' ?>">
                                    <td>
                                        <?php if ($item['lida']): ?>
                                            <span class="badge bg-secondary">Lida</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Nova</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['titulo']) ?></td>
                                    <td><?= htmlspecialchars($item['mensagem']) ?></td>
                                    <td><?= $item['created_at'] ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td>
                                    <td>
                                        <?php if (!empty($item['url'])): ?>
                                            <a href="<?= htmlspecialchars($item['url']) ?>" class="btn btn-primary btn-sm">Acessar</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhuma notificação encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 