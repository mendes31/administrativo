<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$presentes = $this->data['presentes'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$totalPresentes = (int) ($this->data['total_presentes'] ?? 0);
$totalAguardando = (int) ($this->data['total_aguardando'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0">Painel da Portaria</h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= $url ?>dashboard">Voltar ao Dashboard</a>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <p class="text-muted small mb-3">
        Use os cards abaixo para navegar (especialmente no celular). O menu lateral continua disponível no desktop.
    </p>

    <div class="row g-3 mb-4">
        <?php if (in_array('PortariaMovimentacoes', $perms, true)): ?>
            <div class="col-12 col-md-6 col-xl-3 d-flex">
                <a href="<?= $url ?>portaria-movimentacoes" class="text-decoration-none flex-fill">
                    <div class="card shadow-sm h-100 border-success">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-exchange-alt fa-3x text-success mb-3"></i>
                            <h2 class="h5 mb-1">Movimentações</h2>
                            <p class="text-muted small mb-0">Entrada, saída e termo</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (in_array('PortariaAutorizacoes', $perms, true)): ?>
            <div class="col-12 col-md-6 col-xl-3 d-flex">
                <a href="<?= $url ?>portaria-autorizacoes" class="text-decoration-none flex-fill">
                    <div class="card shadow-sm h-100 border-warning">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-clipboard-check fa-3x text-warning mb-3"></i>
                            <h2 class="h5 mb-1">Autorizações</h2>
                            <p class="text-muted small mb-1">Agenda e liberações</p>
                            <?php if ($totalAguardando > 0): ?>
                                <span class="badge bg-warning text-dark"><?= $totalAguardando ?> aguardando</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (in_array('PortariaAutorizacoesCreate', $perms, true)): ?>
            <div class="col-12 col-md-6 col-xl-3 d-flex">
                <a href="<?= $url ?>portaria-autorizacoes-create?modo=rapido" class="text-decoration-none flex-fill">
                    <div class="card shadow-sm h-100 border-primary">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                            <h2 class="h5 mb-1">Liberação rápida</h2>
                            <p class="text-muted small mb-0">Chegada sem aviso</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (in_array('PortariaVisitantes', $perms, true)): ?>
            <div class="col-12 col-md-6 col-xl-3 d-flex">
                <a href="<?= $url ?>portaria-visitantes" class="text-decoration-none flex-fill">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-secondary mb-3"></i>
                            <h2 class="h5 mb-1">Visitantes</h2>
                            <p class="text-muted small mb-0">Cadastro e termos</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (in_array('PortariaPontos', $perms, true)): ?>
            <div class="col-12 col-md-6 col-xl-3 d-flex">
                <a href="<?= $url ?>portaria-pontos" class="text-decoration-none flex-fill">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-map-marker-alt fa-3x text-danger mb-3"></i>
                            <h2 class="h5 mb-1">Pontos de controle</h2>
                            <p class="text-muted small mb-0">Locais de E/S</p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Presentes agora</h2>
                    <strong class="display-6"><?= $totalPresentes ?></strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Aguardando</h2>
                    <strong class="display-6"><?= $totalAguardando ?></strong>
                </div>
            </div>
        </div>
        <?php if (in_array('PortariaMovimentacoes', $perms, true)): ?>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-grid">
                        <a class="btn btn-success" href="<?= $url ?>portaria-movimentacoes">
                            <i class="fas fa-sign-in-alt me-1"></i>Registrar entrada/saída
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Visitantes dentro do complexo</div>
        <div class="card-body">
            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Visitante</th>
                            <th>Empresa</th>
                            <th>Entrada</th>
                            <th>Ponto</th>
                            <th>Anfitrião</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presentes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $item['visitante_nome']) ?></td>
                                <td><?= htmlspecialchars((string) ($item['empresa'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) $item['ocorrido_em']) ?></td>
                                <td><?= htmlspecialchars((string) ($item['ponto_nome'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['anfitriao_nome'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($presentes === []): ?>
                            <tr><td colspan="5" class="text-muted text-center">Nenhum visitante presente.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none list-mobile">
                <?php if ($presentes === []): ?>
                    <p class="text-muted text-center mb-0">Nenhum visitante presente.</p>
                <?php endif; ?>
                <?php foreach ($presentes as $item): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-2"><?= htmlspecialchars((string) $item['visitante_nome']) ?></h5>
                            <div class="small mb-1"><b>Empresa:</b> <?= htmlspecialchars((string) ($item['empresa'] ?? '—')) ?></div>
                            <div class="small mb-1"><b>Entrada:</b> <?= htmlspecialchars((string) $item['ocorrido_em']) ?></div>
                            <div class="small mb-1"><b>Ponto:</b> <?= htmlspecialchars((string) ($item['ponto_nome'] ?? '—')) ?></div>
                            <div class="small mb-0"><b>Anfitrião:</b> <?= htmlspecialchars((string) ($item['anfitriao_nome'] ?? '—')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
