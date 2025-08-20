<?php
$urlAdm = $_ENV['URL_ADM'];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Resposta de Avaliação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $urlAdm ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $urlAdm ?>list-evaluation-answers" class="text-decoration-none">Respostas de Avaliação</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Visualizar Resposta</span>
            <span class="ms-auto">
                <?php if (in_array('ListEvaluationAnswers', $this->data['buttonPermission'])) { ?>
                    <a href="<?= $urlAdm ?>list-evaluation-answers" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Voltar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdateEvaluationAnswer', $this->data['buttonPermission'])) { ?>
                    <a href="<?= $urlAdm ?>update-evaluation-answer/<?= $this->data['answer']['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-pencil"></i> Editar
                    </a>
                <?php } ?>
                <?php if (in_array('DeleteEvaluationAnswer', $this->data['buttonPermission'])) { ?>
                    <a href="<?= $urlAdm ?>delete-evaluation-answer/<?= $this->data['answer']['id'] ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Tem certeza que deseja excluir esta resposta?')">
                        <i class="mdi mdi-delete"></i> Excluir
                    </a>
                <?php } ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ID:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['id'] ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status:</label>
                        <p class="form-control-plaintext">
                            <?php if ($this->data['answer']['status'] == 1) { ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php } else { ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php } ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuário:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['user_name'] ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Modelo de Avaliação:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['model_name'] ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pergunta:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['question_text'] ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resposta:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['resposta'] ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pontuação:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['pontuacao'] ?? 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Data de Criação:</label>
                        <p class="form-control-plaintext">
                            <?= date('d/m/Y H:i:s', strtotime($this->data['answer']['created_at'])) ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Última Atualização:</label>
                        <p class="form-control-plaintext">
                            <?= $this->data['answer']['updated_at'] ? date('d/m/Y H:i:s', strtotime($this->data['answer']['updated_at'])) : 'Nunca' ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if (!empty($this->data['answer']['comentario'])) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Comentário:</label>
                        <p class="form-control-plaintext"><?= $this->data['answer']['comentario'] ?></p>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div> 