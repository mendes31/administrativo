<?php
$total = (int) ($this->data['total'] ?? 0);
$users = $this->data['users'] ?? [];
$currentUserId = (int) ($this->data['current_user_id'] ?? 0);
$consultedAt = (string) ($this->data['consulted_at'] ?? '');
$filtros = $this->data['filtros'] ?? [];
$totalNunca = (int) ($this->data['total_nunca'] ?? 0);
$perPage = (int) ($this->data['per_page'] ?? 20);
$buttonPermission = $this->data['buttonPermission'] ?? [];

$exportParams = array_filter([
    'usuario_nome' => $filtros['usuario_nome'] ?? '',
    'status' => $filtros['status'] ?? '',
    'apenas_nunca' => $filtros['apenas_nunca'] ?? '',
    'sort' => $filtros['sort'] ?? '',
], static fn ($v) => $v !== '' && $v !== null);
$exportQuery = $exportParams !== [] ? '?' . http_build_query($exportParams) : '';

include './app/adms/Views/logs/partials/userAvatarHelper.php';
?>
<style>
    .connected-users-mobile .card { border-radius: 12px; overflow: hidden; }
    .connected-users-mobile .connected-user-dl {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.35rem 0.75rem;
        font-size: 0.875rem;
        margin: 0;
    }
    .connected-users-mobile .connected-user-dl dt {
        margin: 0;
        color: var(--bs-secondary-color);
        font-weight: 600;
        white-space: nowrap;
    }
    .connected-users-mobile .connected-user-dl dd {
        margin: 0;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .connected-users-desktop.table-responsive { -webkit-overflow-scrolling: touch; }
    .connected-user-thumb { display: block; }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-sm-row gap-2 gap-sm-0 align-items-start align-items-sm-center">
        <h2 class="mt-2 mt-md-3 mb-0 fs-4 fs-md-3">Último acesso por usuário</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 11rem;" aria-current="page">Último acesso</li>
        </ol>
    </div>

    <?php
    $activeTab = 'last-access';
    include './app/adms/Views/logs/partials/accessUsersTabs.php';
    ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center justify-content-between">
            <span class="fw-semibold">Listar</span>
            <div class="d-flex gap-2 align-items-center justify-content-between justify-content-sm-end flex-wrap">
                <?php
                if (in_array('ExportUsersLastAccessPdf', $buttonPermission, true)) {
                    echo "<a href='{$_ENV['URL_ADM']}export-users-last-access-pdf{$exportQuery}' class='btn btn-outline-danger btn-sm js-pwa-file-export' data-export-filename='ultimo_acesso_usuarios.pdf' title='Exportar PDF com filtros atuais'><i class='fa-solid fa-file-pdf'></i> PDF</a>";
                }
                if (in_array('ExportUsersLastAccessExcel', $buttonPermission, true)) {
                    echo "<a href='{$_ENV['URL_ADM']}export-users-last-access-excel{$exportQuery}' class='btn btn-outline-success btn-sm' title='Exportar Excel com filtros atuais'><i class='fa-solid fa-file-excel'></i> Excel</a>";
                }
                ?>
                <a href="?" class="btn btn-outline-primary btn-sm flex-grow-1 flex-sm-grow-0" title="Recarregar">
                    <i class="fas fa-sync-alt me-1"></i> Atualizar
                </a>
                <span class="badge bg-secondary"><?= $total; ?> usuário<?= $total === 1 ? '' : 's'; ?></span>
                <?php if ($totalNunca > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $totalNunca; ?> nunca acessou</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="alert alert-light border mb-3 small">
                <i class="fas fa-info-circle text-primary me-1"></i>
                Lista <strong>todos os usuários</strong> cadastrados com a data do último <strong>LOGIN</strong> registrado em
                <code>adms_log_acessos</code>. Quem nunca entrou no sistema aparece como <strong>Nunca acessou</strong>.
                <?php if ($consultedAt !== ''): ?>
                    <span class="d-block mt-2 text-muted"><i class="far fa-clock me-1"></i>Consulta: <?= htmlspecialchars($consultedAt, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="usuario_nome" class="form-label mb-1">Nome / e-mail / @usuário</label>
                    <input type="text" name="usuario_nome" id="usuario_nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Buscar...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="Ativo" <?= ($filtros['status'] ?? '') === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Inativo" <?= ($filtros['status'] ?? '') === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label mb-1">Ordenar por</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="nome" <?= ($filtros['sort'] ?? 'nome') === 'nome' ? 'selected' : ''; ?>>Nome (A–Z)</option>
                        <option value="ultimo_login" <?= ($filtros['sort'] ?? '') === 'ultimo_login' ? 'selected' : ''; ?>>Último login (recente)</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label for="per_page" class="form-label mb-1 me-2">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm w-auto mx-1">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : ''; ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex flex-wrap gap-2 align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="apenas_nunca" id="apenas_nunca" value="1"
                            <?= ($filtros['apenas_nunca'] ?? '') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="apenas_nunca">Só quem nunca acessou</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="list-users-last-access" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                </div>
            </form>

            <?php if ($total === 0): ?>
                <p class="text-muted mb-0">Nenhum usuário encontrado com os filtros informados.</p>
            <?php else: ?>

                <div class="table-responsive connected-users-desktop d-none d-md-block">
                    <table class="table table-bordered table-hover table-striped table-sm mb-0 align-middle">
                        <thead class="table-success">
                            <tr>
                                <th class="text-start ps-2" scope="col">Usuário</th>
                                <th class="text-start ps-2" scope="col">E-mail</th>
                                <th class="text-start ps-2" scope="col">@usuário</th>
                                <th class="text-start ps-2" scope="col">Status</th>
                                <th class="text-start ps-2" scope="col">Último login</th>
                                <th class="text-start ps-2" scope="col">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $row): ?>
                                <?php
                                $uid = (int) ($row['user_id'] ?? 0);
                                $isSelf = $currentUserId > 0 && $uid === $currentUserId;
                                $ultimo = !empty($row['ultimo_login'])
                                    ? date('d/m/Y H:i:s', strtotime((string) $row['ultimo_login']))
                                    : null;
                                $status = (string) ($row['user_status'] ?? '');
                                ?>
                                <tr class="<?= $isSelf ? 'table-primary' : ''; ?><?= $ultimo === null ? ' table-warning' : ''; ?>">
                                    <td class="text-start ps-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <?= $renderUserAvatar($row, 36); ?>
                                            <span class="text-break">
                                                <?= htmlspecialchars((string) ($row['user_name'] ?? '—')); ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge bg-primary ms-1">Você</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-start ps-2 small"><?= htmlspecialchars((string) ($row['user_email'] ?? '')); ?></td>
                                    <td class="text-start ps-2 small text-muted"><?= htmlspecialchars((string) ($row['user_username'] ?? '')); ?></td>
                                    <td class="text-start ps-2 small">
                                        <span class="badge <?= $status === 'Ativo' ? 'bg-success' : 'bg-secondary'; ?>"><?= htmlspecialchars($status ?: '—'); ?></span>
                                    </td>
                                    <td class="text-start ps-2 small">
                                        <?php if ($ultimo !== null): ?>
                                            <?= htmlspecialchars($ultimo); ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Nunca acessou</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start ps-2 small"><?= htmlspecialchars((string) ($row['ultimo_ip'] ?? '—')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="connected-users-mobile d-md-none">
                    <?php foreach ($users as $row): ?>
                        <?php
                        $uid = (int) ($row['user_id'] ?? 0);
                        $isSelf = $currentUserId > 0 && $uid === $currentUserId;
                        $ultimo = !empty($row['ultimo_login'])
                            ? date('d/m/Y H:i:s', strtotime((string) $row['ultimo_login']))
                            : null;
                        $status = (string) ($row['user_status'] ?? '');
                        ?>
                        <div class="card mb-3 shadow-sm border <?= $isSelf ? 'border-primary border-2' : ($ultimo === null ? 'border-warning' : ''); ?>">
                            <div class="card-header py-2 <?= $isSelf ? 'bg-primary bg-opacity-10' : 'bg-light'; ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <?= $renderUserAvatar($row, 44); ?>
                                    <span class="fw-semibold text-break"><?= htmlspecialchars((string) ($row['user_name'] ?? '—')); ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-primary">Você</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <dl class="connected-user-dl">
                                    <dt>E-mail</dt>
                                    <dd><?= htmlspecialchars((string) ($row['user_email'] ?? '—')); ?></dd>
                                    <dt>Login</dt>
                                    <dd class="text-muted"><?= htmlspecialchars((string) ($row['user_username'] ?? '—')); ?></dd>
                                    <dt>Status</dt>
                                    <dd><span class="badge <?= $status === 'Ativo' ? 'bg-success' : 'bg-secondary'; ?>"><?= htmlspecialchars($status ?: '—'); ?></span></dd>
                                    <dt>Último login</dt>
                                    <dd>
                                        <?php if ($ultimo !== null): ?>
                                            <?= htmlspecialchars($ultimo); ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Nunca acessou</span>
                                        <?php endif; ?>
                                    </dd>
                                    <dt>IP</dt>
                                    <dd><?= htmlspecialchars((string) ($row['ultimo_ip'] ?? '—')); ?></dd>
                                </dl>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($this->data['pagination']['html']) || !empty($this->data['pagination']['total'])): ?>
                    <div class="mt-3 d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-between">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            <small class="text-muted mb-0">
                                Mostrando <?= (int) ($this->data['pagination']['first_item'] ?? 0); ?>
                                até <?= (int) ($this->data['pagination']['last_item'] ?? 0); ?>
                                de <?= (int) $this->data['pagination']['total']; ?> registro(s)
                            </small>
                        <?php endif; ?>
                        <?php if (!empty($this->data['pagination']['html'])): ?>
                            <div class="ms-sm-auto">
                                <?= $this->data['pagination']['html']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
