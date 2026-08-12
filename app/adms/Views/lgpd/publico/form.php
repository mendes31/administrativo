<?php
/** @var array $rights @var array $categorias @var array $old @var string $csrf_token @var string $captcha_question @var string $base_url */
$old = is_array($old ?? null) ? $old : [];
$oldDireitos = is_array($old['direitos'] ?? null) ? $old['direitos'] : [];
$h = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="lgpd-pub-card">
    <p class="mb-2">
        <a href="<?php echo $h($base_url); ?>" class="text-decoration-none" style="color:#0a5b30;">&larr; Voltar</a>
    </p>
    <h1 class="lgpd-pub-hero">Requisição de Direitos do Titular de Dados Pessoais</h1>
    <p>
        Declaração de privacidade: a <?php echo $h($empresa); ?> está comprometida com a proteção,
        segurança e privacidade dos dados pessoais. Este formulário, fundamentado no Art. 18 da LGPD,
        facilita o exercício dos seus direitos. O uso é facultativo, mas é uma forma eficiente e segura
        de processar o seu pleito.
    </p>
    <p class="small text-muted">Os campos com * são obrigatórios.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo $h($base_url); ?>/requisicao" class="text-start" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="lgpd-hp" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <h2 class="h5 mt-3" style="color:#0a5b30;">Identificação do titular</h2>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="titular_nome">Nome completo *</label>
                <input class="form-control" id="titular_nome" name="titular_nome" required
                       value="<?php echo $h($old['titular_nome'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="titular_cpf">CPF *</label>
                <input class="form-control" id="titular_cpf" name="titular_cpf" required inputmode="numeric"
                       value="<?php echo $h($old['titular_cpf'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="titular_email">E-mail de contacto *</label>
                <input class="form-control" id="titular_email" name="titular_email" type="email" required
                       value="<?php echo $h($old['titular_email'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="titular_telefone">Telefone</label>
                <input class="form-control" id="titular_telefone" name="titular_telefone"
                       value="<?php echo $h($old['titular_telefone'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="titular_nascimento">Data de nascimento</label>
                <input class="form-control" id="titular_nascimento" name="titular_nascimento" type="date"
                       value="<?php echo $h($old['titular_nascimento'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="titular_categoria">Categoria *</label>
                <select class="form-select" id="titular_categoria" name="titular_categoria" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $h($cat); ?>" <?php echo (($old['titular_categoria'] ?? '') === $cat) ? 'selected' : ''; ?>>
                            <?php echo $h($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12" id="wrap-categoria-outro" style="<?php echo (($old['titular_categoria'] ?? '') === 'Outro') ? '' : 'display:none'; ?>">
                <label class="form-label" for="titular_categoria_outro">Outro (descreva)</label>
                <input class="form-control" id="titular_categoria_outro" name="titular_categoria_outro"
                       value="<?php echo $h($old['titular_categoria_outro'] ?? ''); ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="titular_endereco">Endereço</label>
                <textarea class="form-control" id="titular_endereco" name="titular_endereco" rows="2"><?php echo $h($old['titular_endereco'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="informacoes_adicionais">Informações adicionais (para localizar os seus dados)</label>
                <textarea class="form-control" id="informacoes_adicionais" name="informacoes_adicionais" rows="3"><?php echo $h($old['informacoes_adicionais'] ?? ''); ?></textarea>
            </div>
        </div>

        <h2 class="h5 mt-4" style="color:#0a5b30;">Procurador</h2>
        <p class="small mb-2">Solicitação por meio de procurador? *</p>
        <?php $pp = (string) ($old['por_procurador'] ?? ''); ?>
        <div class="mb-2">
            <label class="me-3"><input type="radio" name="por_procurador" value="sim" <?php echo $pp === 'sim' ? 'checked' : ''; ?>> Sim</label>
            <label><input type="radio" name="por_procurador" value="nao" <?php echo $pp === 'nao' ? 'checked' : ''; ?>> Não</label>
        </div>
        <div id="wrap-procurador" class="row g-2 mb-3" style="<?php echo $pp === 'sim' ? '' : 'display:none'; ?>">
            <div class="col-md-4">
                <label class="form-label" for="procurador_nome">Nome do procurador</label>
                <input class="form-control" id="procurador_nome" name="procurador_nome" value="<?php echo $h($old['procurador_nome'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="procurador_cpf">CPF do procurador</label>
                <input class="form-control" id="procurador_cpf" name="procurador_cpf" value="<?php echo $h($old['procurador_cpf'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="procurador_email">E-mail do procurador</label>
                <input class="form-control" id="procurador_email" name="procurador_email" type="email" value="<?php echo $h($old['procurador_email'] ?? ''); ?>">
            </div>
        </div>

        <h2 class="h5 mt-3" style="color:#0a5b30;">Direitos (Art. 18 e Art. 20)</h2>
        <p class="small text-muted">Assinale Sim apenas nos direitos que deseja exercer. Pelo menos um Sim.</p>
        <?php foreach ($rights as $key => $meta): ?>
            <?php $val = (string) ($oldDireitos[$key] ?? 'nao'); ?>
            <div class="lgpd-right">
                <div class="fw-semibold"><?php echo $h($meta['titulo']); ?> <span class="small text-muted">(<?php echo $h($meta['artigo']); ?>)</span></div>
                <p class="small mb-2 text-muted"><?php echo $h($meta['ajuda']); ?></p>
                <label class="me-3"><input type="radio" name="direitos[<?php echo $h($key); ?>]" value="sim" <?php echo $val === 'sim' ? 'checked' : ''; ?>> Sim</label>
                <label><input type="radio" name="direitos[<?php echo $h($key); ?>]" value="nao" <?php echo $val !== 'sim' ? 'checked' : ''; ?>> Não</label>
            </div>
        <?php endforeach; ?>

        <h2 class="h5 mt-3" style="color:#0a5b30;">Comunicação do resultado *</h2>
        <p class="small">A <?php echo $h($empresa); ?> poderá contactá-lo para esclarecimentos. Como deseja ser comunicado?</p>
        <?php $cm = (string) ($old['comunicacao_meio'] ?? 'email'); ?>
        <label class="d-block mb-1">
            <input type="radio" name="comunicacao_meio" value="email" <?php echo $cm !== 'outro' ? 'checked' : ''; ?>>
            Meio eletrônico (e-mail): o mesmo preenchido para o titular ou procurador
        </label>
        <label class="d-block mb-2">
            <input type="radio" name="comunicacao_meio" value="outro" <?php echo $cm === 'outro' ? 'checked' : ''; ?>> Outro
        </label>
        <div id="wrap-comunicacao-outro" style="<?php echo $cm === 'outro' ? '' : 'display:none'; ?>">
            <input class="form-control mb-3" name="comunicacao_outro" placeholder="Outro:" value="<?php echo $h($old['comunicacao_outro'] ?? ''); ?>">
        </div>

        <label class="form-label" for="captcha"><?php echo $h($captcha_question); ?> *</label>
        <input class="form-control mb-3" id="captcha" name="captcha" required inputmode="numeric" autocomplete="off" style="max-width:12rem;">

        <button type="submit" class="btn btn-lgpd">Submeter</button>
    </form>
</div>
<script>
(function () {
    const cat = document.getElementById('titular_categoria');
    const wrapCat = document.getElementById('wrap-categoria-outro');
    const wrapProc = document.getElementById('wrap-procurador');
    const wrapCom = document.getElementById('wrap-comunicacao-outro');
    function sync() {
        if (wrapCat) wrapCat.style.display = cat && cat.value === 'Outro' ? '' : 'none';
        const proc = document.querySelector('input[name="por_procurador"]:checked');
        if (wrapProc) wrapProc.style.display = proc && proc.value === 'sim' ? '' : 'none';
        const com = document.querySelector('input[name="comunicacao_meio"]:checked');
        if (wrapCom) wrapCom.style.display = com && com.value === 'outro' ? '' : 'none';
    }
    document.addEventListener('change', sync);
    sync();
})();
</script>
