<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="mb-1 d-flex flex-column flex-md-row align-items-md-center gap-2">
                <h2 class="mt-3 fw-bold mb-0 mobile-hide-page-title">Cadastrar Categoria de Política</h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0 mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0 mobile-hide-breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item">Gestão de Pessoas</li>
                        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-policy-categories" class="text-decoration-none">Categorias de Políticas</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cadastrar</li>
                    </ol>
                </nav>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo CSRFHelper::generateCSRFToken('form_create_policy_category'); ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nome da categoria *</label>
                            <input type="text"
                                   class="form-control"
                                   id="name"
                                   name="name"
                                   required
                                   maxlength="120"
                                   value="<?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                   <?php echo (!isset($this->data['form']['ativo']) || !empty($this->data['form']['ativo'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">Ativa</label>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-policy-categories"
                               class="btn btn-outline-secondary">
                                Voltar
                            </a>
                            <button type="submit" class="btn btn-success">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

