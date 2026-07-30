<?php

declare(strict_types=1);

/**
 * Envio público de documentos de pré-admissão (token).
 *
 * @var array<string, mixed>|null $oferta
 * @var list<array<string, mixed>> $documentos
 * @var string $token
 * @var string $csrf_token
 * @var string|null $error
 * @var string|null $success
 * @var string|null $expires_at
 * @var string $url_adm
 */
$erro = $error ?? null;
$sucesso = $success ?? null;
$o = is_array($oferta ?? null) ? $oferta : null;
$docs = is_array($documentos ?? null) ? $documentos : [];
$token = (string) ($token ?? '');
$csrf = (string) ($csrf_token ?? '');
$base = rtrim((string) ($url_adm ?? ($_ENV['URL_ADM'] ?? '')), '/');
$expires = $expires_at ?? null;

$statusBadge = static function (string $st): string {
    return match ($st) {
        'aprovado' => 'vp-badge vp-badge-ok',
        'recebido' => 'vp-badge',
        'recusado' => 'vp-badge vp-badge-danger',
        default => 'vp-badge vp-badge-muted',
    };
};
?>
<?php if ($sucesso): ?>
    <div class="vp-alert vp-alert-ok"><?= htmlspecialchars((string) $sucesso, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($erro || $o === null): ?>
    <div class="vp-card">
        <div class="vp-alert vp-alert-warn mb-0"><?= htmlspecialchars((string) ($erro ?: 'Link inválido ou expirado.'), ENT_QUOTES, 'UTF-8') ?></div>
        <p class="vp-meta mb-0 mt-3">Solicite um novo link ao recrutador se o prazo tiver terminado.</p>
    </div>
<?php else: ?>
    <article class="vp-card">
        <div class="vp-label-caps">Candidato</div>
        <h2 class="mb-1"><?= htmlspecialchars((string) ($o['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="vp-meta mb-0">
            Vaga: <?= htmlspecialchars((string) ($o['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <?php if ($expires): ?>
                · Link válido até <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $expires)), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </p>
        <p class="vp-meta mt-2 mb-0">Formatos aceitos: PDF, JPG, PNG ou DOC/DOCX (até 10 MB).</p>
    </article>

    <?php if ($docs === []): ?>
        <div class="vp-card">
            <p class="vp-meta mb-0">Nenhum documento solicitado neste momento.</p>
        </div>
    <?php else: ?>
        <?php foreach ($docs as $doc):
            $docId = (int) ($doc['id'] ?? 0);
            $temArquivo = !empty($doc['arquivo_caminho']);
            $status = (string) ($doc['status'] ?? 'pendente');
            $fileInputId = 'arquivo_' . $docId;
            $nameSpanId = 'nome_' . $docId;
            $cardExtra = match (true) {
                $status === 'aprovado' => ' vp-card-aprovado',
                $status === 'recusado' => ' vp-card-recusado',
                $temArquivo || $status === 'recebido' => ' vp-card-recebido',
                default => '',
            };
            $cardStyle = match (true) {
                $status === 'aprovado' => 'background:#d8f3df;border-color:#8fd4a4;border-left:4px solid #198754;',
                $status === 'recusado' => 'background:#f8d7da;border-color:#f1aeb5;border-left:4px solid #dc3545;',
                $temArquivo || $status === 'recebido' => 'background:#d9f2f8;border-color:#7ec8d9;border-left:4px solid #0dcaf0;',
                default => '',
            };
            ?>
            <article class="vp-card<?= $cardExtra ?>"<?= $cardStyle !== '' ? ' style="' . $cardStyle . '"' : '' ?>>
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="mb-1"><?= htmlspecialchars((string) ($doc['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="vp-badge <?= !empty($doc['obrigatorio']) ? '' : 'vp-badge-muted' ?>">
                            <?= !empty($doc['obrigatorio']) ? 'Obrigatório' : 'Opcional' ?>
                        </span>
                    </div>
                    <span class="<?= $statusBadge($status) ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <?php if ($temArquivo): ?>
                    <p class="vp-sent">
                        <i class="fas fa-check-circle me-1"></i>
                        Enviado: <?= htmlspecialchars((string) ($doc['arquivo_nome_original'] ?? 'arquivo'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($status !== 'aprovado'): ?>
                    <form method="post"
                          enctype="multipart/form-data"
                          action="<?= htmlspecialchars($base . '/pre-admissao-documentos?token=' . rawurlencode($token), ENT_QUOTES, 'UTF-8') ?>"
                          class="vp-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="documento_id" value="<?= $docId ?>">
                        <div class="vp-file-row">
                            <input type="file"
                                   name="arquivo"
                                   id="<?= $fileInputId ?>"
                                   class="d-none"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/*"
                                   required
                                   onchange="document.getElementById('<?= $nameSpanId ?>').textContent = this.files && this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado'">
                            <label for="<?= $fileInputId ?>" class="vp-btn-outline mb-0">
                                <i class="fas fa-paperclip me-1"></i>Escolher arquivo
                            </label>
                            <span class="vp-file-name" id="<?= $nameSpanId ?>">Nenhum arquivo selecionado</span>
                            <button type="submit" class="btn btn-sm vp-btn-primary">
                                <?= $temArquivo ? 'Substituir' : 'Enviar' ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
