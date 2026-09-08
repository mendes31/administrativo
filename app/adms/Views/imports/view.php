<?php

$job = $this->data['job'] ?? [];
$stats = $this->data['stats'] ?? [];
$report = $this->data['report'] ?? [];
$profile = $this->data['profile'] ?? null;
$urlAdm = $_ENV['URL_ADM'] ?? '';

$actionLabel = [
    'created' => 'Criado',
    'updated' => 'Atualizado',
    'skipped' => 'Ignorado',
    'error' => 'Erro',
    'would_create' => 'Seria criado',
    'would_update' => 'Seria atualizado',
];
$actionClass = [
    'created' => 'success',
    'updated' => 'primary',
    'skipped' => 'secondary',
    'error' => 'danger',
    'would_create' => 'info',
    'would_update' => 'info',
];
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Resultado da importação</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>import-center">Importações</a></li>
            <li class="breadcrumb-item">Job #<?php echo (int) ($job['id'] ?? 0); ?></li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?php echo $profile ? htmlspecialchars($profile->label(), ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) ($job['profile_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-info btn-sm ms-auto" href="<?php echo $urlAdm; ?>import-center"><i class="fa-solid fa-list"></i> Central</a>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($job['dry_run'])): ?>
                <div class="alert alert-warning">Esta execução foi uma <strong>simulação</strong>: nada foi gravado no banco.</div>
            <?php endif; ?>

            <?php if (!empty($job['error_message'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars((string) $job['error_message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Linhas</div><strong><?php echo (int) ($stats['rows'] ?? 0); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Criados</div><strong><?php echo (int) ($stats['created'] ?? 0); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Atualizados</div><strong><?php echo (int) ($stats['updated'] ?? 0); ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Ignorados / erros</div><strong><?php echo (int) ($stats['skipped'] ?? 0); ?> / <?php echo (int) ($stats['errors'] ?? 0); ?></strong></div></div>
            </div>

            <p class="small text-muted">
                Arquivo: <?php echo htmlspecialchars((string) ($job['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                · Operação: <?php echo htmlspecialchars((string) ($job['operation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                · Chave: <?php echo htmlspecialchars((string) ($job['key_field'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                · Status: <?php echo htmlspecialchars((string) ($job['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if ($report === []): ?>
                <p class="text-muted mb-0">Sem linhas no relatório.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Linha</th>
                                <th>Ação</th>
                                <th>Chave</th>
                                <th>Mensagem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report as $row):
                                $acao = (string) ($row['acao'] ?? '');
                                $cls = $actionClass[$acao] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><?php echo (int) ($row['linha'] ?? 0); ?></td>
                                    <td><span class="badge text-bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($actionLabel[$acao] ?? $acao, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string) ($row['chave'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($row['msg'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ((int) ($stats['rows'] ?? 0) > 500): ?>
                    <p class="small text-muted">O relatório lista no máximo 500 linhas.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
