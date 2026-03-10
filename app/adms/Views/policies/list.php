<?php
use App\adms\Helpers\FormatHelper;
?>

<style>
    /* Layout semelhante ao de Informativos, ajustado para Políticas Internas */
    .table-policies {
        width: 100%;
        table-layout: fixed;
    }

    .table-policies th,
    .table-policies td {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    .table-policies th.col-id,
    .table-policies td.col-id {
        width: 60px;
        text-align: center;
    }

    .table-policies th.col-titulo,
    .table-policies td.col-titulo {
        width: 260px;
    }

    .table-policies th.col-categoria,
    .table-policies td.col-categoria {
        width: 150px;
    }

    .table-policies th.col-departamento,
    .table-policies td.col-departamento {
        width: 170px;
    }

    .table-policies th.col-urgente,
    .table-policies td.col-urgente,
    .table-policies th.col-ativo,
    .table-policies td.col-ativo {
        width: 90px;
        text-align: center;
    }

    .table-policies th.col-data,
    .table-policies td.col-data {
        width: 140px;
        text-align: center;
    }

    .table-policies th.col-acoes,
    .table-policies td.col-acoes {
        width: 130px;
        text-align: center;
    }

    .table-policies .badge {
        white-space: normal;
        word-wrap: break-word;
    }
</style>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Políticas Internas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Políticas Internas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-file-contract me-2"></i>Listar Políticas Internas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php
                $isSuperAdmin = isset($_SESSION['user_access_level_id']) && (int)$_SESSION['user_access_level_id'] === 1;
                if ($isSuperAdmin || in_array('CreatePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-policy" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
            <?php if (!empty($this->data['isEditor'])): ?>
            <form method="get" action="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="busca" class="form-label">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control"
                           value="<?php echo htmlspecialchars($this->data['filters']['busca'] ?? ''); ?>"
                           placeholder="Título, conteúdo ou resumo">
                </div>

                <div class="col-md-3">
                    <label for="categoria_id" class="form-label">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($this->data['categorias'] ?? [] as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>"
                                <?php echo (($this->data['filters']['categoria_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="department_id" class="form-label">Departamento Responsável</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['departments'] ?? [] as $dep): ?>
                            <option value="<?php echo (int) $dep['id']; ?>"
                                <?php echo (($this->data['filters']['department_id'] ?? '') == $dep['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dep['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="ativo" class="form-label">Status</label>
                    <select id="ativo" name="ativo" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($this->data['filters']['ativo'] ?? '') === '1') ? 'selected' : ''; ?>>Ativos</option>
                        <option value="0" <?php echo (($this->data['filters']['ativo'] ?? '') === '0') ? 'selected' : ''; ?>>Inativos</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="urgente" class="form-label">Urgente</label>
                    <select id="urgente" name="urgente" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($this->data['filters']['urgente'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($this->data['filters']['urgente'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="data_inicio" class="form-label">Data início</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control"
                           value="<?php echo htmlspecialchars($this->data['filters']['data_inicio'] ?? ''); ?>">
                </div>

                <div class="col-md-2">
                    <label for="data_fim" class="form-label">Data fim</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control"
                           value="<?php echo htmlspecialchars($this->data['filters']['data_fim'] ?? ''); ?>">
                </div>

                <div class="col-md-2">
                    <label for="per_page" class="form-label">Registros por página</label>
                    <select id="per_page" name="per_page" class="form-select">
                        <?php
                        $perPage = (int) ($this->data['per_page'] ?? 10);
                        foreach ([10, 20, 50, 100] as $opt) {
                            $selected = $perPage === $opt ? 'selected' : '';
                            echo "<option value=\"{$opt}\" {$selected}>{$opt}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="btn btn-outline-secondary btn-sm">
                        Limpar
                    </a>
                </div>
            </form>
            <?php endif; ?>

            <!-- Tabela Desktop -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle table-policies">
                        <thead class="table-light">
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-titulo">Título</th>
                            <th class="col-categoria">Categoria</th>
                            <th class="col-departamento">Departamento</th>
                            <th class="col-urgente">Urgente</th>
                            <th class="col-ativo">Ativo</th>
                            <th class="col-data">Publicação</th>
                            <th class="text-center col-acoes">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($this->data['policies'])): ?>
                            <?php foreach ($this->data['policies'] as $policy): ?>
                                <tr>
                                    <td class="col-id"><?php echo (int) $policy['id']; ?></td>
                                    <td class="col-titulo">
                                        <?php echo htmlspecialchars($policy['titulo']); ?>
                                        <?php if (!empty($policy['requires_ack'])): ?>
                                            <span class="badge bg-warning text-dark ms-1">Exige ciência</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-categoria"><?php echo htmlspecialchars($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?></td>
                                    <td class="col-departamento"><?php echo htmlspecialchars($policy['department_name'] ?? ''); ?></td>
                                    <td class="col-urgente text-center">
                                        <?php if (!empty($policy['urgente'])): ?>
                                            <span class="badge bg-danger">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-ativo text-center">
                                        <?php if (!empty($policy['ativo'])): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-data text-center">
                                        <?php
                                        $refDate = $policy['publish_at'] ?? $policy['created_at'] ?? null;
                                        echo $refDate ? \App\adms\Helpers\FormatHelper::formatDateTime($refDate, 'd/m/Y H:i') : '-';
                                        ?>
                                    </td>
                                    <td class="text-center col-acoes">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php
                                            // Super administrador (nível 1) enxerga sempre todas as ações
                                            $isSuperAdmin = isset($_SESSION['user_access_level_id']) && (int)$_SESSION['user_access_level_id'] === 1;

                                            if ($isSuperAdmin): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) $policy['id']; ?>"
                                                   class="btn btn-outline-primary" title="Visualizar">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int) $policy['id']; ?>"
                                                   class="btn btn-outline-warning" title="Editar">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy?policy_id=<?php echo (int) $policy['id']; ?>"
                                                   class="btn btn-outline-info" title="Relatório de Visualização/Ciência">
                                                    <i class="fa-solid fa-chart-bar"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int) $policy['id']; ?>"
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Tem certeza que deseja excluir esta política?');"
                                                   title="Excluir">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <?php if (in_array('ViewPolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) $policy['id']; ?>"
                                                       class="btn btn-outline-primary" title="Visualizar">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($this->data['isEditor']) && in_array('UpdatePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int) $policy['id']; ?>"
                                                       class="btn btn-outline-warning" title="Editar">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array('RelatorioPolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy?policy_id=<?php echo (int) $policy['id']; ?>"
                                                       class="btn btn-outline-info" title="Relatório de Visualização/Ciência">
                                                        <i class="fa-solid fa-chart-bar"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($this->data['isEditor']) && in_array('DeletePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int) $policy['id']; ?>"
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Tem certeza que deseja excluir esta política?');"
                                                       title="Excluir">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Nenhuma política encontrada com os filtros informados.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cards Mobile -->
            <div class="d-block d-md-none">
                <?php if (!empty($this->data['policies'])): ?>
                    <?php foreach ($this->data['policies'] as $policy): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <strong><?php echo htmlspecialchars($policy['titulo']); ?></strong>
                                            <?php if (!empty($policy['requires_ack'])): ?>
                                                <span class="badge bg-warning text-dark ms-1">Exige ciência</span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mb-1">
                                            <?php if (!empty($policy['categoria_nome'] ?? $policy['categoria'])): ?>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['department_name'])): ?>
                                                <span class="badge bg-secondary ms-1">
                                                    <?php echo htmlspecialchars($policy['department_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['ativo'])): ?>
                                                <span class="badge bg-success ms-1">Ativa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-1">Inativa</span>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['urgente'])): ?>
                                                <span class="badge bg-danger ms-1">Urgente</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small mb-1">
                                            <?php
                                            $refDate = $policy['publish_at'] ?? $policy['created_at'] ?? null;
                                            if ($refDate) {
                                                echo '<i class="fas fa-calendar-alt me-1"></i>' . \App\adms\Helpers\FormatHelper::formatDateTime($refDate, 'd/m/Y H:i');
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php
                                        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && (int)$_SESSION['user_access_level_id'] === 1;
                                        ?>
                                        <?php if ($isSuperAdmin || in_array('ViewPolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int)$policy['id']; ?>"
                                               class="btn btn-outline-primary mb-1" title="Visualizar">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isSuperAdmin || (!empty($this->data['isEditor']) && in_array('UpdatePolicy', $this->data['buttonPermission'] ?? [], true))): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int)$policy['id']; ?>"
                                               class="btn btn-outline-warning mb-1" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isSuperAdmin || in_array('RelatorioPolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy?policy_id=<?php echo (int)$policy['id']; ?>"
                                               class="btn btn-outline-info mb-1" title="Relatório de Visualização/Ciência">
                                                <i class="fa-solid fa-chart-bar"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isSuperAdmin || (!empty($this->data['isEditor']) && in_array('DeletePolicy', $this->data['buttonPermission'] ?? [], true))): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int)$policy['id']; ?>"
                                               class="btn btn-outline-danger mb-1"
                                               onclick="return confirm('Tem certeza que deseja excluir esta política?');"
                                               title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        Nenhuma política encontrada com os filtros informados.
                    </div>
                <?php endif; ?>
            </div>

            <?php if (isset($this->data['pagination'])): ?>
                <div class="d-flex justify-content-center mt-3 d-none d-md-flex">
                    <?php
                    // Mesmo padrão da tela de Informativos:
                    // $this->data['pagination'] é um array com a chave 'html'
                    echo $this->data['pagination']['html'] ?? '';
                    ?>
                </div>
                <div class="d-flex justify-content-center mt-3 d-md-none">
                    <?php
                    $paginationHtml = $this->data['pagination']['html'] ?? '';
                    if ($paginationHtml) {
                        $paginationHtml = str_replace(
                            ['>Primeira<','>Anterior<','>Próximo<','>Última<','>Primeiro<'],
                            ['>&laquo;<','>&lsaquo;<','>&rsaquo;<','>&raquo;<','>&laquo;<'],
                            $paginationHtml
                        );
                        $paginationHtml = preg_replace(
                            '/class=\"pagination(.*?)\"/',
                            'class="pagination pagination-sm$1"',
                            $paginationHtml,
                            1
                        );
                        echo $paginationHtml;
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

