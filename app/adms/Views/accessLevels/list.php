<?php

use App\adms\Helpers\CSRFHelper;

// Gera tokens CSRF para proteger os formulários
$csrf_token_delete = CSRFHelper::generateCSRFToken('form_delete_access_level');
$csrf_token_copy   = CSRFHelper::generateCSRFToken('form_copy_access_level_permissions');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Níveis de Acesso</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Níveis de Acesso</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">

                <?php
                if (in_array('CreateAccessLevel', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-access-level' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }

                if (in_array('AccessLevelPageSync', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}access-level-page-sync' class='btn btn-warning btn-sm' onclick='showLoading()'><i class='fa-solid fa-rotate'></i> Sincronizar</a> ";
                }
                ?>
                <!-- <a href="<?= $_ENV['URL_ADM']; ?>import-access-levels/template" class="btn btn-outline-secondary btn-sm me-1 mb-1"><i class="fa-solid fa-download"></i> Baixar Template</a> -->
                <a href="<?= $_ENV['URL_ADM']; ?>import-access-levels" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-solid fa-file-import"></i> Importar</a>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php'; ?>

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="name" class="form-label mb-1">Nome</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" class="form-control form-control-sm" placeholder="Buscar por nome...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label for="per_page" class="form-label mb-1 me-2">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm w-auto mx-1" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-label mb-1 ms-1">registros</span>
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="list-access-levels" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar filtro</a>
                </div>
            </form>

            <!-- Copiar permissões entre níveis -->
            <div class="border rounded p-3 mb-3 bg-light">
                <form id="formCopyPermissions" method="post" action="<?= $_ENV['URL_ADM']; ?>list-access-levels-permissions/0" class="row g-2 align-items-end">
                    <div class="col-12 mb-1">
                        <strong>Copiar permissões entre níveis</strong>
                    </div>
                    <div class="col-md-4">
                        <label for="source_access_level_id" class="form-label mb-1">Copiar de</label>
                        <select name="source_access_level_id" id="source_access_level_id" class="form-select form-select-sm">
                            <option value="">Selecione o nível origem...</option>
                            <?php foreach ($this->data['accessLevels'] as $level): ?>
                                <?php if ((int)$level['id'] === 1) { continue; } // evita Super Admin como origem ?>
                                <option value="<?= (int)$level['id']; ?>">
                                    <?= (int)$level['id']; ?> - <?= htmlspecialchars($level['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="target_access_level_id" class="form-label mb-1">Copiar para</label>
                        <select name="target_access_level_id" id="target_access_level_id" class="form-select form-select-sm">
                            <option value="">Selecione o nível destino...</option>
                            <?php foreach ($this->data['accessLevels'] as $level): ?>
                                <?php if ((int)$level['id'] === 1) { continue; } // evita Super Admin como destino ?>
                                <option value="<?= (int)$level['id']; ?>">
                                    <?= (int)$level['id']; ?> - <?= htmlspecialchars($level['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2 align-items-end">
                        <input type="hidden" name="mode" value="copy_permissions">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token_copy; ?>">
                        <button type="submit" class="btn btn-outline-primary btn-sm"
                                onclick="return confirm('Tem certeza que deseja substituir TODAS as permissões do nível destino pelas permissões do nível origem selecionado?');">
                            <i class="fa-solid fa-copy"></i> Copiar permissões
                        </button>
                    </div>
                </form>
            </div>

            <?php
            // Verifica se há níveis de acesso no array
            if ($this->data['accessLevels'] ?? false) {
            ?>

                <!-- Tabela Desktop -->
                <div class="table-responsive d-none d-md-block list-desktop">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de níveis de acesso
                            foreach ($this->data['accessLevels'] as $accessLevel) {

                                // Extrai variáveis do array de níveis de acesso
                                extract($accessLevel); ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php
                                        $paAuth = (int) ($permissions_authorized_count ?? 0);
                                        $paTot = (int) ($permissions_pages_total ?? 0);
                                        ?>
                                        <span class="text-muted small ms-1" title="Páginas autorizadas / total de páginas vinculadas a este nível">(<?php echo $paAuth; ?>/<?php echo $paTot; ?>)</span>
                                    </td>
                                    <td class="text-center">

                                        <?php
                                        if (in_array('ListAccessLevelsPermissions', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}list-access-levels-permissions/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-lock-open'></i> Permissões</a>";
                                        }

                                        if (in_array('ViewAccessLevel', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-access-level/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('UpdateAccessLevel', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-access-level/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeleteAccessLevel', $this->data['buttonPermission'])) {
                                        ?>

                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-access-level" method="POST" class="d-inline">

                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_delete; ?>">

                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">

                                                <input type="hidden" name="name" id="name" value="<?php echo $name ?? ''; ?>">

                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                                            </form>
                                        <?php } ?>

                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>
                    </table>
                </div>

                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none list-mobile">
                    <?php foreach ($this->data['accessLevels'] as $i => $accessLevel) { extract($accessLevel); ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php $paAuthM = (int) ($permissions_authorized_count ?? 0); $paTotM = (int) ($permissions_pages_total ?? 0); ?>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?></b> <span class="text-muted small" title="Páginas autorizadas / total vinculadas">(<?= $paAuthM ?>/<?= $paTotM ?>)</span></h5>
                                        <div class="mb-1"><b>ID:</b> <?= $id ?></div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <?php
                                    if (in_array('ListAccessLevelsPermissions', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}list-access-levels-permissions/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-lock-open'></i> Permissões</a>";
                                    }

                                    if (in_array('ViewAccessLevel', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}view-access-level/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                    }

                                    if (in_array('UpdateAccessLevel', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}update-access-level/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }

                                    if (in_array('DeleteAccessLevel', $this->data['buttonPermission'])) {
                                    ?>
                                        <form id="formDeleteMobile<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-access-level" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token_delete; ?>">
                                            <input type="hidden" name="id" id="id" value="<?= $id ?? ''; ?>">
                                            <input type="hidden" name="name" id="name" value="<?= $name ?? ''; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                        </form>
                                    <?php } ?>
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
                                Exibindo <?= count($this->data['accessLevels']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?php
                            $paginationHtml = $this->data['pagination']['html'] ?? '';
                            if ($paginationHtml) {
                                $paginationHtml = str_replace(
                                    ['>Primeiro<','>Anterior<','>Próximo<','>Último<'],
                                    ['>&laquo;<','>&lsaquo;<','>&rsaquo;<','>&raquo;<'],
                                    $paginationHtml
                                );
                                $paginationHtml = preg_replace('/class=\"pagination(.*?)\"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                                echo $paginationHtml;
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Paginação Desktop -->
                <div class="d-none d-md-flex justify-content-between align-items-center mt-2">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['accessLevels']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum nível de acesso for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum nível de acesso encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script type="text/javascript">
    // DataTables removido para padronização do sistema

    // Ajusta a action do formulário de cópia para incluir o ID do nível destino na URL
    (function() {
        const formCopy = document.getElementById('formCopyPermissions');
        if (!formCopy) return;

        formCopy.addEventListener('submit', function (e) {
            const targetSelect = document.getElementById('target_access_level_id');
            if (!targetSelect || !targetSelect.value) {
                e.preventDefault();
                alert('Selecione o nível de acesso DESTINO antes de copiar as permissões.');
                return false;
            }

            const baseUrl = '<?= $_ENV['URL_ADM']; ?>';
            const targetId = targetSelect.value;
            this.action = baseUrl + 'list-access-levels-permissions/' + encodeURIComponent(targetId);
        });
    })();
</script>