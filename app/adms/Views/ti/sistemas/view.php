<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$s = $this->data['sistema'] ?? [];
$acessos = $this->data['acessos'] ?? [];
$sid = (int) ($s['id'] ?? 0);
$csrfRevoke = CSRFHelper::generateCSRFToken('form_ti_acesso_revoke');
$perms = $this->data['buttonPermission'] ?? [];
$canUpdateSistema = in_array('TiSistemasUpdate', $perms, true);
$canCreateAcesso = in_array('TiAcessosCreate', $perms, true) && ($s['status'] ?? '') === 'ativo';
$canUpdateAcesso = in_array('TiAcessosUpdate', $perms, true);
$canRevokeAcesso = in_array('TiAcessosRevoke', $perms, true);
$dash = static function (?string $v): string {
    $v = trim((string) $v);
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : '—';
};
?>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3 text-break"><?= htmlspecialchars((string) ($s['nome'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Sistemas</a></li>
            <li class="breadcrumb-item active">#<?= $sid ?></li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">
            <div class="d-flex flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center gap-2 justify-content-between">
                <span class="fw-semibold">Dados gerais</span>
                <?php if ($canUpdateSistema || $canCreateAcesso): ?>
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                        <?php if ($canUpdateSistema): ?>
                            <a href="<?= htmlspecialchars($url . 'ti-sistemas-update/' . $sid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm">
                                <i class="fa-regular fa-pen-to-square me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                        <?php if ($canCreateAcesso): ?>
                            <a href="<?= htmlspecialchars($url . 'ti-acessos-create?sistema_id=' . $sid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-user-plus me-1"></i>Liberar acesso
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0 p-md-3">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="user-view-panel mb-0 border-0 shadow-none rounded-0">
                <div class="user-view-panel-body">
                    <div class="user-view-row">
                        <div class="user-view-row-label">Código</div>
                        <div class="user-view-row-value"><?= $dash($s['codigo'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Tipo</div>
                        <div class="user-view-row-value"><?= $dash($s['tipo'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Status</div>
                        <div class="user-view-row-value">
                            <span class="badge <?= ($s['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= htmlspecialchars((string) ($s['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Filial</div>
                        <div class="user-view-row-value"><?= $dash($s['filial_nome'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Localização</div>
                        <div class="user-view-row-value"><?= $dash($s['localizacao'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Tag do equipamento</div>
                        <div class="user-view-row-value"><?= $dash($s['equipamento_tag'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Fabricante</div>
                        <div class="user-view-row-value"><?= $dash($s['fabricante'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Modelo</div>
                        <div class="user-view-row-value"><?= $dash($s['modelo'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Nº série / patrimônio</div>
                        <div class="user-view-row-value"><?= $dash($s['numero_serie'] ?? null) ?></div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Descrição</div>
                        <div class="user-view-row-value"><?= nl2br($dash($s['descricao'] ?? null)) ?></div>
                    </div>
                    <?php if (!empty($s['observacoes'])): ?>
                        <div class="user-view-row">
                            <div class="user-view-row-label">Observações</div>
                            <div class="user-view-row-value"><?= nl2br(htmlspecialchars((string) $s['observacoes'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-3 px-md-0 pb-3 pt-2"><?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                include './app/adms/Views/partials/button_log_alteracoes.php';
            ?></div>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header"><i class="fas fa-users me-2"></i>Usuários com acesso</div>
        <div class="card-body">
            <?php if ($acessos === []): ?>
                <p class="text-muted mb-0">Nenhum acesso registrado.</p>
            <?php else: ?>
                <div class="table-responsive list-desktop">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Login no sistema</th>
                                <th>Perfil / obs.</th>
                                <th>Situação</th>
                                <th>Liberação</th>
                                <th>Revogação</th>
                                <?php if ($canUpdateAcesso || $canRevokeAcesso): ?>
                                    <th></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($acessos as $a): ?>
                                <tr>
                                    <td>
                                        <a href="<?= htmlspecialchars($url . 'view-user/' . (int) $a['adms_user_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) ($a['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($a['login_externo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($a['perfil_obs'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge <?= ($a['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars((string) ($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($a['data_liberacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($a['data_revogacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php if ($canUpdateAcesso || $canRevokeAcesso): ?>
                                        <td class="text-nowrap">
                                            <?php if (($a['status'] ?? '') === 'ativo' && $canUpdateAcesso): ?>
                                                <a href="<?= htmlspecialchars($url . 'ti-acessos-update/' . (int) $a['id'] . '?return=' . rawurlencode($url . 'ti-sistemas-view/' . $sid), ENT_QUOTES, 'UTF-8') ?>"
                                                   class="btn btn-outline-warning btn-sm">Editar</a>
                                            <?php endif; ?>
                                            <?php if (($a['status'] ?? '') === 'ativo' && $canRevokeAcesso): ?>
                                                <form method="post" action="<?= htmlspecialchars($url . 'ti-acessos-revoke/' . (int) $a['id'], ENT_QUOTES, 'UTF-8') ?>" class="d-inline"
                                                      onsubmit="return confirm('Confirmar que a conta foi inativada neste sistema?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfRevoke, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($url . 'ti-sistemas-view/' . $sid, ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Inativar</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="list-mobile user-view-stack">
                    <?php foreach ($acessos as $a): ?>
                        <article class="user-view-list-card <?= ($a['status'] ?? '') === 'ativo' ? 'user-view-list-card--active' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <a class="user-view-list-card-title mb-0 text-decoration-none"
                                   href="<?= htmlspecialchars($url . 'view-user/' . (int) $a['adms_user_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ($a['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <span class="badge flex-shrink-0 <?= ($a['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars((string) ($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <dl class="user-view-list-card-dl mb-0">
                                <div><dt>Login</dt><dd class="text-break"><?= htmlspecialchars((string) ($a['login_externo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Perfil</dt><dd class="text-break"><?= htmlspecialchars((string) ($a['perfil_obs'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Liberação</dt><dd><?= htmlspecialchars((string) ($a['data_liberacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Revogação</dt><dd><?= htmlspecialchars((string) ($a['data_revogacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                            </dl>
                            <?php if (($a['status'] ?? '') === 'ativo' && ($canUpdateAcesso || $canRevokeAcesso)): ?>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php if ($canUpdateAcesso): ?>
                                        <a href="<?= htmlspecialchars($url . 'ti-acessos-update/' . (int) $a['id'] . '?return=' . rawurlencode($url . 'ti-sistemas-view/' . $sid), ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-outline-warning btn-sm flex-fill">Editar</a>
                                    <?php endif; ?>
                                    <?php if ($canRevokeAcesso): ?>
                                        <form method="post" action="<?= htmlspecialchars($url . 'ti-acessos-revoke/' . (int) $a['id'], ENT_QUOTES, 'UTF-8') ?>" class="flex-fill"
                                              onsubmit="return confirm('Confirmar que a conta foi inativada neste sistema?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfRevoke, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($url . 'ti-sistemas-view/' . $sid, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Inativar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
