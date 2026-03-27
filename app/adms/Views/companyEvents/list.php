<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
        <h2 class="mt-0">Eventos corporativos</h2>
        <?php if (in_array('CreateCompanyEvent', $this->data['buttonPermission'] ?? [], true)): ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>create-company-event" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Novo evento</a>
        <?php endif; ?>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="table-responsive d-none d-md-block">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Ativo</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($this->data['events'] ?? []) as $ev): ?>
                    <tr>
                        <td><?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['title'] ?? ''); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ev['starts_at'] ?? '')); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ev['ends_at'] ?? '')); ?></td>
                        <td><?php echo !empty($ev['ativo']) ? 'Sim' : 'Não'; ?></td>
                        <td class="text-end text-nowrap">
                            <?php if (in_array('CompanyEventReport', $this->data['buttonPermission'] ?? [], true)
                                && (int)($ev['created_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>company-event-report/<?php echo (int)$ev['id']; ?>">Relatório</a>
                            <?php endif; ?>
                            <?php if (in_array('UpdateCompanyEvent', $this->data['buttonPermission'] ?? [], true)): ?>
                                <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-company-event/<?php echo (int)$ev['id']; ?>">Editar</a>
                            <?php endif; ?>
                            <?php if (in_array('DeleteCompanyEvent', $this->data['buttonPermission'] ?? [], true)
                                && ((int)($ev['created_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0) || (int)($_SESSION['user_access_level_id'] ?? 0) === 1)): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?php echo $_ENV['URL_ADM']; ?>delete-company-event/<?php echo (int)$ev['id']; ?>" onclick="return confirm('Excluir este evento?');">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-md-none">
        <?php foreach (($this->data['events'] ?? []) as $ev): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['title'] ?? ''); ?></h6>
                    <div class="small text-muted mb-2">
                        <?php echo date('d/m/Y H:i', strtotime($ev['starts_at'] ?? '')); ?> — <?php echo date('d/m/Y H:i', strtotime($ev['ends_at'] ?? '')); ?>
                    </div>
                    <div class="d-grid gap-2">
                        <?php if (in_array('CompanyEventReport', $this->data['buttonPermission'] ?? [], true)
                            && (int)($ev['created_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>company-event-report/<?php echo (int)$ev['id']; ?>">Relatório</a>
                        <?php endif; ?>
                        <?php if (in_array('UpdateCompanyEvent', $this->data['buttonPermission'] ?? [], true)): ?>
                            <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-company-event/<?php echo (int)$ev['id']; ?>">Editar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($this->data['pagination']['html'])): ?>
        <div class="mt-3"><?php echo $this->data['pagination']['html']; ?></div>
    <?php endif; ?>
</div>
