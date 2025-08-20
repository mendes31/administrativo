<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_evaluation_model');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Modelo de Avaliação</h2>

        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="text-decoration-none">Modelos de Avaliação</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Detalhes do Modelo</span>

            <span class="ms-auto">
                <?php
                if (in_array('UpdateEvaluationModel', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-evaluation-model/{$this->data['form']['id']}' class='btn btn-warning btn-sm me-2'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
                }

                if (in_array('DeleteEvaluationModel', $this->data['buttonPermission'])) {
                ?>
                    <form id="formDelete<?php echo $this->data['form']['id']; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-evaluation-model" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" value="<?php echo $this->data['form']['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="confirmDeletion(event, <?php echo $this->data['form']['id']; ?>)">
                            <i class="fa-regular fa-trash-can"></i> Apagar
                        </button>
                    </form>
                <?php } ?>
            </span>
        </div>

        <div class="card-body">
            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">ID:</label>
                    <p class="form-control-plaintext"><?php echo $this->data['form']['id']; ?></p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Status:</label>
                    <p class="form-control-plaintext">
                        <?php if ($this->data['form']['ativo'] == 1): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Título:</label>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($this->data['form']['titulo']); ?></p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Descrição:</label>
                <p class="form-control-plaintext">
                    <?php echo htmlspecialchars($this->data['form']['descricao'] ?? 'Nenhuma descrição fornecida'); ?>
                </p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Treinamento:</label>
                <p class="form-control-plaintext">
                    <?php echo htmlspecialchars($this->data['form']['training_name'] ?? 'N/A'); ?>
                </p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Criado em:</label>
                    <p class="form-control-plaintext">
                        <?php echo date('d/m/Y H:i:s', strtotime($this->data['form']['created_at'])); ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Atualizado em:</label>
                    <p class="form-control-plaintext">
                        <?php echo date('d/m/Y H:i:s', strtotime($this->data['form']['updated_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-evaluation-models" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
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