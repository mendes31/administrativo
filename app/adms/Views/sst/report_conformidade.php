<?php
$resumo = $this->data['resumo'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrf = $this->data['csrf_esocial'] ?? '';
$semEvento = $resumo['origens_sem_evento'] ?? [];
$sstRascunhoKind = 'esocial';
?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-balance-scale me-2"></i>Conformidade SST</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php include __DIR__ . '/partials/sst_rascunho_oficial_alert.php'; ?>
    <?php if (!empty($_SESSION['esocial_sync_erros'])): ?>
        <div class="alert alert-warning small">
            <strong>Detalhes da sincronização:</strong>
            <ul class="mb-0"><?php foreach ($_SESSION['esocial_sync_erros'] as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
        </div>
        <?php unset($_SESSION['esocial_sync_erros']); endif; ?>
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
                <div class="text-muted small">PGR vigente</div>
                <div class="fs-4 fw-bold text-<?= !empty($resumo['pgr_vigente']) ? 'success' : 'danger' ?>"><?= !empty($resumo['pgr_vigente']) ? 'Sim' : 'Não' ?></div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
                <div class="text-muted small">PCMSO vigente</div>
                <div class="fs-4 fw-bold text-<?= !empty($resumo['pcmso_vigente']) ? 'success' : 'danger' ?>"><?= !empty($resumo['pcmso_vigente']) ? 'Sim' : 'Não' ?></div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
                <div class="text-muted small">Rascunhos eSocial gerados</div>
                <div class="fs-4 fw-bold"><?= (int)($resumo['esocial_gerados'] ?? 0) ?></div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
                <div class="text-muted small">Conferências internas</div>
                <div class="fs-4 fw-bold"><?= (int)($resumo['esocial_enviados'] ?? 0) ?></div>
            </div></div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Programas a vencer (60 dias)</span>
                    <?php if (in_array('SstListProgramas', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-list-programas" class="btn btn-sm btn-outline-primary">Ver todos</a><?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($resumo['programas_vencendo'])): ?>
                        <p class="p-3 text-muted mb-0">Nenhum programa vigente próximo do vencimento.</p>
                    <?php else: foreach ($resumo['programas_vencendo'] as $p): ?>
                        <div class="px-3 py-2 border-bottom small">
                            <span class="badge bg-secondary"><?= htmlspecialchars($p['tipo'] ?? '') ?></span>
                            <?= htmlspecialchars($p['titulo'] ?? '') ?>
                            <?php if (!empty($p['vigencia_fim'])): ?> — val. <?= date('d/m/Y', strtotime($p['vigencia_fim'])) ?><?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Rascunhos S-2210/S-2220 ainda não gerados</div>
                <div class="card-body">
                    <p class="small text-muted">Não inclui EPI nem treinamento — S-2240 e S-2245 estão bloqueados. Estes números não são obrigação legal de transmissão.</p>
                    <ul class="mb-3">
                        <li>Acidentes (S-2210): <strong><?= (int)($semEvento['acidentes'] ?? 0) ?></strong></li>
                        <li>ASOs (S-2220): <strong><?= (int)($semEvento['asos'] ?? 0) ?></strong></li>
                    </ul>
                    <?php if (in_array('SstSyncEsocialPendentes', $perms, true)): ?>
                        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-sync-esocial-pendentes" onsubmit="return confirm('Gera apenas rascunhos S-2210 e S-2220. Não transmite ao governo. Continuar?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i> Gerar rascunhos pendentes</button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array('SstListEsocialEventos', $perms, true)): ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-list-esocial-eventos" class="btn btn-outline-secondary btn-sm ms-1">Fila eSocial</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if (in_array('SstCreatePrograma', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-create-programa" class="btn btn-success btn-sm">+ Programa PGR/PCMSO</a><?php endif; ?>
        <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary btn-sm">Dashboard SST</a>
    </div>
</div>
