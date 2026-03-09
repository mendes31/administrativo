<?php
use App\adms\Helpers\FormatHelper;
?>

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
                <?php if (!empty($this->data['buttonPermission']) && in_array('CreatePolicy', $this->data['buttonPermission'], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-policy" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
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

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Categoria</th>
                        <th>Departamento</th>
                        <th>Urgente</th>
                        <th>Ativo</th>
                        <th>Publicação</th>
                        <th class="text-center">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($this->data['policies'])): ?>
                        <?php foreach ($this->data['policies'] as $policy): ?>
                            <tr>
                                <td><?php echo (int) $policy['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($policy['titulo']); ?>
                                    <?php if (!empty($policy['requires_ack'])): ?>
                                        <span class="badge bg-warning text-dark ms-1">Exige ciência</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($policy['categoria_nome'] ?? $policy['categoria'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($policy['department_name'] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty($policy['urgente'])): ?>
                                        <span class="badge bg-danger">Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($policy['ativo'])): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $refDate = $policy['publish_at'] ?? $policy['created_at'] ?? null;
                                    echo $refDate ? FormatHelper::dateTimeBr($refDate) : '-';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (!empty($this->data['buttonPermission']) && in_array('ViewPolicy', $this->data['buttonPermission'], true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) $policy['id']; ?>"
                                               class="btn btn-outline-primary" title="Visualizar">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($this->data['buttonPermission']) && in_array('UpdatePolicy', $this->data['buttonPermission'], true) && !empty($this->data['isEditor'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy/<?php echo (int) $policy['id']; ?>"
                                               class="btn btn-outline-warning" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($this->data['buttonPermission']) && in_array('DeletePolicy', $this->data['buttonPermission'], true) && !empty($this->data['isEditor'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>delete-policy/<?php echo (int) $policy['id']; ?>"
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir esta política?');"
                                               title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
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

