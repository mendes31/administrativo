<?php

use App\adms\Helpers\CSRFHelper;

$a = $this->data['autorizacao'];
$tokenKey = 'form_portaria_autorizacao_view_' . (int) $a['id'];
$url = (string) ($_ENV['URL_ADM'] ?? '');
$contatos = $a['contatos'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0">Autorização <?= htmlspecialchars((string) $a['protocolo']) ?></h1>
        <a class="btn btn-secondary" href="<?= $url ?>portaria-autorizacoes">Voltar</a>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">Dados da visita</div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-5 col-sm-4">Visitante</dt>
                        <dd class="col-7 col-sm-8"><?= htmlspecialchars((string) $a['visitante_nome']) ?></dd>
                        <dt class="col-5 col-sm-4">Anfitrião</dt>
                        <dd class="col-7 col-sm-8"><?= htmlspecialchars((string) ($a['anfitriao_nome'] ?? '—')) ?></dd>
                        <dt class="col-5 col-sm-4">Destino</dt>
                        <dd class="col-7 col-sm-8"><?= htmlspecialchars((string) ($a['departamento_nome'] ?? '—')) ?></dd>
                        <dt class="col-5 col-sm-4">Período</dt>
                        <dd class="col-7 col-sm-8"><?= htmlspecialchars((string) $a['data_inicio']) ?> a <?= htmlspecialchars((string) $a['data_fim']) ?></dd>
                        <dt class="col-5 col-sm-4">Motivo</dt>
                        <dd class="col-7 col-sm-8"><?= htmlspecialchars((string) ($a['motivo'] ?? '—')) ?></dd>
                        <dt class="col-5 col-sm-4">Status</dt>
                        <dd class="col-7 col-sm-8"><span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span></dd>
                    </dl>
                    <?php if (($a['status'] ?? '') === 'aguardando'): ?>
                        <form method="post" class="d-flex flex-wrap gap-2">
                            <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken($tokenKey) ?>">
                            <button name="acao" value="autorizar" class="btn btn-success flex-fill flex-md-grow-0">Autorizar</button>
                            <button name="acao" value="recusar" class="btn btn-danger flex-fill flex-md-grow-0">Recusar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">Notificar / registrar contato</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken($tokenKey) ?>">
                        <input type="hidden" name="acao" value="contato">
                        <div class="mb-2">
                            <label class="form-label">Canal</label>
                            <select class="form-select" name="canal">
                                <option value="push">Reenviar Push</option>
                                <option value="whatsapp">Reenviar WhatsApp</option>
                                <option value="ligacao">Registrar ligação</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Resultado (ligação)</label>
                            <input class="form-control" name="resultado" placeholder="autorizou, não atendeu...">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes"></textarea>
                        </div>
                        <button class="btn btn-primary w-100">Enviar / registrar</button>
                    </form>
                    <p class="small text-muted mt-2 mb-0">
                        Na criação da liberação rápida o sistema já tenta push e WhatsApp automaticamente.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header">Histórico de contatos</div>
        <div class="card-body">
            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Canal</th>
                            <th>Resultado</th>
                            <th>Porteiro</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contatos === []): ?>
                            <tr><td colspan="5" class="text-muted text-center">Nenhum contato registrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($contatos as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $c['ocorrido_em']) ?></td>
                                <td><?= htmlspecialchars((string) $c['canal']) ?></td>
                                <td><?= htmlspecialchars((string) ($c['resultado'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($c['porteiro_nome'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($c['observacoes'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none list-mobile">
                <?php if ($contatos === []): ?>
                    <p class="text-muted text-center mb-0">Nenhum contato registrado.</p>
                <?php endif; ?>
                <?php foreach ($contatos as $c): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <strong class="text-capitalize"><?= htmlspecialchars((string) $c['canal']) ?></strong>
                                <span class="badge bg-secondary"><?= htmlspecialchars((string) ($c['resultado'] ?? '—')) ?></span>
                            </div>
                            <div class="small mb-1"><b>Data:</b> <?= htmlspecialchars((string) $c['ocorrido_em']) ?></div>
                            <div class="small mb-1"><b>Porteiro:</b> <?= htmlspecialchars((string) ($c['porteiro_nome'] ?? '—')) ?></div>
                            <div class="small mb-0"><b>Observações:</b> <?= htmlspecialchars((string) ($c['observacoes'] ?? '—')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
