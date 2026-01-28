<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header bg-success text-white">
                    <h3 class="text-center font-weight-light my-2">Cadastrar Termo LGPD</h3>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_lgpd_termo'); ?>">

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Versão</label>
                                <input type="text" name="versao" class="form-control" placeholder="1.0"
                                       value="<?= htmlspecialchars($this->data['formData']['versao'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Título</label>
                                <input type="text" name="titulo" class="form-control"
                                       value="<?= htmlspecialchars($this->data['formData']['titulo'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <?php
                                    $tipo = $this->data['formData']['tipo'] ?? 'login';
                                    $tipos = ['login' => 'Login do Sistema', 'site' => 'Site/Portal', 'outro' => 'Outro'];
                                    foreach ($tipos as $k => $label):
                                    ?>
                                        <option value="<?= $k ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Início da Vigência</label>
                                <input type="datetime-local" name="data_inicio_vigencia" class="form-control"
                                       value="<?= htmlspecialchars($this->data['formData']['data_inicio_vigencia'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fim da Vigência (opcional)</label>
                                <input type="datetime-local" name="data_fim_vigencia" class="form-control"
                                       value="<?= htmlspecialchars($this->data['formData']['data_fim_vigencia'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Conteúdo do Termo (HTML ou texto)</label>
                                <textarea name="conteudo" rows="8" class="form-control" required><?= htmlspecialchars($this->data['formData']['conteudo'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php $status = $this->data['formData']['status'] ?? 'Ativo'; ?>
                                    <option value="Ativo" <?= $status === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="Inativo" <?= $status === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


