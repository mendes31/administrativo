<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header bg-warning text-white">
                    <h3 class="text-center font-weight-light my-2">Editar Termo LGPD</h3>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    <form method="POST" action="">
                        <?php $csrfKey = $this->data['csrf_key'] ?? 'form_edit_lgpd_termo'; ?>
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken($csrfKey); ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($this->data['form']['id'] ?? '') ?>">

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Versão</label>
                                <input type="text" name="versao" class="form-control"
                                       value="<?= htmlspecialchars($this->data['form']['versao'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Título</label>
                                <input type="text" name="titulo" class="form-control"
                                       value="<?= htmlspecialchars($this->data['form']['titulo'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <?php
                                    $tipo = $this->data['form']['tipo'] ?? 'login';
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
                                       value="<?= htmlspecialchars($this->data['form']['data_inicio_vigencia'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fim da Vigência (opcional)</label>
                                <input type="datetime-local" name="data_fim_vigencia" class="form-control"
                                       value="<?= htmlspecialchars($this->data['form']['data_fim_vigencia'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Conteúdo do Termo (HTML ou texto)</label>
                                <textarea id="conteudo_termo" name="conteudo" rows="12" class="form-control"><?= $this->data['form']['conteudo'] ?? '' ?></textarea>
                                <small class="text-muted">
                                    Você pode formatar o texto (títulos, listas, negrito, etc.).
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php $status = $this->data['form']['status'] ?? 'Ativo'; ?>
                                    <option value="Ativo" <?= $status === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="Inativo" <?= $status === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label d-block">Canal público LGPD</label>
                                <?php $pub = !empty($this->data['form']['publico_canal']); ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="publico_canal" id="publico_canal" value="1"
                                           <?= $pub ? 'checked' : '' ?>
                                           onchange="document.getElementById('slug_publico_wrap').style.display = this.checked ? '' : 'none';">
                                    <label class="form-check-label" for="publico_canal">
                                        Publicar no canal público (<code>/lgpd/{slug}</code>)
                                    </label>
                                </div>
                                <div id="slug_publico_wrap" class="mt-2" style="<?= $pub ? '' : 'display:none' ?>">
                                    <label class="form-label" for="slug_publico">Slug da URL</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">/lgpd/</span>
                                        <input type="text" name="slug_publico" id="slug_publico" class="form-control"
                                               placeholder="politica"
                                               value="<?= htmlspecialchars($this->data['form']['slug_publico'] ?? '') ?>">
                                    </div>
                                    <small class="text-muted">
                                        Só documentos marcados aqui ficam públicos. Ex.: <code>politica</code>, <code>termos</code>.
                                        Termos de uso do sistema normalmente <strong>não</strong> devem ser publicados.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-warning">Salvar alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Editor WYSIWYG para o conteúdo do termo -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#conteudo_termo',
    menubar: false,
    branding: false,
    statusbar: true,
    plugins: 'lists link code',
    toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | outdent indent | removeformat | link | code',
    height: 420,
    language: 'pt_BR',
    language_url: "<?php echo $_ENV['URL_ADM']; ?>public/js/tinymce/langs/pt_BR.js",
});
</script>
