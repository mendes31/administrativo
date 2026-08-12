<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$v = $this->data['visitante'];
$termo = $v['termo'] ?? ['status' => 'ausente'];
$aceites = $this->data['aceites'] ?? [];
$autorizacoes = $this->data['autorizacoes'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0"><?= htmlspecialchars((string) $v['nome']) ?></h1>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-warning" href="<?= $url ?>portaria-visitantes-update/<?= (int) $v['id'] ?>">Editar</a>
            <a class="btn btn-primary" href="<?= $url ?>portaria-autorizacoes-create?visitante_id=<?= (int) $v['id'] ?>">Nova autorização</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">Cadastro</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Documento</dt>
                        <dd class="col-7"><?= htmlspecialchars((string) ($v['documento'] ?? '—')) ?></dd>
                        <dt class="col-5">Telefone</dt>
                        <dd class="col-7"><?= htmlspecialchars((string) ($v['telefone'] ?? '—')) ?></dd>
                        <dt class="col-5">Empresa</dt>
                        <dd class="col-7"><?= htmlspecialchars((string) ($v['empresa'] ?? '—')) ?></dd>
                        <dt class="col-5">E-mail</dt>
                        <dd class="col-7 text-break"><?= htmlspecialchars((string) ($v['email'] ?? '—')) ?></dd>
                        <dt class="col-5">Termo</dt>
                        <dd class="col-7"><span class="badge bg-secondary"><?= htmlspecialchars((string) $termo['status']) ?></span></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">Aceites de termo</div>
                <div class="card-body">
                    <div class="table-responsive d-none d-md-block list-desktop">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Termo</th>
                                    <th>Aceito em</th>
                                    <th>Válido até</th>
                                    <th>Método</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($aceites === []): ?>
                                    <tr><td colspan="4" class="text-muted text-center">Nenhum aceite registrado.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($aceites as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($a['termo_titulo'] ?? $a['lgpd_termo_id'])) ?></td>
                                        <td><?= htmlspecialchars((string) $a['aceito_em']) ?></td>
                                        <td><?= htmlspecialchars((string) $a['valido_ate']) ?></td>
                                        <td><?= htmlspecialchars((string) $a['metodo']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none list-mobile">
                        <?php if ($aceites === []): ?>
                            <p class="text-muted text-center mb-0">Nenhum aceite registrado.</p>
                        <?php endif; ?>
                        <?php foreach ($aceites as $a): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body py-3">
                                    <h6 class="mb-2"><?= htmlspecialchars((string) ($a['termo_titulo'] ?? $a['lgpd_termo_id'])) ?></h6>
                                    <div class="small mb-1"><b>Aceito em:</b> <?= htmlspecialchars((string) $a['aceito_em']) ?></div>
                                    <div class="small mb-1"><b>Válido até:</b> <?= htmlspecialchars((string) $a['valido_ate']) ?></div>
                                    <div class="small mb-0"><b>Método:</b> <?= htmlspecialchars((string) $a['metodo']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header">Autorizações</div>
        <div class="card-body">
            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                            <th>Período</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($autorizacoes === []): ?>
                            <tr><td colspan="3" class="text-muted text-center">Nenhuma autorização.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($autorizacoes as $a): ?>
                            <tr>
                                <td><a href="<?= $url ?>portaria-autorizacoes-view/<?= (int) $a['id'] ?>"><?= htmlspecialchars((string) $a['protocolo']) ?></a></td>
                                <td><?= htmlspecialchars((string) $a['data_inicio']) ?> a <?= htmlspecialchars((string) $a['data_fim']) ?></td>
                                <td><?= htmlspecialchars((string) $a['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-block d-md-none list-mobile">
                <?php if ($autorizacoes === []): ?>
                    <p class="text-muted text-center mb-0">Nenhuma autorização.</p>
                <?php endif; ?>
                <?php foreach ($autorizacoes as $a): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <a href="<?= $url ?>portaria-autorizacoes-view/<?= (int) $a['id'] ?>" class="fw-semibold">
                                    <?= htmlspecialchars((string) $a['protocolo']) ?>
                                </a>
                                <span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span>
                            </div>
                            <div class="small mb-0">
                                <b>Período:</b>
                                <?= htmlspecialchars((string) $a['data_inicio']) ?> a <?= htmlspecialchars((string) $a['data_fim']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
