<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\RhOfertasRepository;

$o = $this->data['oferta'] ?? [];
$docs = $this->data['documentos'] ?? [];
$canManage = !empty($this->data['can_manage']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$ofertaId = (int) ($o['id'] ?? 0);
$status = (string) ($o['status'] ?? '');
$baseAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
$candidatoId = (int) ($o['rh_candidato_id'] ?? 0);
$perms = $this->data['buttonPermission'] ?? [];
$canConvert = in_array('RhOfertasConvert', $perms, true);
$canDownload = in_array('RhPreAdmissaoDownloadDoc', $perms, true);

$statusBadge = match ($status) {
    'rascunho' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    'enviada' => 'bg-info-subtle text-info border border-info-subtle',
    'aceita' => 'bg-success-subtle text-success border border-success-subtle',
    'recusada' => 'bg-danger-subtle text-danger border border-danger-subtle',
    'cancelada' => 'bg-dark-subtle text-dark border border-dark-subtle',
    default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
};

$docStatusBadge = static function (string $st): string {
    return match ($st) {
        'aprovado' => 'bg-success-subtle text-success border border-success-subtle',
        'recebido' => 'bg-info-subtle text-info border border-info-subtle',
        'recusado' => 'bg-danger-subtle text-danger border border-danger-subtle',
        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    };
};

$docsTotal = count($docs);
$docsAprovados = 0;
$docsComArquivo = 0;
foreach ($docs as $dCount) {
    if (($dCount['status'] ?? '') === 'aprovado') {
        $docsAprovados++;
    }
    if (!empty($dCount['arquivo_caminho'])) {
        $docsComArquivo++;
    }
}
$docsPct = $docsTotal > 0 ? (int) round(($docsAprovados / $docsTotal) * 100) : 0;

$docsUrl = $this->data['docs_request_url'] ?? null;
$docsExpires = $o['docs_request_expires_at'] ?? null;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3 mb-0">Oferta #<?= $ofertaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto flex-shrink-0">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($baseAdm . '/rh-candidatos-view/' . $candidatoId, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Candidato</a>
            </li>
            <li class="breadcrumb-item active">Oferta</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0f9ff 45%, #f8fafc 100%);">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:3.25rem;height:3.25rem;">
                        <i class="fas fa-handshake fa-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                            <span class="badge rounded-pill text-bg-light border text-muted">Oferta #<?= $ofertaId ?></span>
                            <span class="badge rounded-pill <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <h3 class="h4 mb-1 text-truncate"><?= htmlspecialchars((string) ($o['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="text-muted small">
                            <i class="fas fa-briefcase me-1"></i><?= htmlspecialchars((string) ($o['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary btn-sm"
                       href="<?= htmlspecialchars($baseAdm . '/rh-candidatos-view/' . $candidatoId, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao candidato
                    </a>
                    <?php if (empty($o['rh_conversao_id']) && $canManage && $status === RhOfertasRepository::STATUS_ACEITA && $canConvert): ?>
                        <a class="btn btn-primary btn-sm"
                           href="<?= htmlspecialchars($baseAdm . '/rh-ofertas-convert/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fas fa-user-check me-1"></i>Converter em colaborador
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 border p-3 h-100">
                        <div class="small text-muted mb-1">Salário</div>
                        <div class="fw-semibold">
                            <?php if ($o['salario_oferecido'] !== null && $o['salario_oferecido'] !== ''): ?>
                                R$ <?= htmlspecialchars(number_format((float) $o['salario_oferecido'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 border p-3 h-100">
                        <div class="small text-muted mb-1">Contrato</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($o['tipo_contrato'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 border p-3 h-100">
                        <div class="small text-muted mb-1">Início previsto</div>
                        <div class="fw-semibold"><?= htmlspecialchars(FormatHelper::formatDate($o['data_inicio_prevista'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 border p-3 h-100">
                        <div class="small text-muted mb-1">Validade</div>
                        <div class="fw-semibold"><?= htmlspecialchars(FormatHelper::formatDate($o['validade_ate'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="vstack gap-4 mb-4">
        <div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h3 class="h6 mb-0"><i class="fas fa-file-contract me-2 text-success"></i>Detalhes da oferta</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($o['observacoes'])): ?>
                        <div class="mb-3">
                            <div class="small text-muted text-uppercase fw-semibold mb-1">Condições</div>
                            <div class="small"><?= nl2br(htmlspecialchars((string) $o['observacoes'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($o['resposta_observacoes'])): ?>
                        <div class="mb-3">
                            <div class="small text-muted text-uppercase fw-semibold mb-1">Resposta</div>
                            <div class="small"><?= nl2br(htmlspecialchars((string) $o['resposta_observacoes'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>

                    <p class="small text-muted mb-0">
                        Controle interno do RH: o recrutador registra envio/aceite/recusa manualmente.
                        O aceite inicia a pré-admissão e <strong>não</strong> cria vínculo — isso só ocorre na conversão.
                    </p>

                    <?php if (!empty($o['rh_conversao_id'])): ?>
                        <div class="alert alert-success mt-3 mb-0 small">
                            Oferta convertida (conversão #<?= (int) $o['rh_conversao_id'] ?>).
                            <?php if (!empty($this->data['conversao']['adms_user_id'])): ?>
                                · Usuário
                                <a href="<?= htmlspecialchars($baseAdm . '/view-user/' . (int) $this->data['conversao']['adms_user_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    #<?= (int) $this->data['conversao']['adms_user_id'] ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($this->data['onboarding_plano']['id'])): ?>
                                · <a href="<?= htmlspecialchars($baseAdm . '/rh-onboarding-view/' . (int) $this->data['onboarding_plano']['id'], ENT_QUOTES, 'UTF-8') ?>">Onboarding</a>
                            <?php endif; ?>
                            <?php if (!empty($this->data['experiencia']['id'])): ?>
                                · <a href="<?= htmlspecialchars($baseAdm . '/rh-experiencia-view/' . (int) $this->data['experiencia']['id'], ENT_QUOTES, 'UTF-8') ?>">Experiência</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManage && in_array($status, [RhOfertasRepository::STATUS_RASCUNHO, RhOfertasRepository::STATUS_ENVIADA], true)): ?>
                        <hr class="my-3">
                        <form method="post" class="vstack gap-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <div>
                                <label class="form-label small mb-1" for="observacoes">Observações da ação</label>
                                <input type="text" class="form-control form-control-sm" name="observacoes" id="observacoes" maxlength="500" placeholder="Opcional">
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($status === RhOfertasRepository::STATUS_RASCUNHO): ?>
                                    <button type="submit" name="action" value="enviar" class="btn btn-outline-info btn-sm"
                                            title="Apenas status interno — não envia e-mail nem link ao candidato">
                                        <i class="fas fa-paper-plane me-1"></i>Marcar como enviada
                                    </button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="aceitar" class="btn btn-success btn-sm"
                                        onclick="return confirm('Registrar aceite (feito pelo RH) e iniciar pré-admissão?');">
                                    <i class="fas fa-check me-1"></i>Registrar aceite
                                </button>
                                <button type="submit" name="action" value="recusar" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Registrar recusa da oferta?');">
                                    Registrar recusa
                                </button>
                                <button type="submit" name="action" value="cancelar" class="btn btn-outline-secondary btn-sm"
                                        onclick="return confirm('Cancelar esta oferta?');">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($status === RhOfertasRepository::STATUS_ACEITA): ?>
            <div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="h6 mb-1"><i class="fas fa-folder-open me-2 text-success"></i>Documentos de pré-admissão</h3>
                                <div class="small text-muted"><?= $docsAprovados ?> de <?= $docsTotal ?> aprovados · <?= $docsComArquivo ?> com arquivo</div>
                            </div>
                            <?php if ($canManage): ?>
                                <?php
                                $defaultDays = 14;
                                $inicioPrev = (string) ($o['data_inicio_prevista'] ?? '');
                                if ($inicioPrev !== '') {
                                    $inicioTs = strtotime($inicioPrev . ' 23:59:59');
                                    if ($inicioTs !== false && $inicioTs > time()) {
                                        $sugerido = (int) ceil(($inicioTs - time()) / 86400);
                                        if ($sugerido >= 1 && $sugerido <= 90) {
                                            $defaultDays = $sugerido;
                                        }
                                    }
                                }
                                ?>
                                <form method="post" class="d-flex flex-wrap align-items-center gap-2 m-0">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <label class="small text-muted mb-0" for="docs_valid_days">Validade</label>
                                    <div class="input-group input-group-sm" style="width:auto;">
                                        <input type="number" class="form-control" name="docs_valid_days" id="docs_valid_days"
                                               value="<?= $defaultDays ?>" min="1" max="90" step="1" required
                                               title="Dias de validade do link (1 a 90)"
                                               style="width:4.5rem;">
                                        <span class="input-group-text">dias</span>
                                    </div>
                                    <button type="submit" name="action" value="solicitar_documentos" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-link me-1"></i><?= $docsUrl ? 'Renovar link' : 'Gerar link' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if ($docsTotal > 0): ?>
                            <div class="progress mt-3" style="height:6px;" role="progressbar" aria-valuenow="<?= $docsPct ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-success" style="width:<?= $docsPct ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($docsUrl): ?>
                            <div class="rounded-3 border bg-light p-3 mb-3">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <i class="fas fa-link text-success mt-1"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold small">Link para o candidato</div>
                                        <div class="text-muted small">Envie por e-mail ou WhatsApp. Também é possível anexar manualmente abaixo.</div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="docsRequestUrl" readonly
                                           value="<?= htmlspecialchars((string) $docsUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="button" class="btn btn-outline-secondary" data-copy-target="docsRequestUrl">
                                        Copiar
                                    </button>
                                </div>
                                <?php if ($docsExpires): ?>
                                    <div class="small text-muted mt-2 mb-0">
                                        Válido até <?= htmlspecialchars(FormatHelper::formatDateTime($docsExpires) ?: (string) $docsExpires, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <script>
                            (function () {
                                function fallbackCopy(input) {
                                    input.focus();
                                    input.select();
                                    input.setSelectionRange(0, input.value.length);
                                    try {
                                        return document.execCommand('copy');
                                    } catch (e) {
                                        return false;
                                    }
                                }
                                document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                                    btn.addEventListener('click', function () {
                                        var id = btn.getAttribute('data-copy-target');
                                        var input = id ? document.getElementById(id) : null;
                                        if (!input) return;
                                        var text = input.value || '';
                                        var label = btn.textContent;
                                        function markCopied() {
                                            btn.textContent = 'Copiado!';
                                            setTimeout(function () { btn.textContent = label; }, 1500);
                                        }
                                        if (navigator.clipboard && window.isSecureContext) {
                                            navigator.clipboard.writeText(text).then(markCopied).catch(function () {
                                                if (fallbackCopy(input)) markCopied();
                                            });
                                        } else if (fallbackCopy(input)) {
                                            markCopied();
                                        }
                                    });
                                });
                            })();
                            </script>
                        <?php elseif ($canManage): ?>
                            <p class="small text-muted">Gere um link para o candidato enviar os arquivos, ou anexe manualmente em cada item.</p>
                        <?php endif; ?>

                        <?php if ($docs === []): ?>
                            <p class="text-muted mb-0">Nenhum documento na checklist.</p>
                        <?php else: ?>
                            <div class="vstack gap-2">
                                <?php foreach ($docs as $doc):
                                    $docId = (int) ($doc['id'] ?? 0);
                                    $docSt = (string) ($doc['status'] ?? 'pendente');
                                    $temArquivo = !empty($doc['arquivo_caminho']);
                                    $fileInputId = 'doc_file_' . $docId;
                                    $arquivoNome = (string) ($doc['arquivo_nome_original'] ?? 'arquivo');
                                    $via = (string) ($doc['uploaded_by'] ?? '');
                                    $rowClass = match (true) {
                                        $docSt === 'aprovado' => 'border-success-subtle bg-success-subtle',
                                        $docSt === 'recebido' && $temArquivo => 'border-info-subtle bg-info-subtle',
                                        $docSt === 'recusado' => 'border-danger-subtle bg-danger-subtle',
                                        default => 'border bg-white',
                                    };
                                    ?>
                                    <div class="rounded-3 px-3 py-2 border overflow-hidden <?= $rowClass ?>">
                                        <div class="d-flex flex-column flex-md-row flex-md-wrap align-items-stretch align-items-md-center gap-2">
                                            <div class="d-flex flex-wrap align-items-center gap-2 min-w-0 flex-md-grow-1">
                                                <span class="fw-semibold text-break" title="<?= htmlspecialchars((string) ($doc['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string) ($doc['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <?php if (!empty($doc['obrigatorio'])): ?>
                                                    <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle flex-shrink-0">Obrigatório</span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-light text-muted border flex-shrink-0">Opcional</span>
                                                <?php endif; ?>

                                                <span class="badge rounded-pill <?= $docStatusBadge($docSt) ?> flex-shrink-0">
                                                    <?= htmlspecialchars(ucfirst($docSt), ENT_QUOTES, 'UTF-8') ?>
                                                </span>

                                                <?php if ($canManage && $temArquivo && $docSt === 'recebido'): ?>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="action" value="documento">
                                                        <input type="hidden" name="documento_id" value="<?= $docId ?>">
                                                        <input type="hidden" name="documento_status" value="aprovado">
                                                        <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                                                    </form>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="action" value="documento">
                                                        <input type="hidden" name="documento_id" value="<?= $docId ?>">
                                                        <input type="hidden" name="documento_status" value="recusado">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Recusar</button>
                                                    </form>
                                                <?php elseif ($canManage && $temArquivo && in_array($docSt, ['aprovado', 'recusado'], true)): ?>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="action" value="documento">
                                                        <input type="hidden" name="documento_id" value="<?= $docId ?>">
                                                        <input type="hidden" name="documento_status" value="recebido">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Voltar para revisão">Reabrir</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-flex flex-wrap align-items-center gap-2 min-w-0 ms-md-auto">
                                                <div class="small min-w-0 flex-grow-1" style="max-width:100%;">
                                                    <?php if ($temArquivo): ?>
                                                        <?php if ($canDownload): ?>
                                                            <a class="text-decoration-none d-inline-block text-truncate"
                                                               style="max-width:100%;"
                                                               href="<?= htmlspecialchars($baseAdm . '/rh-pre-admissao-download-doc/' . $docId . '?oferta_id=' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>"
                                                               title="<?= htmlspecialchars($arquivoNome . ($via !== '' ? ' (via ' . $via . ')' : ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="fas fa-download me-1"></i><?= htmlspecialchars($arquivoNome, ENT_QUOTES, 'UTF-8') ?>
                                                                <?php if ($via !== ''): ?>
                                                                    <span class="text-muted">· <?= htmlspecialchars($via, ENT_QUOTES, 'UTF-8') ?></span>
                                                                <?php endif; ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="d-inline-block text-truncate" style="max-width:100%;" title="<?= htmlspecialchars($arquivoNome, ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="fas fa-paperclip me-1"></i><?= htmlspecialchars($arquivoNome, ENT_QUOTES, 'UTF-8') ?>
                                                                <?php if ($via !== ''): ?>
                                                                    <span class="text-muted">· <?= htmlspecialchars($via, ENT_QUOTES, 'UTF-8') ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fas fa-file me-1"></i>Sem arquivo</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($canManage): ?>
                                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                        <form method="post" enctype="multipart/form-data" class="m-0" id="form_upload_<?= $docId ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="action" value="upload_documento">
                                                            <input type="hidden" name="documento_id" value="<?= $docId ?>">
                                                            <input type="file" name="arquivo" id="<?= $fileInputId ?>" class="d-none"
                                                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                                   onchange="this.form.requestSubmit()">
                                                            <label for="<?= $fileInputId ?>" class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer;">
                                                                <i class="fas fa-upload me-1"></i><span class="d-none d-sm-inline"><?= $temArquivo ? 'Substituir' : 'Anexar' ?></span>
                                                            </label>
                                                        </form>
                                                        <?php if ($temArquivo): ?>
                                                            <form method="post" class="m-0"
                                                                  onsubmit="return confirm('Excluir o arquivo e voltar o documento para Pendente?');">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                                <input type="hidden" name="action" value="remover_documento">
                                                                <input type="hidden" name="documento_id" value="<?= $docId ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir arquivo">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($canManage): ?>
                                <p class="small text-muted mt-2 mb-0">
                                    Com arquivo, o status fica <em>Recebido</em> (bloco destacado). Use <strong>Aprovar</strong>/<strong>Recusar</strong> ou <strong>Excluir</strong>/<strong>Substituir</strong> o arquivo para alterar.
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
