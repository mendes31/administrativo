<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$presentes = $this->data['presentes'] ?? [];
?>
<div class="container-fluid px-4">
    <h1 class="h3 mt-4 mb-3">Painel da Portaria</h1>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Presentes agora</h2>
                    <strong class="display-6"><?= (int) ($this->data['total_presentes'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Autorizações aguardando</h2>
                    <strong class="display-6"><?= (int) ($this->data['total_aguardando'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-primary" href="<?= $url ?>portaria-movimentacoes">Registrar movimentação</a>
                    <a class="btn btn-outline-primary" href="<?= $url ?>portaria-autorizacoes">Ver autorizações</a>
                </div>
            </div>
        </div>
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
