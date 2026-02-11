<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes do Candidato</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="text-decoration-none">Currículos</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-id-card me-2"></i>Candidato #<?= (int)($this->data['candidato']['id'] ?? 0) ?></span>
            <div class="btn-group">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-edit/<?= (int)$this->data['candidato']['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <?php if (!empty($this->data['log_resumo']['has_logs'])): ?>
                    <a href="<?= htmlspecialchars($this->data['log_resumo']['list_url']); ?>"
                       class="btn btn-outline-info btn-sm">
                        <i class="fas fa-history me-1"></i>
                        Log de Alterações (<?= (int)$this->data['log_resumo']['count']; ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php $c = $this->data['candidato'] ?? []; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Dados do candidato</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nome</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['nome'] ?? '') ?></dd>

                        <dt class="col-sm-4">E-mail</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['email'] ?? '') ?></dd>

                        <dt class="col-sm-4">Telefone</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['telefone'] ?? '') ?></dd>

                        <dt class="col-sm-4">Cidade / UF</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars(($c['cidade'] ?? '') . (isset($c['estado']) && $c['estado'] ? ' / ' . $c['estado'] : '')) ?>
                        </dd>

                        <dt class="col-sm-4">Origem</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['origem'] ?? '') ?></dd>

                        <dt class="col-sm-4">Status do processo</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($c['status_processo'] ?? '') ?>
                            </span>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5>Informações de LGPD</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status LGPD</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($c['lgpd_status'] ?? '') ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Consentimento em</dt>
                        <dd class="col-sm-8">
                            <?= FormatHelper::formatDateTime($c['lgpd_data_consentimento'] ?? null) ?>
                        </dd>

                        <dt class="col-sm-4">Expira em</dt>
                        <dd class="col-sm-8">
                            <?= FormatHelper::formatDateTime($c['lgpd_data_expiracao'] ?? null) ?>
                        </dd>

                        <dt class="col-sm-4">Motivo anonimização</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($c['lgpd_motivo_anonimizacao'] ?? '-') ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <h5>Observações</h5>
                    <div class="border rounded p-2" style="min-height: 60px;">
                        <?= nl2br(htmlspecialchars($c['observacoes'] ?? '')) ?: '<span class="text-muted">Nenhuma observação registrada.</span>' ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($this->data['anexos'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Currículos / Anexos</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Arquivo</th>
                                        <th>Enviado em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['anexos'] as $anexo): ?>
                                        <tr>
                                            <td><?= (int)$anexo['id'] ?></td>
                                            <td><?= htmlspecialchars($anexo['tipo'] ?? '') ?></td>
                                            <td>
                                                <?php if (!empty($anexo['arquivo_caminho'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?= urlencode($anexo['arquivo_caminho']); ?>"
                                                       target="_blank"
                                                       class="text-decoration-underline fw-semibold">
                                                        <i class="fas fa-download me-1"></i>
                                                        <?= htmlspecialchars($anexo['nome_original'] ?? basename($anexo['arquivo_caminho'])) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= \App\adms\Helpers\FormatHelper::formatDateTime($anexo['created_at'] ?? null) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


