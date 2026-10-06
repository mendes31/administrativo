<?php

use App\adms\Helpers\CSRFHelper;

$job = $this->data['job'] ?? [];
$stats = $this->data['stats'] ?? [];
$report = $this->data['report'] ?? [];
$profile = $this->data['profile'] ?? null;
$urlAdm = $_ENV['URL_ADM'] ?? '';
$podeRegistrar = !empty($this->data['pode_registrar']);
$perms = $this->data['buttonPermission'] ?? [];
$csrfCommit = CSRFHelper::generateCSRFToken('form_import_center_commit');

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
            <span class="ms-auto d-flex gap-2">
                <?php if (!empty($job['profile_key'])): ?>
                    <a class="btn btn-primary btn-sm" href="<?php echo $urlAdm; ?>import-center-create?profile=<?php echo urlencode((string) $job['profile_key']); ?>">
                        <i class="fa-solid fa-file-arrow-up"></i> Enviar outro arquivo
                    </a>
                <?php endif; ?>
                <a class="btn btn-info btn-sm" href="<?php echo $urlAdm; ?>import-center"><i class="fa-solid fa-list"></i> Central</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php
            $qtdErros = (int) ($stats['errors'] ?? 0);
            $linhasOk = (int) ($stats['created'] ?? 0) + (int) ($stats['updated'] ?? 0);
            $isDryRun = !empty($job['dry_run']);
            $canCommit = $isDryRun
                && $podeRegistrar
                && $linhasOk > 0
                && in_array('ImportCenterCommit', $perms, true);
            $profileKey = (string) ($job['profile_key'] ?? '');
            $canUpload = $profileKey !== '';
            $confirmGravar = $qtdErros > 0
                ? 'Há ' . $qtdErros . ' linha(s) com erro — elas não serão gravadas. As outras ' . $linhasOk . ' serão gravadas. Continuar?'
                : 'Gravar no banco as ' . $linhasOk . ' linha(s) desta simulação?';
            ?>
            <?php if ($isDryRun): ?>
                <div class="alert alert-warning">
                    Esta execução foi uma <strong>simulação</strong>: nada foi gravado no banco.
                    Você pode <strong>gravar</strong> as linhas válidas ou <strong>enviar outro arquivo</strong> (nova importação do mesmo tipo).
                </div>
                <div class="row g-3 mb-4">
                    <?php if ($canCommit): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6">Gravar esta simulação</h3>
                                <p class="small text-muted mb-2">
                                    Reaproveita o arquivo e o mapeamento já usados.
                                    <?php if ($qtdErros > 0): ?>
                                        As linhas com erro ficam de fora; as demais entram no cadastro.
                                    <?php else: ?>
                                        Todas as linhas válidas entram no cadastro.
                                    <?php endif; ?>
                                </p>
                                <form method="POST" action="<?php echo $urlAdm; ?>import-center-commit/<?php echo (int) ($job['id'] ?? 0); ?>"
                                      onsubmit="return confirm(<?php echo htmlspecialchars(json_encode($confirmGravar, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfCommit, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-floppy-disk"></i> Gravar no banco
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($linhasOk === 0): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6">Gravar esta simulação</h3>
                                <p class="small text-muted mb-0">Nenhuma linha válida para gravar. Corrija a planilha e envie outro arquivo.</p>
                            </div>
                        </div>
                    <?php elseif (!in_array('ImportCenterCommit', $perms, true)): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6">Gravar esta simulação</h3>
                                <p class="small text-muted mb-0">Falta a permissão <em>ImportCenterCommit</em> para gravar a partir desta simulação.</p>
                            </div>
                        </div>
                    <?php elseif (!$podeRegistrar): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6">Gravar esta simulação</h3>
                                <p class="small text-muted mb-0">O arquivo ou o mapeamento desta simulação não está mais disponível. Envie a planilha de novo.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($canUpload): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6">Enviar outro arquivo</h3>
                                <p class="small text-muted mb-2">Nova importação do tipo <?php echo $profile ? htmlspecialchars($profile->label(), ENT_QUOTES, 'UTF-8') : htmlspecialchars($profileKey, ENT_QUOTES, 'UTF-8'); ?>. A simulação fica marcada por padrão.</p>
                                <form method="POST" action="<?php echo $urlAdm; ?>import-center-create" enctype="multipart/form-data" class="row g-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_center_upload'); ?>">
                                    <input type="hidden" name="profile" value="<?php echo htmlspecialchars($profileKey, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="operation" value="<?php echo htmlspecialchars((string) ($job['operation'] ?? 'upsert'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="empty_policy" value="<?php echo htmlspecialchars((string) ($job['empty_policy'] ?? 'skip'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="col-12">
                                        <input class="form-control form-control-sm" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                                    </div>
                                    <?php if ($profileKey === 'sst_epis'): ?>
                                        <div class="col-12">
                                            <input class="form-control form-control-sm" type="file" name="images_zip" accept=".zip,application/zip">
                                            <div class="form-text">ZIP das fotos (opcional)</div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dry_run" id="view_dry_run" value="1" checked>
                                            <label class="form-check-label" for="view_dry_run">Somente simular</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-file-arrow-up"></i> Enviar e mapear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
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
