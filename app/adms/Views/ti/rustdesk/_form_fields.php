<?php

$form = $this->data['form'] ?? [];
$users = $this->data['users'] ?? [];
$isEdit = !empty($this->data['is_edit']);
$encryptionOk = !empty($this->data['encryption_ok']);
$hasSenha = !empty($form['has_senha']);
?>
<div class="col-12 col-md-6">
    <label for="alias" class="form-label mb-1">Apelido *</label>
    <input type="text" name="alias" id="alias" class="form-control form-control-sm" required maxlength="180"
           value="<?= htmlspecialchars((string) ($form['alias'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="ex.: emanueli.muller@xafra36">
    <div class="form-text">Nome amigável da máquina (como aparece no RustDesk).</div>
</div>
<div class="col-12 col-md-6">
    <label for="rustdesk_id" class="form-label mb-1">ID RustDesk *</label>
    <input type="text" name="rustdesk_id" id="rustdesk_id" class="form-control form-control-sm" required maxlength="32"
           inputmode="numeric" autocomplete="off"
           value="<?= htmlspecialchars((string) ($form['rustdesk_id_fmt'] ?? $form['rustdesk_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="ex.: 1 554 351 509">
    <div class="form-text">Somente números; espaços são ignorados.</div>
</div>
<div class="col-12 col-md-6">
    <label for="adms_user_id" class="form-label mb-1">Colaborador</label>
    <select name="adms_user_id" id="adms_user_id" class="form-select form-select-sm">
        <option value="">— nenhum —</option>
        <?php
        $selectedUser = (int) ($form['adms_user_id'] ?? 0);
        foreach ($users as $u):
            $uid = (int) ($u['id'] ?? 0);
            ?>
            <option value="<?= $uid ?>" <?= $uid === $selectedUser ? 'selected' : '' ?>>
                <?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($u['email'])): ?>
                    (<?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8') ?>)
                <?php endif; ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-md-6">
    <label for="status" class="form-label mb-1">Status</label>
    <select name="status" id="status" class="form-select form-select-sm">
        <?php $st = (string) ($form['status'] ?? 'ativo'); ?>
        <option value="ativo" <?= $st === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo" <?= $st === 'inativo' ? 'selected' : '' ?>>Inativo</option>
    </select>
</div>
<div class="col-12 col-md-6">
    <label for="senha" class="form-label mb-1">Senha do RustDesk <?= $isEdit ? '' : '' ?></label>
    <div class="input-group input-group-sm">
        <input type="password" name="senha" id="senha" class="form-control" maxlength="120"
               autocomplete="new-password"
               <?= $encryptionOk ? '' : 'disabled' ?>
               placeholder="<?= $isEdit && $hasSenha ? 'Deixe em branco para manter a senha atual' : 'Opcional' ?>">
        <button type="button" class="btn btn-outline-secondary" id="tiRdToggleSenha" title="Mostrar/ocultar o que você digitou"
                <?= $encryptionOk ? '' : 'disabled' ?>>
            <i class="fa-regular fa-eye"></i>
        </button>
    </div>
    <?php if (!$encryptionOk): ?>
        <div class="form-text text-danger">Defina <code>TI_RUSTDESK_ENCRYPTION_KEY</code> no .env para gravar senhas.</div>
    <?php elseif ($isEdit && $hasSenha): ?>
        <div class="form-text">Já existe senha criptografada. Preencha só se quiser substituí-la.</div>
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="limpar_senha" value="1" id="limpar_senha">
            <label class="form-check-label" for="limpar_senha">Remover a senha cadastrada</label>
        </div>
    <?php else: ?>
        <div class="form-text">A senha é gravada criptografada (AES-256-GCM). Nunca aparece na listagem.</div>
    <?php endif; ?>
</div>
<div class="col-12">
    <label for="observacoes" class="form-label mb-1">Observações</label>
    <textarea name="observacoes" id="observacoes" class="form-control form-control-sm" rows="3"
              maxlength="2000"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('tiRdToggleSenha');
    var input = document.getElementById('senha');
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
        var hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        btn.innerHTML = hidden ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
    });
});
</script>
