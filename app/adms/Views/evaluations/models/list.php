<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_evaluation_model');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Modelos de Avaliação</h2>

        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Avaliações</li>
            <li class="breadcrumb-item">Modelos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar Modelos</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreateEvaluationModel', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-evaluation-model' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">
            <!-- Filtros de busca -->
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por título ou descrição..." 
                               value="<?php echo $this->data['criteria']['search'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="training_id" class="form-select">
                            <option value="">Todos os treinamentos</option>
                            <!-- Aqui você pode adicionar as opções de treinamentos se necessário -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="active" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="1" <?php echo (isset($this->data['criteria']['ativo']) && $this->data['criteria']['ativo'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo (isset($this->data['criteria']['ativo']) && $this->data['criteria']['ativo'] == 0) ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>

            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há modelos no array
            if ($this->data['models'] ?? false) {
            ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Título</th>
                                <th scope="col" class="d-none d-md-table-cell">Descrição</th>
                                <th scope="col" class="d-none d-md-table-cell">Treinamento</th>
                                <th scope="col" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" class="d-none d-md-table-cell">Criado em</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Percorre o array de modelos
                            foreach ($this->data['models'] as $model) {
                                // Extrai variáveis do array de modelo
                                extract($model);
                                ?>
                                <tr>
                                    <th><?php echo $id; ?></th>
                                    <td><?php echo htmlspecialchars($titulo); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo htmlspecialchars(substr($descricao ?? '', 0, 50)) . (strlen($descricao ?? '') > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo htmlspecialchars($training_name ?? 'N/A'); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if ($ativo == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo date('d/m/Y H:i', strtotime($created_at)); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        if (in_array('ViewEvaluationModel', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-evaluation-model/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                                        }

                                        if (in_array('UpdateEvaluationModel', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-evaluation-model/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
                                        }

                                        if (in_array('DeleteEvaluationModel', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-evaluation-model" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)">
                                                    <i class="fa-regular fa-trash-can"></i> Apagar
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            <?php
                // Inclui o arquivo de paginação
                include_once './app/adms/Views/partials/pagination.php';
            } else {
                // Acessa o ELSE quando não existir registros
                echo "<div class='alert alert-info' role='alert'>
                        <i class='fas fa-info-circle me-2'></i>
                        Nenhum modelo de avaliação encontrado.
                      </div>";
            } ?>

        </div>
    </div>
</div>

<script type="text/javascript">
    function confirmDeletion(event, id) {
        event.preventDefault();
        if (confirm('Tem certeza que deseja excluir este modelo de avaliação? Esta ação não pode ser desfeita.')) {
            document.getElementById('formDelete' + id).submit();
        }
    }
</script> 