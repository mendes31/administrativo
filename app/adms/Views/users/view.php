<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Helpers\PositionDisplayHelper;
use App\adms\Helpers\UserEducationHelper;
use App\adms\Helpers\UserFormHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_user');
$csrf_token_delete_image = CSRFHelper::generateCSRFToken('form_delete_user_image');

$urlAdm = (string) ($_ENV['URL_ADM'] ?? '');
$userRow = is_array($this->data['user'] ?? null) ? $this->data['user'] : null;
$userId = (int) ($userRow['id'] ?? 0);
$perms = $this->data['buttonPermission'] ?? [];
$canUpdate = in_array('UpdateUser', $perms, true);
$canPerms = in_array('UpdateUserAccessLevels', $perms, true);

$activeTab = strtolower(trim((string) ($_GET['tab'] ?? 'usuario')));
$allowedTabs = ['usuario', 'pessoais', 'endereco', 'contratuais', 'formacoes', 'acessos', 'historico', 'permissoes'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'usuario';
}

$emptyHtml = static function (mixed $value, string $empty = 'Não informado'): string {
    if ($value === null || $value === '' || $value === false) {
        return '<span class="text-muted">' . htmlspecialchars($empty, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$row = static function (string $label, string $html): void {
    echo '<div class="user-view-row">';
    echo '<div class="user-view-row-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div class="user-view-row-value">' . $html . '</div>';
    echo '</div>';
};

$openPanel = static function (string $title, string $icon = 'fa-circle-info'): void {
    echo '<div class="user-view-panel mb-3">';
    echo '<div class="user-view-panel-head"><i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-2"></i>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div class="user-view-panel-body">';
};

$closePanel = static function (): void {
    echo '</div></div>';
};

$tabUrl = static function (string $tab) use ($urlAdm, $userId): string {
    return htmlspecialchars($urlAdm . 'view-user/' . $userId . '?tab=' . rawurlencode($tab), ENT_QUOTES, 'UTF-8');
};

$viewTabs = [
    'usuario' => ['label' => 'Usuário', 'icon' => 'fa-user'],
    'pessoais' => ['label' => 'Dados Pessoais', 'icon' => 'fa-id-card'],
    'endereco' => ['label' => 'Endereço', 'icon' => 'fa-map-marker-alt'],
    'contratuais' => ['label' => 'Dados Contratuais', 'icon' => 'fa-briefcase'],
    'formacoes' => ['label' => 'Formações', 'icon' => 'fa-graduation-cap'],
    'acessos' => ['label' => 'Acessos', 'icon' => 'fa-network-wired'],
    'historico' => ['label' => 'Histórico', 'icon' => 'fa-history'],
    'permissoes' => ['label' => 'Permissões', 'icon' => 'fa-shield-halved'],
];

$editTab = $activeTab === 'historico' ? 'usuario' : $activeTab;
?>

<div class="container-fluid px-2 px-md-4 user-view-page">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Usuários</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm . 'dashboard', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm . 'list-users', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
                <span class="fw-semibold">Visualizar</span>
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                    <?php if (in_array('ListUsers', $perms, true)): ?>
                        <a href="<?= htmlspecialchars($urlAdm . 'list-users', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-info btn-sm" title="Listar"><i class="fa-solid fa-list-ul"></i><span class="d-none d-md-inline"> Listar</span></a>
                    <?php endif; ?>
                    <?php if ($canUpdate && $userId > 0): ?>
                        <a href="<?= htmlspecialchars($urlAdm . 'update-user/' . $userId . '?tab=' . rawurlencode($editTab), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa-regular fa-pen-to-square"></i><span class="d-none d-md-inline"> Editar</span></a>
                    <?php endif; ?>

                    <button class="btn btn-outline-secondary btn-sm d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#userViewMoreBanner" aria-expanded="false" aria-controls="userViewMoreBanner">
                        Mais <i class="fas fa-chevron-down ms-1 small"></i>
                    </button>

                    <div class="d-none d-md-flex flex-wrap gap-1">
                        <?php if (in_array('UpdatePasswordUser', $perms, true) && $userId > 0): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'update-password-user/' . $userId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-key"></i> Editar Senha</a>
                        <?php endif; ?>
                        <?php if (in_array('UpdateUserImage', $perms, true) && $userId > 0): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'update-user-image/' . $userId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-camera"></i> Editar Imagem</a>
                        <?php endif; ?>
                        <?php if (in_array('SstEmployeeProfile', $perms, true) && $userId > 0): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'sst-employee-profile/' . $userId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-heart-pulse"></i> SST</a>
                        <?php endif; ?>
                        <?php
                        $log_resumo = $this->data['log_resumo'] ?? [];
                        $log_btn_class = 'btn btn-outline-info btn-sm';
                        include __DIR__ . '/../partials/button_log_alteracoes.php';
                        ?>
                        <?php if (in_array('DeleteUser', $perms, true) && $userId > 0): ?>
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeletion(event, <?= $userId ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array('DeleteUser', $perms, true) && $userId > 0): ?>
                        <form id="formDelete<?= $userId ?>" action="<?= htmlspecialchars($urlAdm . 'delete-user', ENT_QUOTES, 'UTF-8') ?>" method="POST" class="d-none">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $userId ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="collapse d-md-none" id="userViewMoreBanner">
                <div class="user-view-more-banner mt-2">
                    <?php if (in_array('UpdatePasswordUser', $perms, true) && $userId > 0): ?>
                        <a class="user-view-more-banner-item" href="<?= htmlspecialchars($urlAdm . 'update-password-user/' . $userId, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-key"></i><span>Editar senha</span></a>
                    <?php endif; ?>
                    <?php if (in_array('UpdateUserImage', $perms, true) && $userId > 0): ?>
                        <a class="user-view-more-banner-item" href="<?= htmlspecialchars($urlAdm . 'update-user-image/' . $userId, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-camera"></i><span>Editar imagem</span></a>
                    <?php endif; ?>
                    <?php if (in_array('SstEmployeeProfile', $perms, true) && $userId > 0): ?>
                        <a class="user-view-more-banner-item" href="<?= htmlspecialchars($urlAdm . 'sst-employee-profile/' . $userId, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-heart-pulse"></i><span>SST</span></a>
                    <?php endif; ?>
                    <?php
                    $logResumoMobile = $this->data['log_resumo'] ?? [];
                    if (!empty($logResumoMobile['list_url']) && (int) ($logResumoMobile['count'] ?? 0) > 0):
                    ?>
                        <a class="user-view-more-banner-item" href="<?= htmlspecialchars((string) $logResumoMobile['list_url'], ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-history"></i><span>Log de alterações (<?= (int) $logResumoMobile['count'] ?>)</span></a>
                    <?php endif; ?>
                    <?php if (in_array('DeleteUser', $perms, true) && $userId > 0): ?>
                        <button type="button" class="user-view-more-banner-item user-view-more-banner-item--danger" onclick="confirmDeletion(event, <?= $userId ?>)"><i class="fa-regular fa-trash-can"></i><span>Apagar</span></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-body px-2 px-sm-3">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if ($userRow === null): ?>
                <div class="alert alert-danger" role="alert">Usuário não encontrado.</div>
            <?php else:
                $name = (string) ($userRow['name'] ?? '');
                $image = (string) ($userRow['image'] ?? '');
                $status = (string) ($userRow['status'] ?? '');
                $bloqueado = (string) ($userRow['bloqueado'] ?? '');
                $isSuper = (int) ($userRow['super_usuario'] ?? 0) === 1;
                $statusBadge = strcasecmp($status, 'Ativo') === 0 ? 'bg-success' : 'bg-secondary';
                $blockedBadge = (strcasecmp($bloqueado, 'Sim') === 0 || $bloqueado === '1') ? 'bg-danger' : 'bg-success';
                $blockedLabel = (strcasecmp($bloqueado, 'Sim') === 0 || $bloqueado === '1') ? 'Bloqueado' : 'Não bloqueado';
            ?>

            <div class="d-flex flex-row gap-2 gap-md-3 align-items-center mb-3 pb-3 border-bottom">
                <div class="flex-shrink-0 user-view-avatar">
                    <?php
                    if (ImageHelper::userImageExists($userId, $image)) {
                        echo ImageHelper::displayImage('users/' . $userId . '/' . $image, [
                            'alt' => 'Imagem do usuário',
                            'class' => 'user-view-avatar-img',
                            'style' => '',
                        ], 'icon_user.png', 'users');
                    } else {
                        echo '<span class="d-md-none">' . ImageHelper::renderInitialsAvatar($name !== '' ? $name : 'U', 56, [
                            'style' => 'border-radius: 50%;',
                        ]) . '</span>';
                        echo '<span class="d-none d-md-inline">' . ImageHelper::renderInitialsAvatar($name !== '' ? $name : 'U', 80, [
                            'style' => 'border-radius: 50%;',
                        ]) . '</span>';
                    }
                    ?>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-1 gap-sm-2 mb-1">
                        <h3 class="h5 mb-0 text-break"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($status !== '' ? $status : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="badge <?= $blockedBadge ?>"><?= htmlspecialchars($blockedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($isSuper): ?>
                            <span class="badge bg-primary">Super usuário</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small text-break mb-1">
                        #<?= $userId ?>
                        · <?= htmlspecialchars((string) ($userRow['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="d-none d-sm-inline"> · <?= htmlspecialchars((string) ($userRow['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="small text-break">
                        <span class="me-2"><strong>Depto:</strong> <?= $emptyHtml($userRow['dep_name'] ?? null) ?></span>
                        <span class="me-2"><strong>Cargo:</strong> <?= $emptyHtml(PositionDisplayHelper::formatForDisplay((string) ($userRow['pos_name'] ?? ''))) ?></span>
                        <span class="d-none d-sm-inline me-2"><strong>Empresa:</strong>
                            <?php
                            $empSlug = UserFormHelper::resolveEmpresaSlugFromUser($userRow);
                            echo $empSlug !== null
                                ? htmlspecialchars(UserFormHelper::empresaContratanteLabel($empSlug), ENT_QUOTES, 'UTF-8')
                                : '<span class="text-muted">Não informado</span>';
                            ?>
                        </span>
                        <?php if (!empty($this->data['totalTenure']['formatted'])): ?>
                            <span class="d-none d-md-inline"><strong>Tempo de casa:</strong> <?= htmlspecialchars((string) $this->data['totalTenure']['formatted'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-3 user-view-tabs-scroll">
                <ul class="nav nav-tabs flex-nowrap mb-0" id="userViewTabs" role="tablist">
                    <?php foreach ($viewTabs as $tabKey => $tabMeta): ?>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-nowrap <?= $activeTab === $tabKey ? 'active' : '' ?>" href="<?= $tabUrl($tabKey) ?>">
                                <i class="fas <?= htmlspecialchars($tabMeta['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($tabMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tab-content">
                <?php if ($activeTab === 'usuario'): ?>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Conta', 'fa-user');
                            $row('ID', (string) $userId);
                            $row('Nome', $emptyHtml($userRow['name'] ?? null));
                            $row('E-mail corporativo', $emptyHtml($userRow['email'] ?? null));
                            $row('Usuário', $emptyHtml($userRow['username'] ?? null));
                            $row('CPF', $emptyHtml($userRow['cpf'] ?? null));
                            $row('Celular', $emptyHtml($userRow['celular'] ?? null));
                            $closePanel();
                            ?>
                        </div>
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Organização', 'fa-sitemap');
                            $row('Departamento', $emptyHtml($userRow['dep_name'] ?? null));
                            $row('Cargo / função', $emptyHtml(PositionDisplayHelper::formatForDisplay((string) ($userRow['pos_name'] ?? ''))));
                            $row('Supervisor imediato', $emptyHtml($userRow['supervisor_name'] ?? null, 'Não definido'));
                            $wsLabel = $userRow['work_shift_description'] ?? '';
                            $row('Turno de trabalho', $emptyHtml($wsLabel !== '' ? $wsLabel : null, 'Não definido'));
                            $closePanel();
                            ?>
                        </div>
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Segurança da conta', 'fa-shield-halved');
                            $row('Status', '<span class="badge ' . $statusBadge . '">' . htmlspecialchars($status !== '' ? $status : '—', ENT_QUOTES, 'UTF-8') . '</span>');
                            $row('Bloqueado', '<span class="badge ' . $blockedBadge . '">' . htmlspecialchars($blockedLabel, ENT_QUOTES, 'UTF-8') . '</span>');
                            $row('Super usuário', $isSuper ? '<span class="badge bg-primary">Sim</span>' : $emptyHtml('Não'));
                            $row('Tentativas de login', $emptyHtml($userRow['tentativas_login'] ?? '0'));
                            $row('Senha nunca expira', $emptyHtml($userRow['senha_nunca_expira'] ?? null));
                            $row('Modificar senha no próximo logon', $emptyHtml($userRow['modificar_senha_proximo_logon'] ?? null));
                            $closePanel();
                            ?>
                        </div>
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Auditoria', 'fa-clock');
                            $row('Cadastrado', !empty($userRow['created_at']) ? htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $userRow['created_at'])), ENT_QUOTES, 'UTF-8') : $emptyHtml(null));
                            $row('Editado', !empty($userRow['updated_at']) ? htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $userRow['updated_at'])), ENT_QUOTES, 'UTF-8') : $emptyHtml(null));
                            $imgAction = ImageHelper::userImageExists($userId, $image)
                                ? '<button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDeleteImageView">Remover imagem</button>'
                                : '<span class="text-muted">Sem imagem personalizada</span>';
                            $row('Imagem', $imgAction);
                            $closePanel();
                            ?>
                            <?php if (ImageHelper::userImageExists($userId, $image)): ?>
                                <div class="modal fade" id="modalDeleteImageView" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar remoção</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">Tem certeza que deseja remover a imagem do usuário?</div>
                                            <div class="modal-footer">
                                                <form action="<?= htmlspecialchars($urlAdm . 'delete-user-image/' . $userId, ENT_QUOTES, 'UTF-8') ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token_delete_image, ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-danger">Sim, remover</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($activeTab === 'pessoais'): ?>
                    <?php
                    $openPanel('Dados pessoais', 'fa-id-card');
                    $dataNasc = $userRow['data_nascimento'] ?? null;
                    $row('Data de nascimento', !empty($dataNasc) ? htmlspecialchars(date('d/m/Y', strtotime((string) $dataNasc)), ENT_QUOTES, 'UTF-8') : $emptyHtml(null));
                    $escRaw = $userRow['escolaridade'] ?? null;
                    $row('Escolaridade', ($escRaw !== null && $escRaw !== '')
                        ? htmlspecialchars(UserFormHelper::escolaridadeLabel(is_string($escRaw) ? $escRaw : null), ENT_QUOTES, 'UTF-8')
                        : $emptyHtml(null));
                    $row('Sexo', htmlspecialchars(UserFormHelper::sexoLabel($userRow['sexo'] ?? null), ENT_QUOTES, 'UTF-8'));
                    $row('Filho(s)', htmlspecialchars(UserFormHelper::filhosLabel($userRow['filhos'] ?? null), ENT_QUOTES, 'UTF-8'));
                    $row('Estado civil', htmlspecialchars(UserFormHelper::estadoCivilLabel($userRow['estado_civil'] ?? null), ENT_QUOTES, 'UTF-8'));
                    $racaRaw = $userRow['raca'] ?? null;
                    $row('Raça/cor', ($racaRaw !== null && $racaRaw !== '')
                        ? htmlspecialchars(UserFormHelper::racaLabel(is_string($racaRaw) ? $racaRaw : null), ENT_QUOTES, 'UTF-8')
                        : $emptyHtml(null));
                    $row('E-mail pessoal', $emptyHtml($userRow['email_pessoal'] ?? null));
                    $row('País de residência', htmlspecialchars(UserFormHelper::paisResidenciaLabel($userRow['pais_residencia_iso'] ?? null), ENT_QUOTES, 'UTF-8'));
                    $closePanel();
                    ?>

                <?php elseif ($activeTab === 'endereco'): ?>
                    <?php
                    $openPanel('Endereço', 'fa-map-marker-alt');
                    $row('Logradouro', $emptyHtml($userRow['endereco'] ?? null));
                    $row('Número', $emptyHtml($userRow['numero_endereco'] ?? null));
                    $row('Complemento', $emptyHtml($userRow['complemento_endereco'] ?? null));
                    $row('Bairro', $emptyHtml($userRow['bairro'] ?? null));
                    $row('CEP', $emptyHtml($userRow['cep'] ?? null));
                    $row('Município', $emptyHtml($userRow['municipio'] ?? null));
                    $row('UF', $emptyHtml($userRow['uf'] ?? null));
                    $closePanel();
                    ?>

                <?php elseif ($activeTab === 'contratuais'): ?>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Vínculo', 'fa-briefcase');
                            $empSlug = UserFormHelper::resolveEmpresaSlugFromUser($userRow);
                            $row('Empresa contratante', $empSlug !== null
                                ? htmlspecialchars(UserFormHelper::empresaContratanteLabel($empSlug), ENT_QUOTES, 'UTF-8')
                                : $emptyHtml(null));
                            $row('Matrícula', $emptyHtml($userRow['matricula'] ?? null));
                            $row('Data de admissão', !empty($userRow['data_admissao'])
                                ? htmlspecialchars(date('d/m/Y', strtotime((string) $userRow['data_admissao'])), ENT_QUOTES, 'UTF-8')
                                : $emptyHtml(null));
                            if (!empty($this->data['totalTenure']['formatted'])) {
                                $tenureHtml = '<strong>' . htmlspecialchars((string) $this->data['totalTenure']['formatted'], ENT_QUOTES, 'UTF-8') . '</strong>'
                                    . ' <span class="text-muted small">(' . (int) ($this->data['totalTenure']['total_periodos'] ?? 0) . ' período(s))</span>';
                                $row('Tempo total de casa', $tenureHtml);
                            }
                            $closePanel();
                            ?>
                        </div>
                        <div class="col-lg-6">
                            <?php
                            $openPanel('Desligamento', 'fa-user-slash');
                            $row('Data de desligamento', !empty($userRow['data_desligamento'])
                                ? htmlspecialchars(date('d/m/Y', strtotime((string) $userRow['data_desligamento'])), ENT_QUOTES, 'UTF-8')
                                : $emptyHtml(null, 'Colaborador ativo'));
                            if (!empty($userRow['data_desligamento'])) {
                                $row('Classificação', htmlspecialchars(
                                    UserFormHelper::tipoImpactoDesligamentoLabel($userRow['tipo_impacto_desligamento'] ?? null),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ));
                                $row('Motivo', $emptyHtml($userRow['motivo_desligamento'] ?? null));
                            } else {
                                $row('Situação', '<span class="badge bg-success">Ativo</span>');
                            }
                            $closePanel();
                            ?>
                        </div>
                    </div>

                <?php elseif ($activeTab === 'formacoes'): ?>
                    <div class="user-view-section-head mb-3">
                        <p class="text-muted small mb-0">Formações acadêmicas e cursos cadastrados.</p>
                        <?php if ($canUpdate): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'update-user/' . $userId . '?tab=formacoes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning flex-shrink-0"><i class="fas fa-edit me-1"></i>Gerenciar</a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($this->data['educations'])): ?>
                        <div class="d-none d-md-block table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Curso/Formação</th>
                                        <th>Instituição</th>
                                        <th>Situação</th>
                                        <th>Período</th>
                                        <th>Carga horária</th>
                                        <th>Comprovante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['educations'] as $education): ?>
                                        <?php
                                        $eduStart = !empty($education['data_inicio']) ? date('m/Y', strtotime((string) $education['data_inicio'])) : '';
                                        $eduEnd = !empty($education['data_conclusao']) ? date('m/Y', strtotime((string) $education['data_conclusao'])) : '';
                                        $eduPeriod = trim($eduStart . ($eduStart !== '' && $eduEnd !== '' ? ' a ' : '') . $eduEnd) ?: '—';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars(UserEducationHelper::typeLabel((string) ($education['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars((string) ($education['curso'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php if (!empty($education['observacoes'])): ?>
                                                    <div class="small text-muted"><?= nl2br(htmlspecialchars((string) $education['observacoes'], ENT_QUOTES, 'UTF-8')) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= !empty($education['instituicao']) ? htmlspecialchars((string) $education['instituicao'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                            <td><?= htmlspecialchars(UserEducationHelper::statusLabel((string) ($education['situacao'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($eduPeriod, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= !empty($education['carga_horaria']) ? (int) $education['carga_horaria'] . ' h' : '—' ?></td>
                                            <td>
                                                <?php if (!empty($education['comprovante_path'])): ?>
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($urlAdm . 'view-user/download-formacao/' . (int) $education['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="fas fa-download me-1"></i>Baixar
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Não anexado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-md-none user-view-stack">
                            <?php foreach ($this->data['educations'] as $education): ?>
                                <?php
                                $eduStart = !empty($education['data_inicio']) ? date('m/Y', strtotime((string) $education['data_inicio'])) : '';
                                $eduEnd = !empty($education['data_conclusao']) ? date('m/Y', strtotime((string) $education['data_conclusao'])) : '';
                                $eduPeriod = trim($eduStart . ($eduStart !== '' && $eduEnd !== '' ? ' a ' : '') . $eduEnd) ?: '—';
                                ?>
                                <article class="user-view-list-card">
                                    <div class="user-view-list-card-title">
                                        <?= htmlspecialchars((string) ($education['curso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="user-view-list-card-meta">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(UserEducationHelper::typeLabel((string) ($education['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(UserEducationHelper::statusLabel((string) ($education['situacao'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <dl class="user-view-list-card-dl mb-0">
                                        <div><dt>Instituição</dt><dd><?= !empty($education['instituicao']) ? htmlspecialchars((string) $education['instituicao'], ENT_QUOTES, 'UTF-8') : '—' ?></dd></div>
                                        <div><dt>Período</dt><dd><?= htmlspecialchars($eduPeriod, ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <div><dt>Carga horária</dt><dd><?= !empty($education['carga_horaria']) ? (int) $education['carga_horaria'] . ' h' : '—' ?></dd></div>
                                    </dl>
                                    <?php if (!empty($education['observacoes'])): ?>
                                        <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars((string) $education['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($education['comprovante_path'])): ?>
                                        <a class="btn btn-sm btn-outline-primary w-100" href="<?= htmlspecialchars($urlAdm . 'view-user/download-formacao/' . (int) $education['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-download me-1"></i>Baixar comprovante
                                        </a>
                                    <?php else: ?>
                                        <span class="small text-muted">Comprovante não anexado</span>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma formação cadastrada.</p>
                    <?php endif; ?>

                <?php elseif ($activeTab === 'acessos'): ?>
                    <?php
                    $tiAcessosView = is_array($this->data['ti_acessos'] ?? null) ? $this->data['ti_acessos'] : [];
                    $csrfTiRevokeView = CSRFHelper::generateCSRFToken('form_ti_acesso_revoke');
                    $canRevokeView = in_array('TiAcessosRevoke', $perms, true);
                    ?>
                    <div class="user-view-section-head mb-3">
                        <p class="text-muted small mb-0">Mapa TI de sistemas/equipamentos (não é a ACL de páginas deste Portal).</p>
                        <?php if (in_array('TiAcessosCreate', $perms, true)): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'ti-acessos-create?user_id=' . $userId . '&return=' . rawurlencode($urlAdm . 'view-user/' . $userId . '?tab=acessos'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success flex-shrink-0"><i class="fas fa-plus me-1"></i>Liberar acesso</a>
                        <?php elseif ($canUpdate): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'update-user/' . $userId . '?tab=acessos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning flex-shrink-0"><i class="fa-regular fa-pen-to-square me-1"></i>Gerenciar na edição</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($tiAcessosView === []): ?>
                        <p class="text-muted mb-0">Nenhum acesso registrado neste mapa.</p>
                    <?php else: ?>
                        <div class="d-none d-md-block table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Sistema</th>
                                        <th>Login</th>
                                        <th>Situação</th>
                                        <th>Local</th>
                                        <?php if ($canRevokeView): ?><th></th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tiAcessosView as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($a['sistema_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($a['login_externo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="badge <?= ($a['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars((string) ($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars((string) ($a['sistema_localizacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php if ($canRevokeView): ?>
                                                <td>
                                                    <?php if (($a['status'] ?? '') === 'ativo'): ?>
                                                        <form method="post" action="<?= htmlspecialchars($urlAdm . 'ti-acessos-revoke/' . (int) $a['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                              onsubmit="return confirm('Confirmar que a conta foi inativada neste sistema?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTiRevokeView, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($urlAdm . 'view-user/' . $userId . '?tab=acessos', ENT_QUOTES, 'UTF-8') ?>">
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
                        <div class="d-md-none user-view-stack">
                            <?php foreach ($tiAcessosView as $a): ?>
                                <article class="user-view-list-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="user-view-list-card-title mb-0"><?= htmlspecialchars((string) ($a['sistema_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="badge flex-shrink-0 <?= ($a['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars((string) ($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <dl class="user-view-list-card-dl mb-0">
                                        <div><dt>Login</dt><dd class="text-break"><?= htmlspecialchars((string) ($a['login_externo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <div><dt>Local</dt><dd class="text-break"><?= htmlspecialchars((string) ($a['sistema_localizacao'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    </dl>
                                    <?php if ($canRevokeView && ($a['status'] ?? '') === 'ativo'): ?>
                                        <form method="post" action="<?= htmlspecialchars($urlAdm . 'ti-acessos-revoke/' . (int) $a['id'], ENT_QUOTES, 'UTF-8') ?>"
                                              onsubmit="return confirm('Confirmar que a conta foi inativada neste sistema?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTiRevokeView, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($urlAdm . 'view-user/' . $userId . '?tab=acessos', ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Inativar</button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($activeTab === 'historico'): ?>
                    <?php if (!empty($this->data['employmentHistory'])): ?>
                        <div class="d-none d-md-block table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Admissão</th>
                                        <th>Desligamento</th>
                                        <th>Motivo</th>
                                        <th>Impacto (RH)</th>
                                        <th>Duração</th>
                                        <th>Observações</th>
                                        <?php if ($canUpdate): ?><th class="text-center">Ações</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['employmentHistory'] as $period): ?>
                                        <tr class="<?= empty($period['data_desligamento']) ? 'table-success' : '' ?>">
                                            <td>
                                                <span class="badge bg-<?= ($period['tipo_periodo'] ?? '') === 'Recontratação' ? 'info' : 'primary' ?>">
                                                    <?= htmlspecialchars((string) ($period['tipo_periodo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime((string) $period['data_admissao'])) ?></td>
                                            <td>
                                                <?php if (!empty($period['data_desligamento'])): ?>
                                                    <?= date('d/m/Y', strtotime((string) $period['data_desligamento'])) ?>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= !empty($period['motivo_desligamento']) ? htmlspecialchars((string) $period['motivo_desligamento'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                            <td>
                                                <?php
                                                $ti = $period['tipo_impacto_desligamento'] ?? null;
                                                echo $ti ? htmlspecialchars(UserFormHelper::tipoImpactoDesligamentoLabel((string) $ti), ENT_QUOTES, 'UTF-8') : '—';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $admissao = new DateTime((string) $period['data_admissao']);
                                                $desligamento = !empty($period['data_desligamento'])
                                                    ? new DateTime((string) $period['data_desligamento'])
                                                    : new DateTime();
                                                $diff = $admissao->diff($desligamento);
                                                echo $diff->y > 0
                                                    ? "{$diff->y} ano(s), {$diff->m} mês(es)"
                                                    : "{$diff->m} mês(es), {$diff->d} dia(s)";
                                                ?>
                                            </td>
                                            <td><small class="text-muted"><?= !empty($period['observacoes']) ? htmlspecialchars((string) $period['observacoes'], ENT_QUOTES, 'UTF-8') : '—' ?></small></td>
                                            <?php if ($canUpdate): ?>
                                                <td class="text-center">
                                                    <a href="<?= htmlspecialchars($urlAdm . 'update-employment-history/' . (int) $period['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-md-none user-view-stack">
                            <?php foreach ($this->data['employmentHistory'] as $period): ?>
                                <?php
                                $admissao = new DateTime((string) $period['data_admissao']);
                                $desligamento = !empty($period['data_desligamento'])
                                    ? new DateTime((string) $period['data_desligamento'])
                                    : new DateTime();
                                $diff = $admissao->diff($desligamento);
                                $duracao = $diff->y > 0
                                    ? "{$diff->y} ano(s), {$diff->m} mês(es)"
                                    : "{$diff->m} mês(es), {$diff->d} dia(s)";
                                $ti = $period['tipo_impacto_desligamento'] ?? null;
                                $isAtivoPeriodo = empty($period['data_desligamento']);
                                ?>
                                <article class="user-view-list-card<?= $isAtivoPeriodo ? ' user-view-list-card--active' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <span class="badge bg-<?= ($period['tipo_periodo'] ?? '') === 'Recontratação' ? 'info' : 'primary' ?>">
                                            <?= htmlspecialchars((string) ($period['tipo_periodo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if ($isAtivoPeriodo): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php endif; ?>
                                    </div>
                                    <dl class="user-view-list-card-dl mb-0">
                                        <div><dt>Admissão</dt><dd><?= date('d/m/Y', strtotime((string) $period['data_admissao'])) ?></dd></div>
                                        <div><dt>Desligamento</dt><dd><?= $isAtivoPeriodo ? '—' : date('d/m/Y', strtotime((string) $period['data_desligamento'])) ?></dd></div>
                                        <div><dt>Duração</dt><dd><?= htmlspecialchars($duracao, ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <div><dt>Motivo</dt><dd class="text-break"><?= !empty($period['motivo_desligamento']) ? htmlspecialchars((string) $period['motivo_desligamento'], ENT_QUOTES, 'UTF-8') : '—' ?></dd></div>
                                        <div><dt>Impacto (RH)</dt><dd><?= $ti ? htmlspecialchars(UserFormHelper::tipoImpactoDesligamentoLabel((string) $ti), ENT_QUOTES, 'UTF-8') : '—' ?></dd></div>
                                        <?php if (!empty($period['observacoes'])): ?>
                                            <div><dt>Observações</dt><dd class="text-break"><?= htmlspecialchars((string) $period['observacoes'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                        <?php endif; ?>
                                    </dl>
                                    <?php if ($canUpdate): ?>
                                        <a href="<?= htmlspecialchars($urlAdm . 'update-employment-history/' . (int) $period['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-warning w-100 mt-2"><i class="fas fa-edit me-1"></i>Editar período</a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhum histórico de admissão/desligamento registrado.</p>
                    <?php endif; ?>

                <?php elseif ($activeTab === 'permissoes'): ?>
                    <?php $viewUserIsSuper = (int) ($userRow['super_usuario'] ?? 0) === 1; ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h5 class="h6 mb-1">Níveis de acesso (ACL do Portal)</h5>
                            <p class="text-muted small mb-0">Somente leitura. Para alterar, use <strong>Editar</strong> → aba Permissões.</p>
                        </div>
                        <?php if ($canPerms && $userId > 0 && !$viewUserIsSuper): ?>
                            <a href="<?= htmlspecialchars($urlAdm . 'update-user/' . $userId . '?tab=permissoes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning flex-shrink-0">
                                <i class="fa-regular fa-pen-to-square me-1"></i>Editar permissões
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($viewUserIsSuper): ?>
                        <div class="alert alert-info mb-0" role="alert">
                            <strong>Super usuário:</strong> acesso total ao sistema (equivalente ao nível Super Administrador).
                            Os níveis de acesso por pacote não se aplicam enquanto o flag estiver ativo.
                        </div>
                    <?php else:
                        $assignedLevelNames = [];
                        $userAccessLevelIds = array_map(
                            'intval',
                            is_array($this->data['userAccessLevelsArray'] ?? null) ? $this->data['userAccessLevelsArray'] : []
                        );
                        $allLevelsForNames = is_array($this->data['userAllAccessLevelsArray'] ?? null)
                            ? $this->data['userAllAccessLevelsArray']
                            : [];
                        foreach ($allLevelsForNames as $levelRow) {
                            $lid = (int) ($levelRow['id'] ?? 0);
                            if ($lid > 0 && in_array($lid, $userAccessLevelIds, true)) {
                                $assignedLevelNames[] = (string) ($levelRow['name'] ?? '');
                            }
                        }
                        if ($assignedLevelNames === [] && is_array($this->data['userAccessLevels'] ?? null)) {
                            foreach ($this->data['userAccessLevels'] as $levelRow) {
                                $n = trim((string) ($levelRow['name'] ?? ''));
                                if ($n !== '') {
                                    $assignedLevelNames[] = $n;
                                }
                            }
                        }
                        sort($assignedLevelNames, SORT_STRING | SORT_FLAG_CASE);
                    ?>
                        <?php if ($assignedLevelNames === []): ?>
                            <p class="text-muted mb-0">Nenhum nível de acesso atribuído a este usuário.</p>
                        <?php else: ?>
                            <div class="user-view-stack">
                                <?php foreach ($assignedLevelNames as $levelName): ?>
                                    <article class="user-view-list-card user-view-list-card--active">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-shield-halved text-success"></i>
                                            <div class="user-view-list-card-title mb-0"><?= htmlspecialchars($levelName, ENT_QUOTES, 'UTF-8') ?></div>
                                            <span class="badge bg-success ms-auto">Atribuído</span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>public/adms/js/user-form-tabs.js?v=20260731c"></script>
