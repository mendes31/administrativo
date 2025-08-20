<?php
// Verificar se o usuário tem a permissão de acessar a página
if (!isset($this->data['access_level'])) {
    header("Location: " . getenv('URL_ADM') . "error-403");
    exit();
}
?>

<?php include_once "app/adms/Views/partials/head.php"; ?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Listar Respostas de Avaliação</h1>
                        <div>
                            <a href="<?= $urlAdm ?>create-evaluation-answer/index" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-plus"></i> Nova Resposta
                            </a>
                            <a href="<?= $urlAdm ?>list-evaluation-answers/index?export=csv<?= http_build_query($this->data['criteria']) ? '&' . http_build_query($this->data['criteria']) : '' ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-file-csv"></i> Exportar CSV
                            </a>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="<?= $urlAdm ?>list-evaluation-answers/index">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="usuario_id">Usuário</label>
                                        <input type="text" class="form-control" id="usuario_id" name="usuario_id" value="<?= $this->data['criteria']['usuario_id'] ?? '' ?>" placeholder="ID do usuário">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="modelo_id">Modelo</label>
                                        <select class="form-control" id="modelo_id" name="modelo_id">
                                            <option value="">Todos</option>
                                            <?php foreach ($this->data['models'] as $model) { ?>
                                                <option value="<?= $model['id'] ?>" <?= (isset($this->data['criteria']['modelo_id']) && $this->data['criteria']['modelo_id'] == $model['id']) ? 'selected' : '' ?>><?= $model['titulo'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="pergunta_id">Pergunta</label>
                                        <select class="form-control" id="pergunta_id" name="pergunta_id">
                                            <option value="">Todas</option>
                                            <?php foreach ($this->data['questions'] as $question) { ?>
                                                <option value="<?= $question['id'] ?>" <?= (isset($this->data['criteria']['pergunta_id']) && $this->data['criteria']['pergunta_id'] == $question['id']) ? 'selected' : '' ?>><?= $question['pergunta'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">Todos</option>
                                            <option value="aprovada" <?= (isset($this->data['criteria']['status']) && $this->data['criteria']['status'] == 'aprovada') ? 'selected' : '' ?>>Aprovada</option>
                                            <option value="reprovada" <?= (isset($this->data['criteria']['status']) && $this->data['criteria']['status'] == 'reprovada') ? 'selected' : '' ?>>Reprovada</option>
                                            <option value="pendente" <?= (isset($this->data['criteria']['status']) && $this->data['criteria']['status'] == 'pendente') ? 'selected' : '' ?>>Pendente</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <label for="data_ini">Data Inicial</label>
                                        <input type="date" class="form-control" id="data_ini" name="data_ini" value="<?= $this->data['criteria']['data_ini'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="data_fim">Data Final</label>
                                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $this->data['criteria']['data_fim'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3 align-self-end">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabela de respostas -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Respostas</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuário</th>
                                            <th>Modelo</th>
                                            <th>Pergunta</th>
                                            <th>Resposta</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($this->data['answers'])) { ?>
                                            <?php foreach ($this->data['answers'] as $answer) { ?>
                                                <tr>
                                                    <td><?= $answer['id'] ?></td>
                                                    <td><?= $answer['usuario_nome'] ?? $answer['usuario_id'] ?></td>
                                                    <td><?= $answer['modelo_titulo'] ?? $answer['evaluation_model_id'] ?></td>
                                                    <td><?= $answer['pergunta'] ?? $answer['evaluation_question_id'] ?></td>
                                                    <td><?= strlen($answer['resposta']) > 40 ? substr($answer['resposta'], 0, 40) . '...' : $answer['resposta'] ?></td>
                                                    <td>
                                                        <?php
                                                        $status = $answer['status'] ?? 'pendente';
                                                        $badge = [
                                                            'aprovada' => 'success',
                                                            'reprovada' => 'danger',
                                                            'pendente' => 'warning',
                                                        ];
                                                        ?>
                                                        <span class="badge badge-<?= $badge[$status] ?? 'secondary' ?>"><?= ucfirst($status) ?></span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($answer['created_at'])) ?></td>
                                                    <td>
                                                        <a href="<?= $urlAdm ?>view-evaluation-answer/index/<?= $answer['id'] ?>" class="btn btn-info btn-sm" title="Visualizar Detalhes">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?= $urlAdm ?>update-evaluation-answer/index/<?= $answer['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?= $urlAdm ?>delete-evaluation-answer/index/<?= $answer['id'] ?>" class="btn btn-danger btn-sm" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar esta resposta?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Nenhuma resposta encontrada.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Paginação -->
                            <?php if (!empty($this->data['pagination'])) { ?>
                                <div class="d-flex justify-content-center">
                                    <?php
                                    if (is_array($this->data['pagination'])) {
                                        foreach ($this->data['pagination'] as $item) {
                                            echo $item . ' ';
                                        }
                                    } else {
                                        echo $this->data['pagination'];
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "app/adms/Views/partials/footer.php"; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
</body>
</html> 