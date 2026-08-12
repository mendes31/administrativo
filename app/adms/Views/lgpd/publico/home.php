<?php
/** @var string $empresa @var string $dpo_nome @var string $dpo_email @var string $dpo_telefone @var string $url_adm @var string $base_url @var list<array<string,mixed>> $comite_membros @var string $comite_titulo @var string $comite_descricao */
$h = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="lgpd-pub-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-warning"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <h2 class="lgpd-pub-hero">Lei Geral de Proteção de Dados Pessoais (LGPD)</h2>
    <p class="mb-0" style="color:#4b5563;max-width:46rem;">
        A <?php echo $h($empresa); ?> está comprometida com a proteção, a segurança e a privacidade
        dos dados pessoais, nos termos da Lei 13.709/2018.
    </p>

    <div class="lgpd-dpo">
        <h2>DPO — Data Protection Officer / Encarregado de Proteção de Dados</h2>
        <p class="mb-2">
            Em atenção à Lei 13.709/2018, artigo 41, está nomeado o encarregado de proteção de dados
            como canal oficial para dúvidas e exercício dos direitos do titular.
        </p>
        <?php if ($dpo_nome === '' && $dpo_email === ''): ?>
            <p class="mb-0 small text-muted">
                Os dados de contacto do encarregado serão publicados aqui em breve.
                Enquanto isso, use o formulário de requisição de dados pessoais.
            </p>
        <?php else: ?>
            <?php
            $emailLimpo = filter_var($dpo_email, FILTER_SANITIZE_EMAIL);
            $telDigitos = preg_replace('/\D+/', '', $dpo_telefone) ?? '';
            ?>
            <ul class="list-unstyled mb-0">
                <?php if ($dpo_nome !== ''): ?>
                    <li><strong>Nome:</strong> <?php echo $h($dpo_nome); ?></li>
                <?php endif; ?>
                <?php if ($emailLimpo !== ''): ?>
                    <li>
                        <strong>E-mail:</strong>
                        <a href="mailto:<?php echo $h($emailLimpo); ?>"><?php echo $h($emailLimpo); ?></a>
                    </li>
                <?php endif; ?>
                <?php if ($telDigitos !== ''): ?>
                    <li>
                        <strong>Telefone:</strong>
                        <a href="tel:+<?php echo $h($telDigitos); ?>"><?php echo $h($dpo_telefone); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if (!empty($comite_membros)): ?>
    <div class="lgpd-comite mt-4">
        <h2><?php echo $h($comite_titulo ?? 'Comitê de Privacidade e Proteção de Dados'); ?></h2>
        <?php if (!empty($comite_descricao)): ?>
            <p class="mb-3" style="color:#4b5563;"><?php echo nl2br($h($comite_descricao)); ?></p>
        <?php endif; ?>
        <div class="lgpd-comite-grid">
            <?php foreach ($comite_membros as $membro): ?>
                <?php
                $nomeMembro = trim((string) ($membro['nome'] ?? ''));
                if ($nomeMembro === '') {
                    continue;
                }
                $cargoMembro = trim((string) ($membro['cargo'] ?? ''));
                $emailMembro = filter_var((string) ($membro['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $telMembro = trim((string) ($membro['telefone'] ?? ''));
                $telDigitosMembro = preg_replace('/\D+/', '', $telMembro) ?? '';
                ?>
                <div class="lgpd-comite-card">
                    <strong><?php echo $h($nomeMembro); ?></strong>
                    <?php if ($cargoMembro !== ''): ?>
                        <div class="lgpd-comite-cargo"><?php echo $h($cargoMembro); ?></div>
                    <?php endif; ?>
                    <?php if ($emailMembro !== ''): ?>
                        <div><a href="mailto:<?php echo $h($emailMembro); ?>"><?php echo $h($emailMembro); ?></a></div>
                    <?php endif; ?>
                    <?php if ($telDigitosMembro !== ''): ?>
                        <div><a href="tel:+<?php echo $h($telDigitosMembro); ?>"><?php echo $h($telMembro); ?></a></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="lgpd-pub-cards">
        <?php foreach (($termos_publicos ?? []) as $termoPub): ?>
            <?php
            $slugPub = trim((string) ($termoPub['slug_publico'] ?? ''));
            $tituloPub = trim((string) ($termoPub['titulo'] ?? ''));
            if ($slugPub === '' || $tituloPub === '') {
                continue;
            }
            $icon = str_contains(mb_strtolower($tituloPub), 'pol') ? 'fa-file-circle-check' : 'fa-scroll';
            ?>
            <a class="lgpd-pub-tile" href="<?php echo $h($base_url); ?>/<?php echo $h($slugPub); ?>">
                <i class="fas <?php echo $icon; ?>"></i>
                <?php echo $h($tituloPub); ?>
            </a>
        <?php endforeach; ?>
        <?php if (!empty($has_carta)): ?>
            <a class="lgpd-pub-tile" href="<?php echo $h($base_url); ?>/documento?tipo=carta">
                <i class="fas fa-file-signature"></i>
                Carta de Compromisso
            </a>
        <?php endif; ?>
        <a class="lgpd-pub-tile" href="<?php echo $h($base_url); ?>/requisicao">
            <i class="fas fa-file-lines"></i>
            Formulário Requisição de Dados Pessoais
        </a>
        <?php if (!empty($has_cartilha)): ?>
            <a class="lgpd-pub-tile" href="<?php echo $h($base_url); ?>/documento?tipo=cartilha">
                <i class="fas fa-book-open"></i>
                Cartilha
            </a>
        <?php endif; ?>
    </div>
</div>
