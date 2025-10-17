<?php
// Verificar se os dados estão definidos
if (!isset($this->data['plan'])) {
    echo "Erro: Dados do plano não encontrados.";
    return;
}

$plan = $this->data['plan'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trash-alt"></i> Excluir Plano Estratégico
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Atenção!</h5>
                        Você está prestes a excluir o plano estratégico abaixo. Esta ação não pode ser desfeita.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Título:</label>
                            <div class="border rounded p-3 bg-light">
                                <?= htmlspecialchars($plan['title'] ?? 'Não informado') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status:</label>
                            <div class="border rounded p-3 bg-light">
                                <?= htmlspecialchars($plan['status'] ?? 'Não informado') ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Departamento:</label>
                            <div class="border rounded p-3 bg-light">
                                <?= htmlspecialchars($plan['dep_name'] ?? 'Não informado') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Responsável:</label>
                            <div class="border rounded p-3 bg-light">
                                <?= htmlspecialchars($plan['user_name'] ?? 'Não informado') ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrição:</label>
                            <div class="border rounded p-3 bg-light">
                                <?= htmlspecialchars($plan['description'] ?? 'Não informado') ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>delete-strategic-plan/<?= $plan['id'] ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt"></i> Confirmar Exclusão
                                </button>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



