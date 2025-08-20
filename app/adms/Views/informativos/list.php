<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_informativo');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Informativos da Empresa</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
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
            
            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-2">
                    <label for="categoria" class="form-label mb-1">Categoria</label>
                    <select name="categoria" id="categoria" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria) ?>" <?= (($this->data['filters']['categoria'] ?? '') === $categoria) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label mb-1">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($this->data['filters']['data_inicio'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_fim" class="form-label mb-1">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($this->data['filters']['data_fim'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <select name="urgente" class="form-select">
                        <option value="">Urgente</option>
                        <option value="1" <?= (($this->data['filters']['urgente'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($this->data['filters']['urgente'] ?? '') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select name="ativo" class="form-select">
                        <option value="">Status</option>
                        <option value="1" <?= (($this->data['filters']['ativo'] ?? '') === '1') ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= (($this->data['filters']['ativo'] ?? '') === '0') ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="busca" class="form-label mb-1">Buscar</label>
                    <input type="text" name="busca" id="busca" class="form-control" placeholder="Título ou conteúdo" value="<?= htmlspecialchars($this->data['filters']['busca'] ?? '') ?>">
                </div>
                <div class="col-auto mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
                <div class="col-md-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="btn btn-secondary btn-sm btn-filtros-mobile">
                        <i class="fa fa-times"></i> Limpar Filtros
                    </a>
                </div>
            </form>

            <!-- Tabela Desktop -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-striped table-hover table-fixed">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 5%;">ID</th>
                            <th style="width: 25%;">Título</th>
                            <th style="width: 15%;">Categoria</th>
                            <th style="width: 20%;">Resumo</th>
                            <th class="text-center" style="width: 10%;">Urgente</th>
                            <th class="text-center" style="width: 10%;">Status</th>
                            <th class="text-center" style="width: 10%;">Data</th>
                            <th class="text-center" style="width: 5%;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['informativos'])): ?>
                            <?php foreach ($this->data['informativos'] as $informativo): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($informativo['id']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($informativo['titulo']); ?></strong>
                                        <?php if (!empty($informativo['imagem']) || !empty($informativo['anexo'])): ?>
                                            <div class="d-flex gap-2 mt-2">
                                                <?php if (!empty($informativo['imagem'])): ?>
                                                    <a href="#" onclick="showImageModal('<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>'); return false;">
                                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>" alt="Imagem" style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef; cursor: pointer;">
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($informativo['anexo'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>" target="_blank" title="Baixar anexo">
                                                        <i class="fas fa-file-pdf fa-2x text-danger" style="vertical-align: middle;"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($informativo['categoria']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($informativo['resumo'] ?? substr(strip_tags($informativo['conteudo']), 0, 100) . '...'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($informativo['urgente']): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
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
                                    <td class="text-center">
                                        <small><?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?></small>
                                    </td>
                                    <td class="text-center">
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
                                                        Tem certeza que deseja excluir o informativo <strong><?php echo htmlspecialchars($informativo['titulo']); ?></strong>?<br>
                                                        <small class="text-muted">Você não poderá reverter esta ação.</small>
                                                      </div>
                                                      <div class="modal-footer">
                                                        <form id="formDelete<?php echo $informativo['id']; ?>-desktop" action="<?php echo $_ENV['URL_ADM']; ?>delete-informativo" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="id" value="<?php echo $informativo['id']; ?>">
                                                            <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($informativo['titulo']); ?>">
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
            <div class="d-block d-md-none">
                <?php if (!empty($this->data['informativos'])): ?>
                    <?php foreach ($this->data['informativos'] as $informativo): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <strong><?php echo htmlspecialchars($informativo['titulo']); ?></strong>
                                            <?php if ($informativo['urgente']): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <i class="fas fa-exclamation-triangle"></i> Urgente
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mb-1">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($informativo['categoria']); ?></span>
                                            <?php if ($informativo['ativo']): ?>
                                                <span class="badge bg-success ms-1">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger ms-1">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($informativo['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small><?php echo htmlspecialchars($informativo['resumo'] ?? substr(strip_tags($informativo['conteudo']), 0, 100) . '...'); ?></small>
                                        </div>
                                        <?php if (!empty($informativo['imagem']) || !empty($informativo['anexo'])): ?>
                                            <div class="mb-2">
                                                <?php if (!empty($informativo['imagem'])): ?>
                                                    <small class="text-muted me-2"><i class="fas fa-image"></i> Com imagem</small>
                                                <?php endif; ?>
                                                <?php if (!empty($informativo['anexo'])): ?>
                                                    <small class="text-muted"><i class="fas fa-paperclip"></i> Com anexo</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php if (in_array('ViewInformativo', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $informativo['id']; ?>" class="btn btn-primary btn-sm mb-1" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array('UpdateInformativo', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-informativo/<?php echo $informativo['id']; ?>" class="btn btn-warning btn-sm mb-1" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array('DeleteInformativo', $this->data['buttonPermission'])): ?>
                                            <button type="button" class="btn btn-danger btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#modalDelete<?php echo $informativo['id']; ?>-mobile">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <!-- Modal Bootstrap Mobile -->
                                            <div class="modal fade" id="modalDelete<?php echo $informativo['id']; ?>-mobile" tabindex="-1" aria-labelledby="modalDeleteLabel<?php echo $informativo['id']; ?>-mobile" aria-hidden="true">
                                              <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                  <div class="modal-header">
                                                    <h5 class="modal-title" id="modalDeleteLabel<?php echo $informativo['id']; ?>-mobile"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                  </div>
                                                  <div class="modal-body">
                                                    Tem certeza que deseja excluir o informativo <strong><?php echo htmlspecialchars($informativo['titulo']); ?></strong>?<br>
                                                    <small class="text-muted">Você não poderá reverter esta ação.</small>
                                                  </div>
                                                  <div class="modal-footer">
                                                    <form id="formDelete<?php echo $informativo['id']; ?>-mobile" action="<?php echo $_ENV['URL_ADM']; ?>delete-informativo" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="id" value="<?php echo $informativo['id']; ?>">
                                                        <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($informativo['titulo']); ?>">
                                                        <button type="submit" class="btn btn-danger">Sim, excluir!</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                  </div>
                                                </div>
                                              </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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
                <div class="d-flex justify-content-center mt-3">
                    <?php echo $this->data['pagination']['html']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 

<script>
function showImageModal(url) {
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.innerHTML = `
        <div style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="this.remove()">
            <img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 4px 32px rgba(0,0,0,0.25);background:#fff;">
        </div>
        `;
        document.body.appendChild(modal);
    }
}
</script> 