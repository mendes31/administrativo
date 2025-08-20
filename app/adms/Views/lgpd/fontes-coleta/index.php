<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Fontes de Coleta LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Fontes de Coleta</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php if (in_array('LgpdFontesColetaCreate', $this->data['buttonPermission'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta-create" class="btn btn-success btn-sm me-1 mb-1">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['fontes'])): ?>
                            <?php foreach ($this->data['fontes'] as $fonte): ?>
                                <tr>
                                    <td><?php echo $fonte['id']; ?></td>
                                    <td><?php echo htmlspecialchars($fonte['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($fonte['descricao'] ?: 'Não informado'); ?></td>
                                    <td>
                                        <?php if ($fonte['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (in_array('LgpdFontesColetaEdit', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta-edit/<?php echo $fonte['id']; ?>" class="btn btn-warning btn-sm me-1">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array('LgpdFontesColetaDelete', $this->data['buttonPermission'])): ?>
                                            <form id="formDelete<?php echo $fonte['id']; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta-delete" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_delete_fonte_coleta'); ?>">
                                                <input type="hidden" name="id" value="<?php echo $fonte['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="confirmDeletion(event, <?php echo $fonte['id']; ?>)">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhuma fonte de coleta encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeletion(event, id) {
    if (!confirm('Tem certeza que deseja excluir esta fonte de coleta?')) {
        event.preventDefault();
    }
}
</script> 