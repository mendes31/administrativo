<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserFormHelper;

$vistoria = $this->data['vistoria'] ?? [];
$respostas = $this->data['respostas'] ?? [];
$anexos = $this->data['anexos'] ?? [];
$naoConformidades = $this->data['nao_conformidades'] ?? [];
$readonly = !empty($this->data['readonly']);
$perms = $this->data['buttonPermission'] ?? [];
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamento_vistoria');
$id = (int) ($vistoria['id'] ?? 0);
$fromQr = isset($_GET['from']) && $_GET['from'] === 'qr';
$assinaturaEm = $vistoria['assinatura_confirmada_em'] ?? null;
$filialLabel = UserFormHelper::empresaContratanteLabel($vistoria['empresa_contratante'] ?? null);
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$podePdf = $readonly && in_array('SstExportEquipamentoVistoriaPdf', $perms, true);
$fromQs = $fromQr ? '?from=qr' : '';
?>
<div class="container-fluid px-3 px-md-4 pb-5 pb-md-3">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php if ($fromQr && !$readonly): ?>
    <div class="alert alert-info py-2 small"><i class="fas fa-qrcode me-1"></i>Equipamento identificado via QR Code. Preencha o checklist, anexe fotos e confirme a declaração para concluir.</div>
    <?php endif; ?>

    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0 fs-4">
            <i class="fas fa-clipboard-check me-2"></i>Vistoria <?= htmlspecialchars($vistoria['competencia'] ?? '') ?>
        </h2>
        <ol class="breadcrumb mb-0 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias">Minhas vistorias</a></li>
            <li class="breadcrumb-item active">Executar</li>
        </ol>
    </div>

    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3">
            <div class="row g-3 small">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-muted">Equipamento</div>
                    <div class="fw-semibold"><?= htmlspecialchars($vistoria['equipamento_codigo'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-muted">Grupo</div>
                    <div class="fw-semibold"><?= htmlspecialchars($vistoria['tipo_nome'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-muted">Filial</div>
                    <div class="fw-semibold"><?= htmlspecialchars($filialLabel) ?></div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="text-muted">Local</div>
                    <div class="fw-semibold"><?= htmlspecialchars($vistoria['localizacao'] ?? '—') ?></div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="text-muted">Status</div>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($vistoria['status'] ?? '') ?>
                        <?php if (!empty($vistoria['resultado'])): ?>
                            / <?= htmlspecialchars($vistoria['resultado']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($podePdf): ?>
            <div class="mt-3">
                <a href="<?= htmlspecialchars($urlAdm) ?>sst-export-equipamento-vistoria-pdf/<?= $id ?>"
                   class="btn btn-outline-danger btn-sm" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf me-1"></i>Documento para impressão (PDF)
                </a>
            </div>
            <?php endif; ?>
            <?php if ($readonly && ($vistoria['resultado'] ?? '') === 'Não conforme'): ?>
            <div class="mt-3">
                <?php if ($naoConformidades === []): ?>
                <div class="alert alert-warning py-2 small mb-0">
                    Resultado <strong>Não conforme</strong>. As NCs serão geradas automaticamente; atualize a página se não aparecerem.
                </div>
                <?php else: ?>
                <div class="alert alert-warning py-2 small mb-0">
                    <div class="fw-semibold mb-1">Resultado histórico: Não conforme</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($naoConformidades as $ncRow): ?>
                        <li>
                            <a href="<?= htmlspecialchars($urlAdm) ?>sst-view-equipamento-nao-conformidade/<?= (int) $ncRow['id'] ?>">
                                <?= htmlspecialchars($ncRow['codigo'] ?? 'NC') ?>
                            </a>
                            — <?= htmlspecialchars($ncRow['descricao'] ?? '') ?>
                            · <strong><?= htmlspecialchars($ncRow['status'] ?? '') ?></strong>
                            <?php if (($ncRow['status'] ?? '') === 'Encerrada' && !empty($ncRow['acao_encerramento_codigo'])): ?>
                            <span class="text-success">
                                (encerrada mediante <?= htmlspecialchars($ncRow['acao_encerramento_codigo']) ?>)
                            </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-1 text-muted">A vistoria não muda para Conforme após a correção — o tratamento fica na NC/ação corretiva.</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-camera"></i> Fotos da vistoria
            <span class="badge bg-secondary"><?= count($anexos) ?></span>
        </div>
        <div class="card-body px-2 px-md-3">
            <?php if ($anexos !== []): ?>
            <div class="row g-2 mb-3">
                <?php foreach ($anexos as $anexo): ?>
                <?php
                    $aid = (int) ($anexo['id'] ?? 0);
                    $quando = !empty($anexo['created_at'])
                        ? date('d/m/Y H:i', strtotime((string) $anexo['created_at']))
                        : '—';
                    $viewUrl = htmlspecialchars($urlAdm) . 'sst-view-anexo/' . $aid;
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border shadow-sm">
                        <a href="<?= $viewUrl ?>" target="_blank" rel="noopener" class="ratio ratio-1x1 bg-light">
                            <img src="<?= $viewUrl ?>" alt="<?= htmlspecialchars($anexo['file_name'] ?? 'Foto') ?>"
                                 class="object-fit-cover rounded-top" style="object-fit:cover;width:100%;height:100%">
                        </a>
                        <div class="card-body p-2 small">
                            <div class="fw-semibold text-truncate" title="<?= htmlspecialchars($anexo['file_name'] ?? '') ?>">
                                <?= htmlspecialchars($anexo['file_name'] ?? 'Foto') ?>
                            </div>
                            <div class="text-muted"><i class="far fa-clock me-1"></i><?= htmlspecialchars($quando) ?></div>
                            <?php if (!empty($anexo['uploaded_by_name'])): ?>
                            <div class="text-muted text-truncate"><?= htmlspecialchars($anexo['uploaded_by_name']) ?></div>
                            <?php endif; ?>
                            <?php if (!$readonly): ?>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" form="form-vistoria-fotos" name="delete_anexos[]" value="<?= $aid ?>" id="del_foto_<?= $aid ?>">
                                <label class="form-check-label text-danger" for="del_foto_<?= $aid ?>">Excluir ao salvar</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="small text-muted mb-3">Nenhuma foto anexada ainda.</p>
            <?php endif; ?>

            <?php if (!$readonly): ?>
            <form method="POST" action="<?= htmlspecialchars($urlAdm) ?>sst-execute-equipamento-vistoria/<?= $id ?><?= htmlspecialchars($fromQs) ?>"
                  enctype="multipart/form-data" class="border rounded p-3 bg-light" id="form-vistoria-fotos">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="form_action" value="upload_fotos">
                <label class="form-label fw-semibold" for="fotos_rapido">
                    <i class="fas fa-camera me-1"></i>Adicionar fotos (câmera ou galeria)
                </label>
                <input type="file" name="fotos[]" id="fotos_rapido" class="form-control mb-2" accept="image/*" capture="environment" multiple>
                <div class="form-text mb-2">Cada foto é salva com data e hora do envio (JPG, PNG, GIF, WEBP — máx. 10 MB). Marque “Excluir ao salvar” nas fotos que deseja remover.</div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt me-1"></i>Salvar fotos
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">
            <?= $readonly ? 'Checklist registrado' : 'Checklist de inspeção' ?>
        </div>
        <div class="card-body px-2 px-md-3">
            <form method="POST" action="<?= htmlspecialchars($urlAdm) ?>sst-execute-equipamento-vistoria/<?= $id ?><?= htmlspecialchars($fromQs) ?>"
                  id="form-vistoria-execute" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="form_action" value="conclude">

                <div class="vistoria-checklist mb-3">
                    <?php foreach ($respostas as $idx => $r): ?>
                    <?php
                        $naoConforme = ($r['resposta'] ?? '') === 'Não conforme';
                        $itemId = (int) $r['id'];
                    ?>
                    <div class="card mb-2 <?= $naoConforme ? 'border-danger' : 'border-light' ?> shadow-sm">
                        <div class="card-body p-3">
                            <div class="row g-2 g-md-3 align-items-md-center">
                                <div class="col-12 col-md-5">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge bg-secondary flex-shrink-0"><?= $idx + 1 ?></span>
                                        <div class="fw-semibold small lh-sm"><?= htmlspecialchars($r['descricao_snapshot'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <?php if ($readonly): ?>
                                        <div class="small">
                                            <span class="text-muted">Resposta:</span>
                                            <span class="fw-semibold <?= $naoConforme ? 'text-danger' : '' ?>"><?= htmlspecialchars($r['resposta'] ?? '-') ?></span>
                                        </div>
                                    <?php else: ?>
                                        <label class="form-label small mb-1 d-md-none" for="resp_<?= $itemId ?>">Resposta</label>
                                        <select name="resposta[<?= $itemId ?>]" id="resp_<?= $itemId ?>" class="form-select" required>
                                            <option value="">Selecione</option>
                                            <?php foreach (['Conforme', 'Não conforme', 'N/A'] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= ($r['resposta'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-4">
                                    <?php if ($readonly): ?>
                                        <?php if (($r['observacao'] ?? '') !== ''): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($r['observacao']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <label class="form-label small mb-1 d-md-none" for="obs_<?= $itemId ?>">Observação</label>
                                        <input type="text" name="obs_item[<?= $itemId ?>]" id="obs_<?= $itemId ?>" class="form-control" value="<?= htmlspecialchars($r['observacao'] ?? '') ?>" placeholder="Observação (opcional)">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mb-3 px-1 px-md-0">
                    <label class="form-label" for="observacao">Observação geral</label>
                    <?php if ($readonly): ?>
                        <p class="form-control-plaintext mb-0"><?= nl2br(htmlspecialchars($vistoria['observacao'] ?? '-')) ?></p>
                    <?php else: ?>
                        <textarea name="observacao" id="observacao" class="form-control" rows="3"><?= htmlspecialchars($vistoria['observacao'] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>

                <?php if (!$readonly): ?>
                <div class="mb-3 px-1 px-md-0">
                    <label class="form-label" for="fotos_conclude"><i class="fas fa-images me-1"></i>Anexar mais fotos ao concluir (opcional)</label>
                    <input type="file" name="fotos[]" id="fotos_conclude" class="form-control" accept="image/*" capture="environment" multiple>
                </div>
                <?php endif; ?>

                <?php if ($readonly && !empty($assinaturaEm)): ?>
                <div class="alert alert-light border small mb-3">
                    <strong>Assinatura eletrônica:</strong>
                    confirmada em <?= date('d/m/Y H:i', strtotime((string) $assinaturaEm)) ?>
                    <?php if (!empty($vistoria['executor_nome'])): ?>
                    por <?= htmlspecialchars($vistoria['executor_nome']) ?>
                    <?php endif; ?>
                    <?php if (!empty($vistoria['assinatura_ip'])): ?>
                    · IP <?= htmlspecialchars($vistoria['assinatura_ip']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$readonly): ?>
                <div class="card bg-light border mb-3">
                    <div class="card-body py-3">
                        <p class="small mb-2">
                            Ao concluir, declaro ter realizado a inspeção física deste equipamento conforme o checklist acima,
                            registrando fielmente as condições observadas. O sistema armazena data/hora, usuário, IP e navegador.
                        </p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="aceite_assinatura" id="aceite_assinatura" value="1" required style="width:1.25em;height:1.25em">
                            <label class="form-check-label ms-1" for="aceite_assinatura">
                                Confirmo a realização da vistoria e a veracidade das informações registradas.
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-none d-md-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Concluir e assinar vistoria</button>
                    <a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias" class="btn btn-secondary">Voltar</a>
                </div>

                <div class="d-md-none vistoria-mobile-actions">
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-2">
                        <i class="fas fa-check me-1"></i>Concluir e assinar
                    </button>
                    <a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias" class="btn btn-outline-secondary w-100">Voltar</a>
                </div>
                <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($podePdf): ?>
                    <a href="<?= htmlspecialchars($urlAdm) ?>sst-export-equipamento-vistoria-pdf/<?= $id ?>"
                       class="btn btn-danger" target="_blank" rel="noopener">
                        <i class="fas fa-print me-1"></i>Imprimir / PDF
                    </a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias" class="btn btn-secondary">Voltar</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<style>
@media (max-width: 767.98px) {
    .vistoria-mobile-actions {
        position: sticky;
        bottom: 0;
        z-index: 10;
        margin: 0 -0.5rem -0.5rem;
        padding: 0.75rem 0.5rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
        background: linear-gradient(to top, #fff 70%, rgba(255,255,255,0.92));
        border-top: 1px solid rgba(0,0,0,.08);
    }
    .vistoria-checklist .form-select,
    .vistoria-checklist .form-control {
        min-height: 2.75rem;
        font-size: 1rem;
    }
}
</style>
