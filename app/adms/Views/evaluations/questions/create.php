<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_create_evaluation_question');

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

        <?php include_once "app/adms/Views/partials/sidebar.php"; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once "app/adms/Views/partials/topbar.php"; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Cadastrar Pergunta de Avaliação</h1>
                        <?php if ($this->data['buttonPermission']['ListEvaluationQuestions']) { ?>
                            <a href="<?= $urlAdm ?>list-evaluation-questions/index" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-list fa-sm text-white-50"></i> Listar Perguntas
                            </a>
                        <?php } ?>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Content Column -->
                        <div class="col-lg-12 mb-4">

                            <!-- Form Card -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Dados da Pergunta</h6>
                                </div>
                                <div class="card-body">

                                    <?php if (isset($this->data['error'])) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-triangle"></i> <?= $this->data['error'] ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php } ?>

                                    <?php if (isset($this->data['success'])) { ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle"></i> <?= $this->data['success'] ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php } ?>

                                    <form method="POST" action="<?= $urlAdm ?>create-evaluation-question/index" id="form-create-question">
                                        
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="model_id">Modelo de Avaliação <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="model_id" name="model_id" required>
                                                        <option value="">Selecione um modelo</option>
                                                        <?php foreach ($this->data['models'] as $model) { ?>
                                                            <option value="<?= $model['id'] ?>" 
                                                                    <?= (isset($this->dataForm['model_id']) && $this->dataForm['model_id'] == $model['id']) ? 'selected' : '' ?>>
                                                                <?= $model['titulo'] ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tipo">Tipo de Pergunta <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="tipo" name="tipo" required>
                                                        <option value="">Selecione o tipo</option>
                                                        <option value="texto" <?= (isset($this->dataForm['tipo']) && $this->dataForm['tipo'] == 'texto') ? 'selected' : '' ?>>Texto</option>
                                                        <option value="multipla_escolha" <?= (isset($this->dataForm['tipo']) && $this->dataForm['tipo'] == 'multipla_escolha') ? 'selected' : '' ?>>Múltipla Escolha</option>
                                                        <option value="verdadeiro_falso" <?= (isset($this->dataForm['tipo']) && $this->dataForm['tipo'] == 'verdadeiro_falso') ? 'selected' : '' ?>>Verdadeiro/Falso</option>
                                                        <option value="numerica" <?= (isset($this->dataForm['tipo']) && $this->dataForm['tipo'] == 'numerica') ? 'selected' : '' ?>>Numérica</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="pergunta">Pergunta <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="pergunta" name="pergunta" rows="3" 
                                                      placeholder="Digite a pergunta..." required><?= $this->dataForm['pergunta'] ?? '' ?></textarea>
                                            <small class="form-text text-muted">Mínimo 10 caracteres</small>
                                        </div>

                                        <div class="form-group" id="opcoes-group" style="display: none;">
                                            <label for="opcoes">Opções de Resposta <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="opcoes" name="opcoes" rows="4" 
                                                      placeholder="Digite as opções, uma por linha..."><?= $this->dataForm['opcoes'] ?? '' ?></textarea>
                                            <small class="form-text text-muted">Digite uma opção por linha. Exemplo:<br>
                                                A) Primeira opção<br>
                                                B) Segunda opção<br>
                                                C) Terceira opção</small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="ordem">Ordem</label>
                                                    <input type="number" class="form-control" id="ordem" name="ordem" 
                                                           value="<?= $this->dataForm['ordem'] ?? '1' ?>" min="1">
                                                    <small class="form-text text-muted">Ordem de exibição da pergunta (opcional)</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" name="SendAddQuestion" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Cadastrar Pergunta
                                            </button>
                                            <a href="<?= $urlAdm ?>list-evaluation-questions/index" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Voltar
                                            </a>
                                        </div>

                                    </form>

                                </div>
                            </div>

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

    <?php include_once "app/adms/Views/partials/logout_modal.php"; ?>

    <?php include_once "app/adms/Views/partials/scripts.php"; ?>

    <script>
        // Mostrar/ocultar campo de opções baseado no tipo selecionado
        document.getElementById('tipo').addEventListener('change', function() {
            const opcoesGroup = document.getElementById('opcoes-group');
            const opcoesField = document.getElementById('opcoes');
            
            if (this.value === 'multipla_escolha') {
                opcoesGroup.style.display = 'block';
                opcoesField.required = true;
            } else {
                opcoesGroup.style.display = 'none';
                opcoesField.required = false;
            }
        });

        // Executar no carregamento da página para verificar o tipo já selecionado
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipo');
            if (tipoSelect.value === 'multipla_escolha') {
                document.getElementById('opcoes-group').style.display = 'block';
                document.getElementById('opcoes').required = true;
            }
        });
    </script>

</body>

</html> 