<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$s = $this->data['sistema'] ?? [];
$acessos = $this->data['acessos'] ?? [];
$sid = (int) ($s['id'] ?? 0);
$csrfRevoke = CSRFHelper::generateCSRFToken('form_ti_acesso_revoke');
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><?= htmlspecialchars((string) ($s['nome'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>">Sistemas</a></li>
            <li class="breadcrumb-item active">#<?= $sid ?></li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Dados gerais</span>
            <span>
                <?php if (in_array('TiSistemasUpdate', $perms, true)): ?>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas-update/' . $sid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i> Editar</a>
                <?php endif; ?>
                <?php if (in_array('TiAcessosCreate', $perms, true) && ($s['status'] ?? '') === 'ativo'): ?>
                    <a href="<?= htmlspecialchars($url . 'ti-acessos-create?sistema_id=' . $sid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-user-plus"></i> Liberar acesso</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row g-3">
                <div class="col-md-2"><strong>Código</strong><br><?= htmlspecialchars((string) ($s['codigo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-2"><strong>Tipo</strong><br><?= htmlspecialchars((string) ($s['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-2"><strong>Status</strong><br>
                    <span class="badge <?= ($s['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= htmlspecialchars((string) ($s['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div class="col-md-3"><strong>Filial</strong><br><?= htmlspecialchars((string) ($s['filial_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Localização</strong><br><?= htmlspecialchars((string) ($s['localizacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Tag do equipamento</strong><br><?= htmlspecialchars((string) ($s['equipamento_tag'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Fabricante</strong><br><?= htmlspecialchars((string) ($s['fabricante'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Modelo</strong><br><?= htmlspecialchars((string) ($s['modelo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Nº série / patrimônio</strong><br><?= htmlspecialchars((string) ($s['numero_serie'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-12"><strong>Descrição</strong><br><?= nl2br(htmlspecialchars((string) ($s['descricao'] ?? '—'), ENT_QUOTES, 'UTF-8')) ?></div>
                <?php if (!empty($s['observacoes'])): ?>
                    <div class="col-12"><strong>Observações</strong><br><?= nl2br(htmlspecialchars((string) $s['observacoes'], ENT_QUOTES, 'UTF-8')) ?></div>
                <?php endif; ?>
            </div>
            <div class="mt-3"><?php
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
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Login no sistema</th>
                                <th>Perfil / obs.</th>
                                <th>Situação</th>
                                <th>Liberação</th>
                                <th>Revogação</th>
                                <?php if (in_array('TiAcessosRevoke', $perms, true)): ?>
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
                                    <?php if (in_array('TiAcessosRevoke', $perms, true)): ?>
                                        <td>
                                            <?php if (($a['status'] ?? '') === 'ativo'): ?>
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
            <?php endif; ?>
        </div>
    </div>
</div>
