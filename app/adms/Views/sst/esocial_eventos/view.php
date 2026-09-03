<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstEsocialPolicy;
$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
$csrf = CSRFHelper::generateCSRFToken('sst_esocial_actions');
$payload = $item['payload_json'] ?? '';
$tipoEvento = (string) ($item['tipo_evento'] ?? '');
$geracaoBloqueada = SstEsocialPolicy::isGeracaoBloqueada($tipoEvento);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php include __DIR__ . '/../partials/sst_rascunho_oficial_alert.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-cloud me-2"></i><?= htmlspecialchars($tipoEvento) ?> #<?= (int)$item['id'] ?></h2>
        <span class="badge text-bg-warning">Não oficial</span>
        <?php if ($geracaoBloqueada): ?><span class="badge text-bg-secondary">geração bloqueada</span><?php endif; ?>
        <div class="ms-auto d-flex gap-1 flex-wrap">
            <?php if (!empty($payload) && in_array('SstExportEsocialJson', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-export-esocial-json/<?= (int)$item['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-download"></i> Exportar JSON</a>
            <?php endif; ?>
            <?php if (!$geracaoBloqueada && in_array('SstGenerateEsocialEvento', $perms, true)): ?>
                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-generate-esocial-evento" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="tipo_evento" value="<?= htmlspecialchars($tipoEvento) ?>">
                    <input type="hidden" name="origem_tabela" value="<?= htmlspecialchars($item['origem_tabela'] ?? '') ?>">
                    <input type="hidden" name="origem_id" value="<?= (int)($item['origem_id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-warning btn-sm">Regenerar rascunho</button>
                </form>
            <?php endif; ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-esocial-eventos" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header">Resumo</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm mb-0">
                        <tr><th>Colaborador</th><td><?= htmlspecialchars($item['colaborador_nome'] ?? '') ?></td></tr>
                        <tr><th>CPF</th><td><?= htmlspecialchars($item['colaborador_cpf'] ?? '-') ?></td></tr>
                        <tr><th>Origem</th><td><?= htmlspecialchars($item['origem_tabela'] ?? '') ?> #<?= (int)($item['origem_id'] ?? 0) ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($item['status'] ?? '') ?></td></tr>
                        <tr><th>Protocolo</th><td><?= htmlspecialchars($item['protocolo'] ?? '-') ?></td></tr>
                        <tr><th>Gerado em</th><td><?= !empty($item['data_geracao']) ? date('d/m/Y H:i', strtotime($item['data_geracao'])) : '-' ?></td></tr>
                        <tr><th>Enviado em</th><td><?= !empty($item['data_envio']) ? date('d/m/Y H:i', strtotime($item['data_envio'])) : '-' ?></td></tr>
                    </table>
                </div>
            </div>
            <?php if (!$geracaoBloqueada && in_array('SstMarkEsocialEnviado', $perms, true) && ($item['status'] ?? '') !== 'Enviado'): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header">Registrar conferência interna</div>
                    <div class="card-body">
                        <p class="small text-muted">Não transmite ao governo. Use só para anotar protocolo de um envio feito fora do sistema.</p>
                        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-mark-esocial-enviado">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <div class="mb-2"><label class="form-label small">Protocolo (opcional)</label><input type="text" name="protocolo" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="form-label small">Retorno / observação</label><textarea name="mensagem_retorno" class="form-control form-control-sm" rows="2"></textarea></div>
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Registrar conferência</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">Payload JSON</div>
                <div class="card-body">
                    <?php if ($payload): ?>
                        <pre class="bg-light p-3 small mb-0" style="max-height: 520px; overflow: auto;"><?= htmlspecialchars($payload) ?></pre>
                    <?php else: ?>
                        <p class="text-muted mb-0">Payload ainda não gerado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
