<?php
// Verificar se o usuário tem a permissão de acessar a página
if (!isset($this->data['access_level'])) {
    header("Location: " . getenv('URL_ADM') . "error-403");
    exit();
}
?>

<?php include_once "app/adms/Views/partials/head.php"; ?>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Listar Perguntas de Avaliação</h1>
                        <?php if (!empty($this->data['buttonPermission']['CreateEvaluationQuestion'])) { ?>
                            <a href="<?= $urlAdm ?>create-evaluation-question/index" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-plus fa-sm text-white-50"></i> Nova Pergunta
                            </a>
                        <?php } ?>
                    </div>

                    <!-- Search Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtros de Pesquisa</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="<?= $urlAdm ?>list-evaluation-questions/index">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="search">Pesquisar:</label>
                                            <input type="text" class="form-control" id="search" name="search" 
                                                   value="<?= $this->data['criteria']['search'] ?? '' ?>" 
                                                   placeholder="Digite a pergunta...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="model_id">Modelo:</label>
                                            <select class="form-control" id="model_id" name="model_id">
                                                <option value="">Todos os modelos</option>
                                                <?php foreach ($this->data['models'] as $model) { ?>
                                                    <option value="<?= $model['id'] ?>" 
                                                            <?= (isset($this->data['criteria']['model_id']) && $this->data['criteria']['model_id'] == $model['id']) ? 'selected' : '' ?>>
                                                        <?= $model['titulo'] ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="tipo">Tipo:</label>
                                            <select class="form-control" id="tipo" name="tipo">
                                                <option value="">Todos os tipos</option>
                                                <option value="texto" <?= (isset($this->data['criteria']['tipo']) && $this->data['criteria']['tipo'] == 'texto') ? 'selected' : '' ?>>Texto</option>
                                                <option value="multipla_escolha" <?= (isset($this->data['criteria']['tipo']) && $this->data['criteria']['tipo'] == 'multipla_escolha') ? 'selected' : '' ?>>Múltipla Escolha</option>
                                                <option value="verdadeiro_falso" <?= (isset($this->data['criteria']['tipo']) && $this->data['criteria']['tipo'] == 'verdadeiro_falso') ? 'selected' : '' ?>>Verdadeiro/Falso</option>
                                                <option value="numerica" <?= (isset($this->data['criteria']['tipo']) && $this->data['criteria']['tipo'] == 'numerica') ? 'selected' : '' ?>>Numérica</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-search"></i> Pesquisar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Perguntas de Avaliação</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Modelo</th>
                                            <th>Pergunta</th>
                                            <th>Tipo</th>
                                            <th>Ordem</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($this->data['questions'])) { ?>
                                            <?php foreach ($this->data['questions'] as $question) { ?>
                                                <tr>
                                                    <td><?= $question['id'] ?></td>
                                                    <td><?= $question['model_name'] ?? 'N/A' ?></td>
                                                    <td>
                                                        <?php 
                                                        $pergunta = $question['pergunta'];
                                                        echo strlen($pergunta) > 50 ? substr($pergunta, 0, 50) . '...' : $pergunta;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $tipos = [
                                                            'texto' => '<span class="badge badge-info">Texto</span>',
                                                            'multipla_escolha' => '<span class="badge badge-primary">Múltipla Escolha</span>',
                                                            'verdadeiro_falso' => '<span class="badge badge-warning">Verdadeiro/Falso</span>',
                                                            'numerica' => '<span class="badge badge-success">Numérica</span>'
                                                        ];
                                                        echo $tipos[$question['tipo']] ?? '<span class="badge badge-secondary">' . $question['tipo'] . '</span>';
                                                        ?>
                                                    </td>
                                                    <td><?= $question['ordem'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($question['created_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <?php if ($this->data['buttonPermission']['ViewEvaluationQuestion']) { ?>
                                                                <a href="<?= $urlAdm ?>view-evaluation-question/index/<?= $question['id'] ?>" 
                                                                   class="btn btn-info btn-sm" title="Visualizar">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php } ?>
                                                            <?php if ($this->data['buttonPermission']['UpdateEvaluationQuestion']) { ?>
                                                                <a href="<?= $urlAdm ?>update-evaluation-question/index/<?= $question['id'] ?>" 
                                                                   class="btn btn-warning btn-sm" title="Editar">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            <?php } ?>
                                                            <?php if ($this->data['buttonPermission']['DeleteEvaluationQuestion']) { ?>
                                                                <a href="<?= $urlAdm ?>delete-evaluation-question/index/<?= $question['id'] ?>" 
                                                                   class="btn btn-danger btn-sm" title="Apagar"
                                                                   onclick="return confirm('Tem certeza que deseja apagar esta pergunta?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhuma pergunta encontrada.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
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
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once "app/adms/Views/partials/footer.php"; ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

</body>

</html> 