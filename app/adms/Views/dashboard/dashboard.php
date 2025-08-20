<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">
            <div class="bg-success bg-gradient rounded-4 p-4 mb-4" style="margin-top: 2rem;">
                <h2 class="fw-bold text-white mb-1" style="font-size: 1.7rem; letter-spacing: -1px;">Bem-vindo(a), <?php echo htmlspecialchars($this->data['user_name'] ?? 'Usuário'); ?>!</h2>
                <div class="text-white" style="font-size: 1.05rem;">Portal Interno da Tiaraju - Sua central de informações corporativas</div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="row g-3 justify-content-center align-items-stretch">
                <div class="col-12 col-md-4 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-newspaper fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Informativos</h5>
                            <div class="text-muted mb-2 text-center" style="font-size: 1.1rem;"><?php echo $this->data['informativos_ativos'] ?? 0; ?> ativos</div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos?urgente=1" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: #fff7f7;">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-danger text-center group-title">Informativos Urgentes</h5>
                            <div class="text-danger mb-2 text-center" style="font-size: 1.1rem;"><?php echo $this->data['informativos_urgentes'] ?? 0; ?> urgente(s)</div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-stretch">
                    <a href="#" class="text-decoration-none flex-fill h-100" data-bs-toggle="modal" data-bs-target="#modalAniversariantesMes">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-birthday-cake fa-3x text-warning"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Aniversariantes do mês</h5>
                            <div class="text-muted mb-2 text-center" style="font-size: 1.1rem;">
                                <?php echo $this->data['qtd_aniversariantes_mes'] ?? 0; ?> este mês
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de aniversariantes do mês -->
    <div class="modal fade" id="modalAniversariantesMes" tabindex="-1" aria-labelledby="modalAniversariantesMesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAniversariantesMesLabel">Aniversariantes do Mês</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php foreach ($this->data['aniversariantes_mes'] ?? [] as $aniv): ?>
                            <div class="col-12 col-md-6 col-lg-4 d-flex">
                                <div class="card border-0 shadow-sm text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center" style="border-radius: 18px; min-height: 180px;">
                                    <div class="mb-2">
                                        <?php if (!empty($aniv['image'])): ?>
                                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($aniv['image']); ?>" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aniv['name']); ?>&background=ececec&color=6c757d&size=100" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($aniv['name']); ?></h6>
                                    <div class="text-muted small mb-1"><?php echo htmlspecialchars($aniv['departamento'] ?? ''); ?></div>
                                    <div class="text-muted small mt-1"><i class="fas fa-birthday-cake text-warning me-1"></i><span class="fw-bold" style="color:#ff9800;"> <?php echo $aniv['aniversario']; ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Informativos Recentes -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <h5 class="fw-bold mb-3 mt-2" style="color: #219150; text-align: left;">Informativos Recentes</h5>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="row g-4">
                <?php foreach (array_slice($this->data['informativos'] ?? [], 0, 6) as $info): ?>
                    <div class="col-12 col-md-6 col-lg-4 d-flex">
                        <div class="card border-0 shadow-sm p-4 flex-fill d-flex flex-column card-info" style="border-radius: 14px; min-height: 220px;">
                            <div class="d-flex align-items-center mb-2 gap-2 flex-wrap justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                    <span class="text-muted small"><?php echo date('d/m/Y', strtotime($info['created_at'])); ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-info text-white" style="font-size:0.95rem;"> <?php echo htmlspecialchars($info['categoria']); ?> </span>
                                    <?php if ($info['urgente']): ?><span class="badge bg-danger">Urgente</span><?php endif; ?>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1 text-start text-truncate" title="<?php echo htmlspecialchars($info['titulo']); ?>"><?php echo htmlspecialchars($info['titulo']); ?></h6>
                            <div class="text-muted mb-2 flex-grow-1 text-start" style="font-size: 1rem; min-height: 40px;">
                                <?php echo htmlspecialchars($info['resumo'] ?? substr(strip_tags($info['conteudo']), 0, 100) . '...'); ?>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <?php if (!empty($info['imagem'])): ?>
                                    <a href="#" onclick="showImageModal('<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>'); return false;">
                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>"
                                             class="img-fluid rounded shadow"
                                             alt="Imagem do informativo"
                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef; cursor: pointer;">
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($info['anexo'])): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['anexo']); ?>" target="_blank" title="Baixar anexo">
                                        <i class="fas fa-file-pdf fa-2x text-danger" style="vertical-align: middle;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="mt-auto text-end">
                                <button type="button" class="btn btn-outline-primary fw-semibold px-4" style="border-radius: 8px; border-width:2px; min-width: 120px;" data-bs-toggle="modal" data-bs-target="#informativoModal<?php echo $info['id']; ?>">
                                    <i class="fas fa-eye me-1"></i>Ver Mais
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Modal para cada informativo -->
                    <div class="modal fade" id="informativoModal<?php echo $info['id']; ?>" tabindex="-1" aria-labelledby="informativoModalLabel<?php echo $info['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content" style="border-radius: 12px; border: none;">
                                <div class="modal-header" style="border-radius: 12px 12px 0 0; border-bottom: 1px solid #e9ecef;">
                                    <h5 class="modal-title" id="informativoModalLabel<?php echo $info['id']; ?>">
                                        <?php echo htmlspecialchars($info['titulo']); ?>
                                        <?php if ($info['urgente']): ?>
                                            <span class="badge bg-danger ms-2" style="border-radius: 8px;">Urgente</span>
                                        <?php endif; ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="informativo-card">
                                        <div class="informativo-conteudo" id="conteudo-<?php echo $info['id']; ?>">
                                            <?php echo nl2br(htmlspecialchars($info['conteudo'])); ?>
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <?php if (!empty($info['imagem'])): ?>
                                                <a href="#" onclick="showImageModal('<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>'); return false;">
                                                    <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>"
                                                         class="img-fluid rounded shadow"
                                                         alt="Imagem do informativo"
                                                         style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef; cursor: pointer;">
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($info['anexo'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['anexo']); ?>" target="_blank" title="Baixar anexo">
                                                    <i class="fas fa-file-pdf fa-2x text-danger" style="vertical-align: middle;"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn btn-outline-primary w-100 mt-2" id="verMais-<?php echo $info['id']; ?>" style="display:none;" onclick="abrirModalInformativo(<?php echo $info['id']; ?>)">Ver Mais</button>
                                    </div>
                                    <div class="mb-3">
                                        <span class="badge bg-info" style="border-radius: 8px;"><?php echo htmlspecialchars($info['categoria']); ?></span>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($info['usuario_nome'] ?? 'N/A'); ?>
                                            <span class="ms-2">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($info['created_at'])); ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-radius: 0 0 12px 12px; border-top: 1px solid #e9ecef;">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Próximos Aniversários -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <h5 class="fw-bold mb-3 mt-2" style="color: #219150; text-align: left;">Próximos Aniversários</h5>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="row g-4">
                <?php foreach ($this->data['aniversariantes_mes'] ?? [] as $aniv): ?>
                    <div class="col-12 col-md-6 col-lg-4 d-flex">
                        <div class="card border-0 shadow-sm text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center" style="border-radius: 18px; min-height: 180px;">
                            <div class="mb-2">
                                <?php if (!empty($aniv['image'])): ?>
                                    <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($aniv['image']); ?>" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aniv['name']); ?>&background=ececec&color=6c757d&size=100" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                                <?php endif; ?>
                            </div>
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($aniv['name']); ?></h6>
                            <div class="text-muted small mb-1"><?php echo htmlspecialchars($aniv['departamento'] ?? ''); ?></div>
                            <div class="text-muted small mt-1"><i class="fas fa-birthday-cake text-warning me-1"></i><span class="fw-bold" style="color:#ff9800;"> <?php echo $aniv['aniversario']; ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 18px !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border: none;
}
.card-main {
    transition: box-shadow 0.2s, border 0.2s;
    min-height: 110px;
}
.card-main:hover, .card-main:focus-within {
    box-shadow: 0 8px 25px rgba(0,123,255,0.10);
    border: 2px solid #0d6efd22;
}
.icon-main {
    background: #f8f9fa;
    border-radius: 50%;
    min-width: 60px;
    min-height: 60px;
}
.card-info {
    border: none !important;
    box-shadow: 0 2px 12px rgba(0,123,255,0.07) !important;
    transition: border 0.2s, box-shadow 0.2s;
}
.card-info:hover, .card-info:focus-within {
    border: 2px solid #0d6efd !important;
    box-shadow: 0 8px 25px rgba(0,123,255,0.10) !important;
}
.btn-outline-primary {
    color: #0d6efd;
    border-color: #0d6efd;
    background: #fff;
}
.btn-outline-primary:hover, .btn-outline-primary:focus {
    background: #eaf4ff;
    color: #0a58ca;
    border-color: #0a58ca;
}
.badge.bg-info {
    background: #0dcaf0 !important;
    color: #fff !important;
}
.group-title {
    font-size: 1.25rem;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-main {
    min-height: 160px !important;
}
.card-info, .card, .card.border-0, .card.shadow-sm {
    border: 1px solid #e3e8ee !important;
    border-radius: 16px !important;
    box-shadow: none !important;
}
.dashboard-card, .dashboard-card-clickable {
    min-height: 170px !important;
    border-radius: 16px !important;
    border: 1px solid #e3e8ee !important;
    box-shadow: none !important;
    transition: box-shadow 0.2s, border-color 0.2s;
    background: #fff;
}
.dashboard-card:hover, .dashboard-card-clickable:hover {
    box-shadow: 0 4px 18px rgba(33, 145, 80, 0.10) !important;
    border-color: #219150 !important;
    cursor: pointer;
}
.card-info:hover, .card:hover, .card.border-0:hover, .card.shadow-sm:hover {
    box-shadow: 0 4px 18px rgba(33, 145, 80, 0.10) !important;
    border-color: #219150 !important;
    cursor: pointer;
}
.informativo-conteudo {
    max-height: 80px;
    overflow: hidden;
    position: relative;
}
</style>
<!-- Modal para ampliar imagem -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="Imagem ampliada" style="max-width: 100%; max-height: 70vh;">
      </div>
    </div>
  </div>
</div>
<script>
function showImageModal(src) {
    document.getElementById('modalImage').src = src;
    var modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>