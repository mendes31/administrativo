<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_user');

$renderListUserAvatar = static function (int $userId, ?string $imageName, string $userName, int $sizePx): string {
    if (ImageHelper::userImageExists($userId, $imageName)) {
        return ImageHelper::displayImage(
            'users/' . $userId . '/' . (string) $imageName,
            [
                'alt' => 'Foto de ' . $userName,
                'class' => 'users-list-avatar rounded-circle flex-shrink-0',
                'style' => 'width:' . $sizePx . 'px;height:' . $sizePx . 'px;object-fit:cover;',
            ],
            'icon_user.png',
            'users'
        );
    }

    return ImageHelper::renderInitialsAvatar($userName, $sizePx, [
        'class' => 'users-list-avatar flex-shrink-0',
    ]);
};

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Usuários</h2>

        <ol class="breadcrumb  mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Usuários</li>
        </ol>

    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>
                Listar
            </span>

            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php
                // Montar querystring atual para reaproveitar filtros na exportação
                $qsExport = '';
                if (!empty($this->data['filtros'] ?? [])) {
                    $qsExport = http_build_query($this->data['filtros']);
                    if ($qsExport) {
                        $qsExport = '?' . $qsExport;
                    }
                }

                if (in_array('CreateUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-user' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                if (in_array('ImportUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}import-users' class='btn btn-primary btn-sm'><i class='fa-solid fa-file-import'></i> Importar</a> ";
                }
                echo "<a href='{$_ENV['URL_ADM']}export-users-excel{$qsExport}' class='btn btn-outline-success btn-sm'><i class='fa-solid fa-file-excel'></i> Excel</a> ";
                echo "<a href='{$_ENV['URL_ADM']}export-users-pdf{$qsExport}' class='btn btn-outline-danger btn-sm'><i class='fa-solid fa-file-pdf'></i> PDF</a> ";
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>
            <div class="d-md-none mb-2">
                <button
                    class="btn btn-outline-primary btn-sm"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#usersListFiltersCollapse"
                    aria-expanded="false"
                    aria-controls="usersListFiltersCollapse"
                >
                    <i class="fa fa-filter me-1"></i> Abrir filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="usersListFiltersCollapse">
                <form method="get" class="row g-2 mb-2 align-items-end users-list-filters">
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="nome" class="form-label users-list-filters-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control users-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['nome'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="usuario" class="form-label users-list-filters-label">Usuário</label>
                        <input type="text" name="usuario" id="usuario" class="form-control users-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['usuario'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="departamento_id" class="form-label users-list-filters-label">Departamento</label>
                        <select name="departamento_id" id="departamento_id" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['departments'] ?? [] as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($this->data['filtros']['departamento_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="cargo_id" class="form-label users-list-filters-label">Cargo</label>
                        <select name="cargo_id" id="cargo_id" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['positions'] ?? [] as $pos): ?>
                                <option value="<?= $pos['id'] ?>" <?= ($this->data['filtros']['cargo_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="turno_id" class="form-label users-list-filters-label">Turno</label>
                        <select name="turno_id" id="turno_id" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="0" <?= ($this->data['filtros']['turno_id'] ?? '') !== '' && (string) ($this->data['filtros']['turno_id'] ?? '') === '0' ? 'selected' : '' ?>>Sem turno</option>
                            <?php foreach ($this->data['work_shifts'] ?? [] as $ws): ?>
                                <option value="<?= (int) $ws['id'] ?>" <?= ($this->data['filtros']['turno_id'] ?? '') == $ws['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ws['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="status" class="form-label users-list-filters-label">Status</label>
                        <select name="status" id="status" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="Ativo" <?= ($this->data['filtros']['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($this->data['filtros']['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="bloqueado" class="form-label users-list-filters-label">Bloqueado</label>
                        <select name="bloqueado" id="bloqueado" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="1" <?= ($this->data['filtros']['bloqueado'] ?? '') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($this->data['filtros']['bloqueado'] ?? '') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="desligado" class="form-label users-list-filters-label">Desligado</label>
                        <select name="desligado" id="desligado" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="1" <?= ($this->data['filtros']['desligado'] ?? '') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($this->data['filtros']['desligado'] ?? '') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="sexo" class="form-label users-list-filters-label">Sexo</label>
                        <select name="sexo" id="sexo" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="M" <?= ($this->data['filtros']['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($this->data['filtros']['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="O" <?= ($this->data['filtros']['sexo'] ?? '') === 'O' ? 'selected' : '' ?>>Outros</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="filhos" class="form-label users-list-filters-label">Filho(s)</label>
                        <select name="filhos" id="filhos" class="form-select users-list-filters-control">
                            <option value="">Todos</option>
                            <option value="S" <?= ($this->data['filtros']['filhos'] ?? '') === 'S' ? 'selected' : '' ?>>Sim</option>
                            <option value="N" <?= ($this->data['filtros']['filhos'] ?? '') === 'N' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                        <label for="periodo_tipo" class="form-label users-list-filters-label">Selecionar</label>
                        <select name="periodo_tipo" id="periodo_tipo" class="form-select users-list-filters-control">
                            <option value="">-- Selecionar --</option>
                            <option value="admissao" <?= ($this->data['filtros']['periodo_tipo'] ?? '') == 'admissao' ? 'selected' : '' ?>>Admissão</option>
                            <option value="desligamento" <?= ($this->data['filtros']['periodo_tipo'] ?? '') == 'desligamento' ? 'selected' : '' ?>>Desligamento</option>
                            <option value="atualizacao_cargos" <?= ($this->data['filtros']['periodo_tipo'] ?? '') == 'atualizacao_cargos' ? 'selected' : '' ?>>Atualização de Cargos</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="data_de" class="form-label users-list-filters-label">Data de</label>
                        <input type="date" name="data_de" id="data_de" class="form-control users-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['data_de'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="data_ate" class="form-label users-list-filters-label">Data até</label>
                        <input type="date" name="data_ate" id="data_ate" class="form-control users-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['data_ate'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                        <label for="per_page" class="form-label users-list-filters-label">Mostrar</label>
                        <div class="d-flex align-items-center">
                            <select name="per_page" id="per_page" class="form-select users-list-filters-control me-2" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-label mb-0 users-list-filters-hint">registros</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-auto d-flex gap-2 flex-wrap align-items-end users-list-filters-actions">
                        <button type="submit" class="btn btn-primary btn-sm users-list-filters-btn"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm users-list-filters-btn"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>
            </div>
            <?php
            // Verifica se há usuários no array
            if ($this->data['users'] ?? false) {
            ?>
                <!-- Tabela Desktop -->
                <div class="table-responsive d-none d-md-block list-desktop">
                    <table class="table table-striped table-hover table-users-desktop table-users-desktop-header">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 4%;">ID</th>
                                <th scope="col" style="width: 22%;">Nome</th>
                                <th scope="col" style="width: 16%;" class="d-none d-md-table-cell">Departamento</th>
                                <th scope="col" style="width: 13%;" class="d-none d-md-table-cell">Cargo</th>
                                <th scope="col" style="width: 7%;" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" style="width: 7%;" class="d-none d-md-table-cell">Bloqueado</th>
                                <th scope="col" style="width: 8%;" class="d-none d-md-table-cell">Desligado</th>
                                <th scope="col" class="text-center text-nowrap table-users-desktop-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['users'] as $user) { extract($user); 
                                $dataDesligamento = $data_desligamento ?? '';
                                $isDesligado = !empty($dataDesligamento);
                                $desligadoClass = $isDesligado ? 'table-danger' : '';
                                $canViewUser = in_array('ViewUser', $this->data['buttonPermission']);
                            ?>
                                <tr class="<?= $desligadoClass ?>">
                                    <th class="text-center"><?= $id; ?></th>
                                    <td title="<?= htmlspecialchars($name); ?>">
                                        <div class="d-flex align-items-center gap-2 users-list-name-wrap">
                                            <?php if ($canViewUser): ?>
                                                <a
                                                    href="<?= $_ENV['URL_ADM']; ?>view-user/<?= $id; ?>"
                                                    class="users-list-avatar-link"
                                                    title="Visualizar perfil de <?= htmlspecialchars((string)($name ?? 'Usuário')); ?>"
                                                    aria-label="Visualizar perfil de <?= htmlspecialchars((string)($name ?? 'Usuário')); ?>"
                                                >
                                                    <?= $renderListUserAvatar((int)($id ?? 0), (string)($image ?? ''), (string)($name ?? 'Usuário'), 28); ?>
                                                </a>
                                            <?php else: ?>
                                                <?= $renderListUserAvatar((int)($id ?? 0), (string)($image ?? ''), (string)($name ?? 'Usuário'), 28); ?>
                                            <?php endif; ?>
                                            <span class="text-truncate d-inline-block users-list-name-text">
                                                <?= $name; ?>
                                            </span>
                                            <?php if ($isDesligado): ?>
                                                <i class="fas fa-user-slash text-danger ms-1" title="Desligado em <?= date('d/m/Y', strtotime($dataDesligamento)) ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell text-truncate" title="<?= htmlspecialchars($name_dep); ?>"><?= $name_dep ?></td>
                                    <td class="d-none d-md-table-cell text-break users-list-cargo-full" title="<?= htmlspecialchars((string)($name_pos ?? '')); ?>"><?= htmlspecialchars((string)($name_pos ?? '')) ?></td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <span class="badge <?= $status === 'Ativo' ? 'bg-success' : 'bg-danger'; ?>"><?= $status ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <span class="badge <?= $bloqueado === 'Sim' ? 'bg-danger' : 'bg-success'; ?>"><?= $bloqueado ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php if ($isDesligado): ?>
                                            <span class="badge bg-danger" title="Desligado em <?= date('d/m/Y', strtotime($dataDesligamento)) ?>">
                                                <i class="fas fa-user-slash me-1"></i>Sim
                                            </span>
                                            <br><small class="text-muted"><?= date('d/m/Y', strtotime($dataDesligamento)) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-user-check me-1"></i>Não
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center table-users-desktop-actions">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php
                                            if (in_array('ViewUser', $this->data['buttonPermission'])) {
                                                echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-info btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i></a>";
                                            }
                                            if (in_array('UpdateUser', $this->data['buttonPermission'])) {
                                                echo "<a href='{$_ENV['URL_ADM']}update-user/$id' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
                                            }
                                            if (in_array('DeleteUser', $this->data['buttonPermission'])) {
                                            ?>
                                                <form id="formDelete<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-user" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $id ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title='Apagar' onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i></button>
                                                </form>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none list-mobile">
                    <?php foreach ($this->data['users'] as $i => $user) { extract($user); 
                        $dataDesligamento = $data_desligamento ?? '';
                        $isDesligado = !empty($dataDesligamento);
                        $canViewUser = in_array('ViewUser', $this->data['buttonPermission']);
                    ?>
                        <div class="card mb-3 shadow-sm <?= $isDesligado ? 'border-danger' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start users-list-mobile-header">
                                    <div class="users-list-mobile-info">
                                        <h5 class="card-title mb-1">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <?php if ($canViewUser): ?>
                                                    <a
                                                        href="<?= $_ENV['URL_ADM']; ?>view-user/<?= $id; ?>"
                                                        class="users-list-avatar-link"
                                                        title="Visualizar perfil de <?= htmlspecialchars((string)($name ?? 'Usuário')); ?>"
                                                        aria-label="Visualizar perfil de <?= htmlspecialchars((string)($name ?? 'Usuário')); ?>"
                                                    >
                                                        <?= $renderListUserAvatar((int)($id ?? 0), (string)($image ?? ''), (string)($name ?? 'Usuário'), 36); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= $renderListUserAvatar((int)($id ?? 0), (string)($image ?? ''), (string)($name ?? 'Usuário'), 36); ?>
                                                <?php endif; ?>
                                                <b><?= $name ?></b>
                                            </span>
                                            <?php if ($isDesligado): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="fas fa-user-slash me-1"></i>Desligado
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mb-1"><b>Status:</b> <?= $status ?></div>
                                        <div class="mb-1"><b>Bloqueado:</b> <?= $bloqueado ?></div>
                                        <div class="mb-1"><b>Departamento:</b> <?= htmlspecialchars((string)($name_dep ?? '—')) ?></div>
                                        <?php if ($isDesligado): ?>
                                            <div class="mb-1"><b>Data de Desligamento:</b> <span class="text-danger"><?= date('d/m/Y', strtotime($dataDesligamento)) ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2 users-list-mobile-more-btn" type="button" data-bs-toggle="collapse" data-bs-target="#cardUserDetails<?= $i ?>" aria-expanded="false" aria-controls="cardUserDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardUserDetails<?= $i ?>">
                                    <div><b>ID:</b> <?= $id ?></div>
                                    <div><b>Usuário:</b> <?= $username ?></div>
                                    <div><b>E-mail:</b> <?= $email ?></div>
                                    <div><b>Departamento:</b> <?= $name_dep ?></div>
                                    <div class="text-break"><b>Cargo:</b> <?= htmlspecialchars((string)($name_pos ?? '')) ?></div>
                                    <div class="mt-2">
                                        <?php
                                        if (in_array('ViewUser', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                                        }
                                        if (in_array('UpdateUser', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-user/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
                                        }
                                        if (in_array('DeleteUser', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDeleteMobile<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-user" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                                <input type="hidden" name="id" id="id" value="<?= $id ?? ''; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <!-- Paginação e informações abaixo dos cards no mobile -->
                    <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                        <div class="text-secondary small w-100 text-center mb-1">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php else: ?>
                                Exibindo <?= count($this->data['users']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?php
                            $paginationHtml = $this->data['pagination']['html'] ?? '';
                            if ($paginationHtml) {
                                // Compactar para mobile: ícones e tamanho pequeno
                                $paginationHtml = str_replace(
                                    ['>Primeiro<','>Anterior<','>Próximo<','>Último<'],
                                    ['>&laquo;<','>&lsaquo;<','>&rsaquo;<','>&raquo;<'],
                                    $paginationHtml
                                );
                                // Acrescentar classe pagination-sm
                                $paginationHtml = preg_replace('/class=\"pagination(.*?)\"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                                echo $paginationHtml;
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <!-- Paginação Desktop -->
                <div class="w-100 mt-2 d-none d-md-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['users']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>
            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhum usuário encontrado.</div>";
            } ?>

        </div>

    </div>
</div>

<style>
/* Filtros compactos (listagem de usuários) */
.users-list-filters .users-list-filters-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--bs-secondary-color, #6c757d);
    margin-bottom: 0.125rem;
    line-height: 1.2;
}

.users-list-filters .users-list-filters-control {
    font-size: 0.75rem;
    line-height: 1.25;
    padding: 0.15rem 0.4rem;
    min-height: calc(1.25em + 0.3rem + 2px);
}

.users-list-filters .users-list-filters-hint {
    font-size: 0.6875rem;
    color: var(--bs-secondary-color, #6c757d);
}

.users-list-filters .users-list-filters-btn {
    font-size: 0.75rem;
    padding: 0.2rem 0.55rem;
}

.users-list-filters.row > div {
    display: flex;
    flex-direction: column;
}

.users-list-filters.row > .users-list-filters-actions {
    flex-direction: row;
    justify-content: flex-start;
    align-items: flex-end;
}

@media (min-width: 768px) {
    .users-list-filters.row > .users-list-filters-actions {
        margin-left: auto;
        justify-content: flex-end;
    }
}

@media (max-width: 767.98px) {
    .users-list-filters.row > div {
        margin-bottom: 0.35rem;
    }
}

/* Cabeçalho da tabela (mesmo padrão visual da listagem de informativos) */
.table-users-desktop-header > thead > tr > th {
    background: linear-gradient(135deg, #2E9263 0%, #2C844B 55%, #236D3D 100%) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    font-weight: 600;
    font-size: 0.8125rem;
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
}

/* Otimizações específicas para a tabela de usuários no desktop */
.table-users-desktop {
    font-size: 0.9rem;
    table-layout: fixed;
}

.table-users-desktop tbody th,
.table-users-desktop tbody td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

.table-users-desktop .text-truncate {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.users-list-name-wrap {
    min-width: 0;
}

.users-list-name-text {
    max-width: calc(100% - 44px);
}

.users-list-avatar {
    display: inline-flex;
}

.users-list-avatar-link {
    display: inline-flex;
    border-radius: 999px;
    text-decoration: none;
}

.users-list-mobile-header {
    gap: 0.5rem;
}

.users-list-mobile-info {
    min-width: 0;
    flex: 1 1 auto;
}

.users-list-mobile-more-btn {
    flex: 0 0 auto;
    min-width: 72px;
}

/* Otimizar botões de ações */
.btn-group .btn {
    width: 32px;
    height: 32px;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}

/* Cargo na listagem: texto integral do cadastro (quebra em várias linhas) */
.users-list-cargo-full {
    white-space: normal;
    min-width: 10rem;
}

/* Scroll horizontal se ainda faltar espaço (ex.: textos longos em Nome/Cargo) */
.list-desktop.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Ações: coluna fixa à direita (cabeçalho mantém o verde; corpo com fundo da linha) */
.table-users-desktop thead th.table-users-desktop-actions {
    width: 1%;
    min-width: 108px;
    white-space: nowrap;
    position: sticky;
    right: 0;
    z-index: 3;
    box-shadow: -6px 0 10px -6px rgba(0, 0, 0, 0.2);
}

.table-users-desktop tbody td.table-users-desktop-actions {
    width: 1%;
    min-width: 108px;
    white-space: nowrap;
    position: sticky;
    right: 0;
    z-index: 2;
    background-color: var(--bs-body-bg, #fff);
    box-shadow: -6px 0 8px -6px rgba(0, 0, 0, 0.12);
}

.table-users-desktop.table-striped > tbody > tr:nth-of-type(odd) > td.table-users-desktop-actions {
    background-color: var(--bs-table-striped-bg, rgba(0, 0, 0, 0.05));
}

.table-users-desktop.table-hover > tbody > tr:hover > td.table-users-desktop-actions {
    background-color: var(--bs-table-hover-bg, rgba(0, 0, 0, 0.075));
}

.table-users-desktop tbody tr.table-danger > td.table-users-desktop-actions {
    background-color: var(--bs-danger-bg-subtle, #f8d7da);
}

.table-users-desktop.table-hover > tbody > tr.table-danger:hover > td.table-users-desktop-actions {
    background-color: #f1c2cb;
}

/* Responsividade para telas médias */
@media (min-width: 768px) and (max-width: 1199px) {
    .table-users-desktop {
        font-size: 0.85rem;
    }
    
    .table-users-desktop tbody th,
    .table-users-desktop tbody td {
        padding: 0.375rem 0.125rem;
    }
    
    .btn-group .btn {
        width: 28px;
        height: 28px;
    }
}

/* Para telas muito grandes, aumentar um pouco o espaçamento no corpo da tabela */
@media (min-width: 1200px) {
    .table-users-desktop tbody th,
    .table-users-desktop tbody td {
        padding: 0.625rem 0.375rem;
    }
}
</style>