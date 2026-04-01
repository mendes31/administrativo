<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_informativo');
?>

<style>
    /* Layout otimizado para evitar corte de informações na listagem de informativos (desktop) */
    .table-informativos {
        width: 100%;
        table-layout: fixed;
    }

    .table-informativos th,
    .table-informativos td {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    /* Larguras FIXAS em pixels para cada coluna (desktop) */
    .table-informativos th.col-titulo,
    .table-informativos td.col-titulo {
        width: 240px;
    }

    .table-informativos th.col-categoria,
    .table-informativos td.col-categoria {
        width: 130px;
    }

    .table-informativos th.col-departamento,
    .table-informativos td.col-departamento {
        width: 150px;
    }

    .table-informativos th.col-resumo,
    .table-informativos td.col-resumo {
        width: 260px;
        /* Forçar quebra mesmo para textos muito longos ou sem espaços */
        white-space: normal !important;
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    .table-informativos th.col-urgente,
    .table-informativos td.col-urgente,
    .table-informativos th.col-status,
    .table-informativos td.col-status {
        width: 80px;
        text-align: center;
    }

    .table-informativos th.col-data,
    .table-informativos td.col-data {
        width: 120px;
        text-align: center;
    }

    .table-informativos th.col-acoes,
    .table-informativos td.col-acoes {
        width: 100px;
        text-align: center;
    }

    /* Limitar tamanho das badges para quebra de linha agradável */
    .table-informativos .badge {
        white-space: normal;
        word-wrap: break-word;
    }

    /* Imagem e ícone de anexo em linha, porém sem forçar largura extra */
    .table-informativos .informativo-media {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }

    .informativo-card-alert {
        border: 2px solid #dc3545; /* realce de alerta */
        background: rgba(220, 53, 69, 0.06);
    }

    /* Evitar cards “crescendo” no mobile: título e resumo com limite. */
    .informativo-card-title-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .informativo-card-summary {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Informativos da Empresa</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Informativos</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-newspaper me-2"></i>Listar Informativos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateInformativo', $this->data['buttonPermission'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-informativo" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php
            $unreadInformativoIds = array_map('intval', $this->data['unreadInformativoIds'] ?? []);
            $unreadInformativoIdSet = array_fill_keys($unreadInformativoIds, true);
            ?>
            
            <!-- Filtros (somente para usuários com permissão de cadastrar/editar informativos) -->
            <?php if (!empty($this->data['isEditor'])): ?>
                <form method="GET" class="mb-3">
                    <!-- Linha compacta (mobile) / alinhada (desktop) -->
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label for="busca" class="form-label mb-1">Buscar</label>
                            <input type="text" name="busca" id="busca" class="form-control" placeholder="Título ou conteúdo" value="<?= htmlspecialchars($this->data['filters']['busca'] ?? '') ?>">
                        </div>

                        <div class="col-6 col-md-auto mb-2">
                            <label for="per_page" class="form-label mb-1">Mostrar</label>
                            <div class="d-flex align-items-center">
                                <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 90px;" onchange="this.form.submit()">
                                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-label mb-1 ms-1">registros</span>
                            </div>
                        </div>

                        <div class="col-6 col-md-auto filtros-btns-row">
                            <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile w-100">
                                <i class="fa fa-search"></i> Filtrar
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="btn btn-secondary btn-sm btn-filtros-mobile w-100">
                                <i class="fa fa-times"></i> Limpar
                            </a>
                        </div>
                    </div>

                    <!-- Toggle de Filtros avançados no mobile -->
                    <div class="d-block d-md-none mt-2">
                        <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#informativosFiltersAdvanced" aria-expanded="false" aria-controls="informativosFiltersAdvanced">
                            <i class="fas fa-sliders me-2"></i>Filtros avançados
                        </button>
                    </div>

                    <!-- Filtros avançados -->
                    <div id="informativosFiltersAdvanced" class="collapse mt-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="categoria_id" class="form-label mb-1">Categoria</label>
                                <select name="categoria_id" id="categoria_id" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                        <option value="<?= (int)$categoria['id'] ?>" <?= (($this->data['filters']['categoria_id'] ?? '') == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="department_id" class="form-label mb-1">Departamento</label>
                                <select name="department_id" id="department_id" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                        <option value="<?= (int)$dep['id'] ?>" <?= (($this->data['filters']['department_id'] ?? '') == $dep['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dep['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="data_inicio" class="form-label mb-1">Data início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($this->data['filters']['data_inicio'] ?? '') ?>">
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="data_fim" class="form-label mb-1">Data fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($this->data['filters']['data_fim'] ?? '') ?>">
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="urgente" class="form-label mb-1">Urgente</label>
                                <select name="urgente" id="urgente" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="1" <?= (($this->data['filters']['urgente'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
                                    <option value="0" <?= (($this->data['filters']['urgente'] ?? '') === '0') ? 'selected' : '' ?>>Não</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="ativo" class="form-label mb-1">Status</label>
                                <select name="ativo" id="ativo" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="1" <?= (($this->data['filters']['ativo'] ?? '') === '1') ? 'selected' : '' ?>>Ativo</option>
                                    <option value="0" <?= (($this->data['filters']['ativo'] ?? '') === '0') ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Tabela Desktop -->
            <div class="d-none d-md-block list-desktop">
                <table class="table table-bordered table-striped table-hover table-informativos">
                    <thead class="table-dark">
                        <tr>
                            <th class="col-titulo">Título</th>
                            <th class="col-categoria">Categoria</th>
                            <th class="col-departamento">Departamento</th>
                            <th class="col-resumo">Resumo</th>
                            <th class="text-center col-urgente">Urgente</th>
                            <th class="text-center col-status">Status</th>
                            <th class="text-center col-data">Data</th>
                            <th class="text-center col-acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['informativos'])): ?>
                            <?php foreach ($this->data['informativos'] as $informativo): ?>
                                <?php
                                $informativoId = (int) ($informativo['id'] ?? 0);
                                $isUnread = $informativoId > 0 && isset($unreadInformativoIdSet[$informativoId]);
                                $requiresAck = !empty($informativo['requires_ack']);
                                ?>
                                <tr>
                                    <td class="col-titulo">
                                        <strong><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?></strong>
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
                                        <?php if (!empty($informativo['imagem']) || !empty($informativo['anexo'])): ?>
                                            <div class="informativo-media">
                                                <?php if (!empty($informativo['imagem'])): ?>
                                                    <a href="#"
                                                       onclick="return openInformativoImageDesktop(event, <?php echo (int)$informativoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, '<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>');">
                                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>" alt="Imagem" style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef; cursor: pointer;">
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($informativo['anexo'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>"
                                                       target="_blank"
                                                       title="Baixar anexo"
                                                       onclick="return openInformativoAttachment(event, <?php echo (int)$informativoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, this.href);">
                                                        <?php echo \App\adms\Helpers\FormatHelper::renderFileIcon($informativo['anexo'], 'fa-2x'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-categoria">
                                        <span class="badge bg-info"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['categoria_nome'] ?? $informativo['categoria'] ?? ''); ?></span>
                                    </td>
                                    <td class="col-departamento">
                                        <span class="badge bg-secondary"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['department_name'] ?? ''); ?></span>
                                    </td>
                                    <td class="col-resumo">
                                        <?php
                                        $textoResumo = $informativo['resumo'] ?? strip_tags($informativo['conteudo']);
                                        echo htmlspecialchars($textoResumo);
                                        ?>
                                    </td>
                                    <td class="text-center col-urgente">
                                        <?php if ($informativo['urgente']): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center col-status">
                                        <?php if ($informativo['ativo']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center col-data">
                                        <div class="d-flex flex-column small">
                                            <span title="Publicado em"><?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?></span>
                                            <?php if (!empty($informativo['expire_at'])): ?>
                                                <span class="text-muted" title="Expira em"><i class="fas fa-hourglass-end me-1"></i><?php echo date('d/m/Y H:i', strtotime($informativo['expire_at'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center col-acoes">
                                        <div class="btn-group" role="group">
                                            <?php if (in_array('ViewInformativo', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $informativo['id']; ?>" class="btn btn-primary btn-sm" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array('UpdateInformativo', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-informativo/<?php echo $informativo['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array('RelatorioInformativo', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo?informativo_id=<?php echo $informativo['id']; ?>" class="btn btn-info btn-sm" title="Relatório">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteInformativo', $this->data['buttonPermission'])): ?>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDelete<?php echo $informativo['id']; ?>-desktop">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <!-- Modal Bootstrap Desktop -->
                                                <div class="modal fade" id="modalDelete<?php echo $informativo['id']; ?>-desktop" tabindex="-1" aria-labelledby="modalDeleteLabel<?php echo $informativo['id']; ?>-desktop" aria-hidden="true">
                                                  <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                      <div class="modal-header">
                                                        <h5 class="modal-title" id="modalDeleteLabel<?php echo $informativo['id']; ?>-desktop"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Exclusão</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                      </div>
                                                      <div class="modal-body">
                                                        Tem certeza que deseja excluir o informativo <strong><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?></strong>?<br>
                                                        <small class="text-muted">Você não poderá reverter esta ação.</small>
                                                      </div>
                                                      <div class="modal-footer">
                                                        <form id="formDelete<?php echo $informativo['id']; ?>-desktop" action="<?php echo $_ENV['URL_ADM']; ?>delete-informativo" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="id" value="<?php echo $informativo['id']; ?>">
                                                            <input type="hidden" name="titulo" value="<?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?>">
                                                            <button type="submit" class="btn btn-danger">Sim, excluir!</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                      </div>
                                                    </div>
                                                  </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">Nenhum informativo encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile -->
            <div class="d-block d-md-none list-mobile">
                <?php if (!empty($this->data['informativos'])): ?>
                    <?php foreach ($this->data['informativos'] as $informativo): ?>
                        <?php $isEditor = !empty($this->data['isEditor']); ?>
                        <?php
                        $informativoId = (int) ($informativo['id'] ?? 0);
                        $isUnread = $informativoId > 0 && isset($unreadInformativoIdSet[$informativoId]);
                        $requiresAck = !empty($informativo['requires_ack']);
                        $isAckPendingAlert = $requiresAck && $isUnread;

                        $buttonPermission = $this->data['buttonPermission'] ?? [];
                        $canViewInformativo = in_array('ViewInformativo', $buttonPermission, true);
                        $cardClickable = (!$isEditor) || $canViewInformativo;

                        $canUpdateInformativo = in_array('UpdateInformativo', $buttonPermission, true);
                        $canRelatorioInformativo = in_array('RelatorioInformativo', $buttonPermission, true);
                        $canDeleteInformativo = in_array('DeleteInformativo', $buttonPermission, true);
                        $hasSecondaryActions = $canUpdateInformativo || $canRelatorioInformativo || $canDeleteInformativo;
                        ?>
                        <div class="card mb-3 shadow-sm<?php echo $isAckPendingAlert ? ' informativo-card-alert' : ''; ?>">
                            <div class="card-body"<?php if ($cardClickable): ?> onclick="window.location.href='<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $informativo['id']; ?>';" style="cursor:pointer;"<?php endif; ?>>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <strong class="informativo-card-title-text"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?></strong>
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
                                            <?php if ($informativo['urgente']): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <i class="fas fa-exclamation-triangle"></i> Urgente
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mb-1">
                                            <span class="badge bg-info"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['categoria_nome'] ?? $informativo['categoria'] ?? ''); ?></span>
                                            <?php if (!empty($informativo['department_name'])): ?>
                                                <span class="badge bg-secondary ms-1"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['department_name'] ?? ''); ?></span>
                                            <?php endif; ?>
                                            <?php if ($informativo['ativo']): ?>
                                                <span class="badge bg-success ms-1">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger ms-1">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($informativo['expire_at'])): ?>
                                            <div class="text-muted small" title="Expira em"><i class="fas fa-hourglass-end me-1"></i><?php echo date('d/m/Y H:i', strtotime($informativo['expire_at'])); ?></div>
                                        <?php endif; ?>
                                        <div class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <?php
                                            $textoResumoMobile = $informativo['resumo'] ?? strip_tags($informativo['conteudo']);
                                            ?>
                                            <small class="informativo-card-summary"><?php echo htmlspecialchars($textoResumoMobile); ?></small>
                                        </div>
                                        <?php if (!empty($informativo['imagem']) || !empty($informativo['anexo'])): ?>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <?php if (!empty($informativo['imagem'])): ?>
                                                    <a href="#"
                                                       onclick="return openInformativoImageMobile(event, <?php echo (int)$informativoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, '<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>');">
                                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>"
                                                             alt="Imagem"
                                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($informativo['anexo'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>"
                                                       target="_blank"
                                                       class="mobile-anexo-icon-thumb text-decoration-none"
                                                       onclick="return openInformativoAttachment(event, <?php echo (int)$informativoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, this.href);">
                                                        <?php echo \App\adms\Helpers\FormatHelper::renderFileIcon($informativo['anexo'], 'fa-2x'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEditor && $hasSecondaryActions): ?>
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
                                                <?php if ($canUpdateInformativo): ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?php echo $_ENV['URL_ADM']; ?>update-informativo/<?php echo $informativo['id']; ?>"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($canRelatorioInformativo): ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo?informativo_id=<?php echo $informativo['id']; ?>"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-chart-bar me-2"></i>Relatório
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($canDeleteInformativo): ?>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item text-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalDelete<?php echo $informativo['id']; ?>-mobile"
                                                                onclick="event.stopPropagation();">
                                                            <i class="fas fa-trash me-2"></i>Excluir
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <?php if ($canDeleteInformativo): ?>
                                            <!-- Modal Bootstrap Mobile -->
                                            <div class="modal fade" id="modalDelete<?php echo $informativo['id']; ?>-mobile" tabindex="-1" aria-labelledby="modalDeleteLabel<?php echo $informativo['id']; ?>-mobile" aria-hidden="true">
                                              <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                  <div class="modal-header">
                                                    <h5 class="modal-title" id="modalDeleteLabel<?php echo $informativo['id']; ?>-mobile"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                  </div>
                                                  <div class="modal-body">
                                                    Tem certeza que deseja excluir o informativo <strong><?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?></strong>?<br>
                                                    <small class="text-muted">Você não poderá reverter esta ação.</small>
                                                  </div>
                                                  <div class="modal-footer">
                                                    <form id="formDelete<?php echo $informativo['id']; ?>-mobile" action="<?php echo $_ENV['URL_ADM']; ?>delete-informativo" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="id" value="<?php echo $informativo['id']; ?>">
                                                        <input type="hidden" name="titulo" value="<?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-danger">Sim, excluir!</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                  </div>
                                                </div>
                                              </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>Nenhum informativo encontrado.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Paginação -->
            <?php if (isset($this->data['pagination'])): ?>
                <div class="d-flex justify-content-center mt-3 d-none d-md-flex">
                    <?php echo $this->data['pagination']['html']; ?>
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
                        $paginationHtml = preg_replace('/class=\"pagination(.*?)\"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                        echo $paginationHtml;
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 

<script>
// Modal de imagem compatível com Bootstrap (participa do controle global de histórico)
function showImageModal(url) {
    var modalEl = document.getElementById('informativoImageModal');
    var imgEl;

    if (!modalEl) {
        modalEl = document.createElement('div');
        modalEl.id = 'informativoImageModal';
        modalEl.className = 'modal fade';
        modalEl.setAttribute('tabindex', '-1');
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.innerHTML = ''
            + '<div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-xl">'
            + '  <div class="modal-content border-0 bg-dark bg-opacity-75">'
            + '    <div class="modal-header border-0">'
            + '      <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>'
            + '    </div>'
            + '    <div class="modal-body d-flex align-items-center justify-content-center p-1 p-md-3">'
            + '      <img id="informativoImageModalImg" src="" alt="Imagem" class="img-fluid" style="max-height:90vh;object-fit:contain;">'
            + '    </div>'
            + '  </div>'
            + '</div>';
        document.body.appendChild(modalEl);
    }

    imgEl = document.getElementById('informativoImageModalImg');
    if (imgEl) {
        imgEl.src = url;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: 'static',
        keyboard: true
    });
    modal.show();
}

// Etapa 3/4 (Informativos): ao clicar em imagem/anexo do card mobile,
// marcar como "lido" via read-informativo apenas quando requires_ack=0.
function openInformativoAttachment(event, informativoId, requiresAck, url) {
    event.stopPropagation();

    // Se exige ciência, não marca como lido automaticamente.
    if (requiresAck) {
        return true;
    }

    event.preventDefault();

    try {
        window.open(url, '_blank', 'noopener,noreferrer');
        fetch('<?php echo $_ENV['URL_ADM']; ?>read-informativo/' + informativoId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () {});
    } catch (e) {}

    setTimeout(function () {
        window.location.reload();
    }, 250);

    return false;
}

// Desktop: abre modal da imagem e, quando requires_ack=0, marca como lido.
function openInformativoImageDesktop(event, informativoId, requiresAck, imageUrl) {
    event.preventDefault();
    event.stopPropagation();

    // Se exige ciência, apenas abre o modal.
    if (requiresAck) {
        showImageModal(imageUrl);
        return false;
    }

    // Marca como lido e abre o modal.
    try {
        fetch('<?php echo $_ENV['URL_ADM']; ?>read-informativo/' + informativoId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () {});
    } catch (e) {}

    showImageModal(imageUrl);

    setTimeout(function () {
        window.location.reload();
    }, 250);

    return false;
}

// Mobile: abre a imagem em modal Bootstrap, evitando navegação de página
function openInformativoImageMobile(event, informativoId, requiresAck, imageUrl) {
    event.preventDefault();
    event.stopPropagation();

    if (!requiresAck) {
        try {
            fetch('<?php echo $_ENV['URL_ADM']; ?>read-informativo/' + informativoId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).catch(function () {});
        } catch (e) {}
    }

    showImageModal(imageUrl);
    return false;
}
</script> 