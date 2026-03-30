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

    .policy-card-alert {
        border: 2px solid #dc3545; /* realce de alerta */
        background: rgba(220, 53, 69, 0.06);
    }

    /* Evitar cards “crescendo” no mobile: título e resumo com limite. */
    .policy-card-title-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .policy-card-summary {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Políticas Internas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
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
                $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
                if ($isSuperAdmin || in_array('CreatePolicy', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-policy" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
            <?php
            $unreadPolicyIds = array_map('intval', $this->data['unreadPolicyIds'] ?? []);
            $unreadPolicyIdSet = array_fill_keys($unreadPolicyIds, true);
            ?>
            <?php if (!empty($this->data['isEditor'])): ?>
            <form method="get" action="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="busca" class="form-label">Busca</label>
                        <input type="text" id="busca" name="busca" class="form-control"
                               value="<?php echo htmlspecialchars($this->data['filters']['busca'] ?? ''); ?>"
                               placeholder="Título, conteúdo ou resumo">
                    </div>

                    <div class="col-6 col-md-auto mb-2">
                        <label for="per_page" class="form-label">Mostrar</label>
                        <select id="per_page" name="per_page" class="form-select form-select-sm" style="min-width: 90px;" onchange="this.form.submit()">
                            <?php
                            $perPage = (int) ($this->data['per_page'] ?? 10);
                            foreach ([10, 20, 50, 100] as $opt) {
                                $selected = $perPage === $opt ? 'selected' : '';
                                echo "<option value=\"{$opt}\" {$selected}>{$opt}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-auto filtros-btns-row">
                        <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile w-100">
                            <i class="fa fa-search"></i> Filtrar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="btn btn-secondary btn-sm btn-filtros-mobile w-100">
                            <i class="fa fa-times"></i> Limpar
                        </a>
                    </div>
                </div>

                <!-- Toggle de Filtros avançados no mobile -->
                <div class="d-block d-md-none mt-2">
                    <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#policiesFiltersAdvanced" aria-expanded="false" aria-controls="policiesFiltersAdvanced">
                        <i class="fas fa-sliders me-2"></i>Filtros avançados
                    </button>
                </div>

                <div id="policiesFiltersAdvanced" class="collapse mt-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
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

                        <div class="col-12 col-md-3">
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

                        <div class="col-6 col-md-2">
                            <label for="data_inicio" class="form-label">Data início</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control"
                                   value="<?php echo htmlspecialchars($this->data['filters']['data_inicio'] ?? ''); ?>">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="data_fim" class="form-label">Data fim</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control"
                                   value="<?php echo htmlspecialchars($this->data['filters']['data_fim'] ?? ''); ?>">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="urgente" class="form-label">Urgente</label>
                            <select id="urgente" name="urgente" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" <?php echo (($this->data['filters']['urgente'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                                <option value="0" <?php echo (($this->data['filters']['urgente'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="ativo" class="form-label">Status</label>
                            <select id="ativo" name="ativo" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" <?php echo (($this->data['filters']['ativo'] ?? '') === '1') ? 'selected' : ''; ?>>Ativos</option>
                                <option value="0" <?php echo (($this->data['filters']['ativo'] ?? '') === '0') ? 'selected' : ''; ?>>Inativos</option>
                            </select>
                        </div>
                    </div>
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
                                <?php
                                $policyId = (int) ($policy['id'] ?? 0);
                                $isUnread = $policyId > 0 && isset($unreadPolicyIdSet[$policyId]);
                                $requiresAck = !empty($policy['requires_ack']);
                                ?>
                                <tr>
                                    <td class="col-id"><?php echo (int) $policy['id']; ?></td>
                                    <td class="col-titulo">
                                        <?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['titulo'] ?? ''); ?>
                                        <?php if ($requiresAck): ?>
                                            <?php if ($isUnread): ?>
                                                <span class="badge bg-warning text-dark ms-1" style="border:1px solid #dc3545;">
                                                    Ciência pendente
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success ms-1">Ciente</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($isUnread): ?>
                                                <span class="badge bg-primary ms-1">Novo</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-categoria"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?></td>
                                    <td class="col-departamento"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['department_name'] ?? ''); ?></td>
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
                                            $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();

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
                        <?php $isEditor = !empty($this->data['isEditor']); ?>
                        <?php
                        $policyId = (int) ($policy['id'] ?? 0);
                        $isUnread = $policyId > 0 && isset($unreadPolicyIdSet[$policyId]);
                        $requiresAck = !empty($policy['requires_ack']);
                        $isAckPendingAlert = $requiresAck && $isUnread;

                        $buttonPermission = $this->data['buttonPermission'] ?? [];
                        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
                        $canViewPolicy = $isSuperAdmin || in_array('ViewPolicy', $buttonPermission, true);
                        $cardClickable = (!$isEditor) || $canViewPolicy;

                        $canUpdatePolicy = $isSuperAdmin || in_array('UpdatePolicy', $buttonPermission, true);
                        $canRelatorioPolicy = $isSuperAdmin || in_array('RelatorioPolicy', $buttonPermission, true);
                        $canDeletePolicy = $isSuperAdmin || in_array('DeletePolicy', $buttonPermission, true);
                        $hasSecondaryActions = $canUpdatePolicy || $canRelatorioPolicy || $canDeletePolicy;
                        ?>
                        <div class="card mb-3 shadow-sm<?php echo $isAckPendingAlert ? ' policy-card-alert' : ''; ?>">
                            <div class="card-body"<?php if ($cardClickable): ?> onclick="window.location.href='<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int)$policy['id']; ?>';" style="cursor:pointer;"<?php endif; ?>>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <strong class="policy-card-title-text"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['titulo'] ?? ''); ?></strong>
                                            <?php if ($requiresAck): ?>
                                                <?php if ($isUnread): ?>
                                                    <span class="badge bg-warning text-dark ms-1" style="border:1px solid #dc3545;">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Ciência pendente
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success ms-1">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Ciente
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($isUnread): ?>
                                                    <span class="badge bg-primary ms-1">Novo</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['urgente'])): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mb-1 d-flex flex-wrap align-items-center gap-1">
                                            <?php if (!empty($policy['categoria_nome'] ?? $policy['categoria'])): ?>
                                                <span class="badge bg-info">
                                                    <?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['department_name'])): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo \App\adms\Helpers\TextEncodingHelper::escape($policy['department_name'] ?? ''); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($policy['ativo'])): ?>
                                                <span class="badge bg-success">Ativa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativa</span>
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
                                        <div class="mb-2">
                                            <?php
                                            $textoResumoMobile = $policy['resumo'] ?? strip_tags($policy['conteudo'] ?? '');
                                            ?>
                                            <small class="policy-card-summary"><?php echo htmlspecialchars($textoResumoMobile); ?></small>
                                        </div>
                                        <?php if (!empty($policy['imagem']) || !empty($policy['anexo'])): ?>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <?php if (!empty($policy['imagem'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['imagem']); ?>"
                                                       target="_blank"
                                                       onclick="return openPolicyAttachment(event, <?php echo (int)$policyId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, this.href);">
                                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['imagem']); ?>"
                                                             alt="Imagem"
                                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.08); cursor: pointer;">
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($policy['anexo'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['anexo']); ?>"
                                                       target="_blank"
                                                       class="mobile-anexo-icon-thumb text-decoration-none"
                                                       onclick="return openPolicyAttachment(event, <?php echo (int)$policyId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, this.href);">
                                                        <?php echo \App\adms\Helpers\FormatHelper::renderFileIcon($policy['anexo'], 'fa-2x'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (($isEditor || $isSuperAdmin) && $hasSecondaryActions): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm mobile-card-actions-btn"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                    aria-label="Ações"
                                                    onclick="event.stopPropagation();">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if ($canUpdatePolicy): ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int)$policy['id']; ?>"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($canRelatorioPolicy): ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?php echo $_ENV['URL_ADM']; ?>relatorio-policy?policy_id=<?php echo (int)$policy['id']; ?>"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-chart-bar me-2"></i>Relatório
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($canDeletePolicy): ?>
                                                    <li>
                                                        <a class="dropdown-item text-danger"
                                                           href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int)$policy['id']; ?>"
                                                           onclick="event.stopPropagation(); return confirm('Tem certeza que deseja excluir esta política?');">
                                                            <i class="fas fa-trash me-2"></i>Excluir
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
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

<script>
    // Etapa 2 (Políticas): ao clicar em imagem/anexo do card (mobile),
    // marcar como "lido" via endpoint read-policy apenas quando requires_ack=0.
    function openPolicyAttachment(event, policyId, requiresAck, url) {
        event.stopPropagation();

        // Se exige ciência, não marcar como lido automaticamente.
        if (requiresAck) {
            return true;
        }

        event.preventDefault();

        try {
            // Abre o anexo/ imagem em nova aba.
            window.open(url, '_blank', 'noopener,noreferrer');

            // Marca como lida para atualizar sino/badge.
            fetch('<?php echo $_ENV['URL_ADM']; ?>read-policy/' + policyId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).catch(function () {});
        } catch (e) {}

        // Recarregar para refletir "Novo" removido / sino atualizado.
        setTimeout(function () {
            window.location.reload();
        }, 250);

        return false;
    }
</script>

