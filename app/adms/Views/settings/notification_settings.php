<?php
$groups = $this->data['notification_groups'] ?? [];
$anyEnabled = !empty($this->data['any_enabled']);
$csrfToken = $this->data['csrf_token'] ?? '';
?>
<div class="container-fluid px-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">
            <i class="fas fa-bell-slash me-2"></i>Configurações de Notificações
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Administração</li>
            <li class="breadcrumb-item">Configurações</li>
            <li class="breadcrumb-item active">Notificações</li>
        </ol>
    </div>

    <div class="alert alert-warning border-warning shadow-sm">
        <div class="d-flex gap-2 align-items-start">
            <i class="fas fa-exclamation-triangle fa-lg mt-1"></i>
            <div>
                <strong>Ambiente de testes:</strong> todas as notificações automáticas ficam <strong>desligadas por padrão</strong>.
                Ative apenas os tipos que deseja testar. Enquanto desligadas, crons e rotinas automáticas não enviam e-mail nem alertas in-app.
                <?php if (!$anyEnabled): ?>
                    <span class="d-block mt-1 text-success"><i class="fas fa-check-circle me-1"></i>Nenhuma notificação automática está ativa no momento.</span>
                <?php else: ?>
                    <span class="d-block mt-1 text-danger"><i class="fas fa-broadcast-tower me-1"></i>Há notificações automáticas ativas — revise antes de rodar crons em produção.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-notification-settings">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($groups as $group): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><?= htmlspecialchars($group['module'] ?? '') ?></h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($group['items'] ?? [] as $item): ?>
                                <?php
                                $key = (string) ($item['key'] ?? '');
                                $isNotif = !empty($item['is_notification']);
                                ?>
                                <div class="form-check form-switch mb-3 pb-3 border-bottom">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="enabled[]" value="<?= htmlspecialchars($key) ?>"
                                           id="notif_<?= htmlspecialchars($key) ?>"
                                           <?= !empty($item['enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_<?= htmlspecialchars($key) ?>">
                                        <span class="fw-semibold"><?= htmlspecialchars($item['label'] ?? '') ?></span>
                                        <?php if ($isNotif): ?>
                                            <span class="badge bg-secondary ms-1">notificação</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark ms-1">regra de dados</span>
                                        <?php endif; ?>
                                        <span class="d-block small text-muted mt-1"><?= htmlspecialchars($item['description'] ?? '') ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Salvar configurações
                </button>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Orientações</h6>
                    </div>
                    <div class="card-body small text-muted">
                        <ul class="mb-0 ps-3">
                            <li class="mb-2">Esta tela controla <strong>apenas</strong> alertas automáticos de <strong>SST</strong> (cron) e <strong>Treinamentos</strong> (cron/vínculo obrigatório).</li>
                            <li class="mb-2"><strong>Não afeta</strong> informativos, políticas internas, SAC, folha, avaliações, projetos nem outros push do portal — esses módulos seguem com a lógica própria de publicação.</li>
                            <li class="mb-2">Para cada tipo, você pode ligar só <strong>e-mail</strong>, só <strong>notificação interna / push</strong>, ou ambos.</li>
                            <li class="mb-2">O digest SST roda no <strong>login</strong> (no máximo 1 vez por dia) e também pelo script <code>scripts/cron_sst_pendencias.php</code>. Vistorias de equipamentos usam o mesmo disparo no login (<code>cron_sst_equipamento_vistorias.php</code> continua válido no agendador).</li>
                            <li>Recomendado manter tudo desligado durante homologação e ativar gradualmente.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
