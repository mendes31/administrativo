<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Categorias de Políticas Internas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item active">Categorias de Políticas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-tags me-2"></i>Categorias de Políticas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (!empty($this->data['buttonPermission']) && in_array('CreatePolicyCategory', $this->data['buttonPermission'], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-policy-category" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Cadastrar Categoria
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($this->data['categorias'])): ?>
                        <?php foreach ($this->data['categorias'] as $cat): ?>
                            <tr>
                                <td><?php echo (int) $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td>
                                    <?php if (!empty($cat['ativo'])): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (!empty($this->data['buttonPermission']) && in_array('UpdatePolicyCategory', $this->data['buttonPermission'], true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-policy-category/<?php echo (int) $cat['id']; ?>"
                                               class="btn btn-outline-warning"
                                               title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($this->data['buttonPermission']) && in_array('DeletePolicyCategory', $this->data['buttonPermission'], true)): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token"
                                                       value="<?php echo CSRFHelper::generateCSRFToken('form_delete_policy_category'); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int) $cat['id']; ?>">
                                                <button type="submit"
                                                        formaction="<?php echo $_ENV['URL_ADM']; ?>delete-policy-category"
                                                        class="btn btn-outline-danger"
                                                        onclick="return confirm('Tem certeza que deseja excluir esta categoria?');"
                                                        title="Excluir">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Nenhuma categoria cadastrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

