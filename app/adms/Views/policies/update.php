<?php
use App\adms\Helpers\CSRFHelper;

/** @var array $policy */
$policy = $this->data['policy'] ?? [];
$notifyDeps = $this->data['notify_departments'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-1 d-flex flex-column flex-md-row align-items-md-center gap-2">
                <h2 class="mt-3 text-primary fw-bold mb-0" style="font-size: 1.7rem; letter-spacing: -1px;">
                    Editar Política Interna
                </h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="text-decoration-none">Políticas Internas</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Editar</li>
                    </ol>
                </nav>
            </div>

            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 18px; background: #fff;">
                <div class="card-body p-4">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <form method="POST" style="max-width: 700px; margin: 0 auto;">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('update_policy'); ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label fw-semibold">Título *</label>
                                <input type="text"
                                       class="form-control form-control-lg rounded-3"
                                       id="titulo"
                                       name="titulo"
                                       required
                                       maxlength="255"
                                       value="<?php echo htmlspecialchars($policy['titulo'] ?? ''); ?>"
                                       placeholder="Título da política interna">
                            </div>

                            <div class="col-md-6">
                                <label for="categoria_id" class="form-label fw-semibold">Categoria *</label>
                                <select class="form-select form-select-lg rounded-3"
                                        id="categoria_id"
                                        name="categoria_id"
                                        required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                        <?php
                                        $selected = ((int)($policy['categoria_id'] ?? 0) === (int)$categoria['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int) $categoria['id']; ?>" <?= $selected; ?>>
                                            <?= htmlspecialchars($categoria['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="categoria" id="categoria_nome_hidden" value="<?php echo htmlspecialchars($policy['categoria'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-semibold">Departamento responsável *</label>
                                <select class="form-select form-select-lg rounded-3"
                                        id="department_id"
                                        name="department_id"
                                        required>
                                    <option value="">Selecione o departamento</option>
                                    <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                        <?php
                                        $selected = ((int)($policy['department_id'] ?? 0) === (int)$dep['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int) $dep['id']; ?>" <?= $selected; ?>>
                                            <?= htmlspecialchars($dep['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="publish_at" class="form-label fw-semibold">Publicar em</label>
                                <input type="datetime-local"
                                       class="form-control form-control-lg rounded-3"
                                       id="publish_at"
                                       name="publish_at"
                                       value="<?php echo !empty($policy['publish_at']) ? date('Y-m-d\TH:i', strtotime($policy['publish_at'])) : ''; ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="expire_at" class="form-label fw-semibold">Expira em</label>
                                <input type="datetime-local"
                                       class="form-control form-control-lg rounded-3"
                                       id="expire_at"
                                       name="expire_at"
                                       value="<?php echo !empty($policy['expire_at']) ? date('Y-m-d\TH:i', strtotime($policy['expire_at'])) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="conteudo" class="form-label fw-semibold">Conteúdo da política *</label>
                            <textarea class="form-control form-control-lg rounded-3"
                                      id="conteudo"
                                      name="conteudo"
                                      rows="8"
                                      required
                                      placeholder="Descreva a política interna em detalhes..."><?php echo htmlspecialchars($policy['conteudo'] ?? ''); ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="urgente" name="urgente"
                                        <?php echo !empty($policy['urgente']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-danger" for="urgente">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                    </label>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_ack" name="requires_ack"
                                        <?php echo !empty($policy['requires_ack']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="requires_ack">
                                        Exigir ciência dos colaboradores
                                    </label>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                        <?php echo !empty($policy['ativo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-success" for="ativo">
                                        <i class="fas fa-check me-1"></i>Ativa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notificar" name="notificar"
                                    <?php echo !empty($policy['notificar']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="notificar">
                                    Enviar notificação via WhatsApp
                                </label>
                            </div>
                            <label class="form-label small mb-1">Departamentos a notificar (opcional)</label>
                            <div class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                    <?php
                                    $checked = in_array((int) $dep['id'], $notifyDeps, true) ? 'checked' : '';
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="notify_departments[]"
                                               value="<?= (int) $dep['id']; ?>"
                                               id="notify_dep_<?= (int) $dep['id']; ?>"
                                            <?= $checked; ?>>
                                        <label class="form-check-label"
                                               for="notify_dep_<?= (int) $dep['id']; ?>">
                                            <?= htmlspecialchars($dep['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text small mt-1">
                                Marque os setores que devem receber a notificação. Nenhum marcado = todos recebem.
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                               class="btn btn-outline-secondary btn-lg rounded-3 px-4">
                                Voltar
                            </a>

                            <button type="submit"
                                    class="btn btn-primary btn-lg rounded-3 px-4 fw-bold">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-control, .form-select {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    font-size: 1.05rem;
    min-height: 46px;
}
.form-label {
    color: #3a3a3a;
    font-size: 1.02rem;
}
.btn-primary {
    background: #0d6efd;
    border: none;
}
.btn-primary:hover {
    background: #0b5ed7;
}
.btn-outline-secondary {
    border: 2px solid #e9ecef;
}
@media (max-width: 991.98px) {
    .card-body form { max-width: 100% !important; }
}
</style>

<script>
document.getElementById('categoria_id')?.addEventListener('change', function () {
    const sel   = this;
    const nome  = sel.options[sel.selectedIndex]?.text || '';
    const hidden = document.getElementById('categoria_nome_hidden');
    if (hidden) hidden.value = nome;
});
</script>

