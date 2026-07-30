<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;

$csrfTokenStatus = CSRFHelper::generateCSRFToken('form_rh_atualizar_status_candidatura');
$csrfTokenVinculoAjax = CSRFHelper::generateCSRFToken('form_rh_vincular_candidato_vaga');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes da Vaga</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="text-decoration-none">Vagas</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-briefcase me-2"></i>Vaga #<?= (int)($this->data['vaga']['id'] ?? 0) ?></span>
            <div class="d-flex flex-wrap gap-1">
                <?php if (!empty($this->data['can_manage_pipeline'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Vincular Candidatos
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-pipeline/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-project-diagram me-1"></i>Pipeline
                    </a>
                <?php endif; ?>
                <?php if (!empty($this->data['buttonPermission']['RhVagasEdit'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-edit/<?= (int)$this->data['vaga']['id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php $v = $this->data['vaga'] ?? []; ?>

            <div class="d-flex flex-wrap gap-1 gap-sm-2 mb-3">
                <?php
                $statusClass = match($v['status'] ?? '') {
                    'aberta'   => 'badge bg-success',
                    'pausada'  => 'badge bg-warning text-dark',
                    'fechada'  => 'badge bg-secondary',
                    'cancelada'=> 'badge bg-danger',
                    default    => 'badge bg-secondary',
                };
                ?>
                <span class="<?= $statusClass ?>"><?= htmlspecialchars(ucfirst($v['status'] ?? '')) ?></span>
                <?php if (!empty($v['publicada'])): ?>
                    <span class="badge bg-primary">Publicada (portal)</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark border">Não publicada</span>
                <?php endif; ?>
                <?php
                $visBadge = \App\adms\Models\Services\RhVagaDivulgacaoService::normalizeVisibilidade($v['visibilidade'] ?? 'externa');
                $visBadgeClass = match ($visBadge) {
                    'interna' => 'bg-warning text-dark',
                    'ambas' => 'bg-info text-dark',
                    default => 'bg-secondary',
                };
                ?>
                <span class="badge <?= $visBadgeClass ?>"><?= htmlspecialchars(\App\adms\Models\Services\RhVagaDivulgacaoService::labelVisibilidade($visBadge)) ?></span>
                <?php if (!empty($v['area_nome'])): ?>
                    <span class="badge bg-info"><?= htmlspecialchars($v['area_nome']) ?></span>
                <?php endif; ?>
            </div>

            <h4 class="h5 mb-3"><?= htmlspecialchars($v['titulo'] ?? '') ?></h4>

            <?php
            $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float) $v['salario_min'], 2, ',', '.') : '';
            $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float) $v['salario_max'], 2, ',', '.') : '';
            $faixaSal = '-';
            if ($salMin && $salMax) {
                $faixaSal = $salMin . ' – ' . $salMax;
            } elseif ($salMin) {
                $faixaSal = 'A partir de ' . $salMin;
            } elseif ($salMax) {
                $faixaSal = 'Até ' . $salMax;
            }
            if ($faixaSal !== '-' && empty($v['mostrar_salario'])) {
                $faixaSal .= ' (não divulgado)';
            }
            $infoCards = [
                ['label' => 'Área', 'value' => (string) ($v['area_nome'] ?? '-')],
                ['label' => 'Cargo', 'value' => \App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) ($v['cargo_nome'] ?? '')) ?: '-'],
                ['label' => 'Contrato', 'value' => (string) ($v['tipo_contrato'] ?? '-')],
                ['label' => 'Faixa salarial', 'value' => $faixaSal],
                ['label' => 'Qtd. vagas', 'value' => (string) ((int) ($v['quantidade_vagas'] ?? 1))],
                ['label' => 'Local', 'value' => (string) ($v['local_trabalho'] ?? '-')],
                ['label' => 'Jornada', 'value' => (string) ($v['jornada_trabalho'] ?? '-')],
                ['label' => 'Responsável', 'value' => (string) ($v['responsavel_nome'] ?? '-')],
            ];
            $dataCards = [
                ['label' => 'Abertura', 'value' => FormatHelper::formatDateTime($v['data_abertura'] ?? null) ?: '—'],
                ['label' => 'Limite inscrição', 'value' => FormatHelper::formatDateTime($v['data_limite_inscricao'] ?? null) ?: '—'],
                ['label' => 'Fechamento', 'value' => FormatHelper::formatDateTime($v['data_fechamento'] ?? null) ?: '—'],
                ['label' => 'Divulgação', 'value' => \App\adms\Models\Services\RhVagaDivulgacaoService::labelVisibilidade((string) ($v['visibilidade'] ?? 'externa'))],
            ];
            ?>

            <h5 class="h6 text-muted text-uppercase small mb-2">Informações da vaga</h5>
            <div class="row g-2 mb-3">
                <?php foreach ($infoCards as $card): ?>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 bg-light px-2 py-2 h-100">
                            <div class="small text-muted"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fw-semibold small text-break"><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h5 class="h6 text-muted text-uppercase small mb-2">Datas e status</h5>
            <div class="row g-2 mb-3">
                <?php foreach ($dataCards as $card): ?>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 bg-light px-2 py-2 h-100">
                            <div class="small text-muted"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fw-semibold small text-break"><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12 col-lg-6">
                    <div class="border rounded-3 bg-light px-2 py-2 h-100">
                        <div class="small text-muted">Publicação (portal)</div>
                        <div class="fw-semibold small">
                            <?php if (!empty($v['publicada'])): ?>
                                Sim
                                <?php if (!empty($v['publicado_em'])): ?>
                                    <span class="text-muted fw-normal">(desde <?= htmlspecialchars(FormatHelper::formatDateTime($v['publicado_em']), ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                                <div class="text-muted fw-normal mt-1">Candidatura pública em <code>vagas-abertas/<?= (int) ($v['id'] ?? 0) ?></code>.</div>
                            <?php else: ?>
                                Não
                                <?php if (\App\adms\Models\Services\RhVagaDivulgacaoService::permiteDivulgacaoInterna((string) ($v['visibilidade'] ?? ''))): ?>
                                    <div class="text-muted fw-normal mt-1">Para o público interno, use Informativos no app.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="border rounded-3 bg-light px-2 py-2 h-100">
                        <div class="small text-muted">Status</div>
                        <div><span class="<?= $statusClass ?>"><?= htmlspecialchars(ucfirst($v['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></div>
                    </div>
                </div>
            </div>

            <?php
            $visNow = \App\adms\Models\Services\RhVagaDivulgacaoService::normalizeVisibilidade($v['visibilidade'] ?? 'externa');
            $showExternalLinks = !empty($v['publicada'])
                && \App\adms\Models\Services\RhVagaDivulgacaoService::permitePortalPublico($visNow);
            $showInternalHint = \App\adms\Models\Services\RhVagaDivulgacaoService::permiteDivulgacaoInterna($visNow);
            $canCreateInformativo = in_array('CreateInformativo', $this->data['buttonPermission'] ?? [], true);
            $linksExt = $showExternalLinks
                ? \App\adms\Models\Services\RhVagaDivulgacaoService::linksExternos((int) ($v['id'] ?? 0))
                : [];
            $informativoUrl = \App\adms\Models\Services\RhVagaDivulgacaoService::createInformativoUrl($v);
            ?>
            <?php if ($showExternalLinks || $showInternalHint): ?>
            <div class="card mb-4 border-light shadow overflow-hidden">
                <div class="card-header"><i class="fas fa-share-alt me-2"></i>Divulgação</div>
                <div class="card-body">
                    <?php if ($showExternalLinks): ?>
                        <p class="small text-muted mb-3">
                            Copie o link adequado para cada canal. Os parâmetros <code>utm_source</code> são gravados na candidatura
                            (Canal / Origem), para saber se veio do LinkedIn, site, redes ou informativo.
                            O formulário público exige CSRF, honeypot, LGPD
                            <?= !empty($this->data['captcha_hint']) ? ' e CAPTCHA' : '' ?>
                            (configurável em Portal de Vagas).
                        </p>
                        <div class="vstack gap-2 mb-3">
                            <?php foreach ($linksExt as $linkRow):
                                $canalId = htmlspecialchars((string) $linkRow['canal'], ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="border rounded-3 p-2 p-sm-3 bg-white overflow-hidden">
                                    <div class="fw-semibold small mb-2"><?= htmlspecialchars((string) $linkRow['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center min-w-0">
                                        <input type="text"
                                               class="form-control form-control-sm font-monospace min-w-0 flex-grow-1"
                                               id="share-url-<?= $canalId ?>"
                                               value="<?= htmlspecialchars((string) $linkRow['url'], ENT_QUOTES, 'UTF-8') ?>"
                                               readonly>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 flex-sm-grow-0"
                                                    data-copy-target="share-url-<?= $canalId ?>">
                                                Copiar
                                            </button>
                                            <a class="btn btn-sm btn-outline-primary flex-grow-1 flex-sm-grow-0"
                                               target="_blank" rel="noopener"
                                               href="<?= htmlspecialchars((string) $linkRow['url'], ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($visNow === 'interna'): ?>
                        <p class="small text-muted mb-2">
                            Esta vaga é <strong>só interna</strong>: não aparece em <code>vagas-abertas</code>.
                            Divulgue no app pelos Informativos (colaboradores logados, com push/ciência se quiser).
                        </p>
                    <?php endif; ?>

                    <?php if ($showInternalHint): ?>
                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                            <a href="<?= htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-internas/' . (int) ($v['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                Abrir no portal interno
                            </a>
                            <?php if ($canCreateInformativo): ?>
                                <a href="<?= htmlspecialchars($informativoUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-bullhorn me-1"></i>Criar informativo desta vaga
                                </a>
                            <?php else: ?>
                                <span class="small text-muted">
                                    Sem permissão <em>CreateInformativo</em> — peça a alguém de Comunicação/RH para publicar o anúncio no app.
                                </span>
                            <?php endif; ?>
                            <span class="small text-muted">
                                No informativo, restrinja a notificação aos departamentos desejados.
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            (function () {
                function fallbackCopy(input) {
                    input.focus();
                    input.select();
                    input.setSelectionRange(0, (input.value || '').length);
                    try { return document.execCommand('copy'); } catch (e) { return false; }
                }
                document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-copy-target');
                        var input = id ? document.getElementById(id) : null;
                        if (!input) return;
                        var text = input.value || '';
                        var label = btn.textContent;
                        function markCopied() {
                            btn.textContent = 'Copiado';
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
            <?php endif; ?>

            <?php if (!empty($v['descricao'])): ?>
                <div class="mb-3">
                    <h5 class="h6">Descrição</h5>
                    <div class="border rounded-3 p-3 bg-light small text-break">
                        <?= nl2br(htmlspecialchars($v['descricao'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['requisitos'])): ?>
                <div class="mb-3">
                    <h5 class="h6">Requisitos</h5>
                    <div class="border rounded-3 p-3 bg-light small text-break">
                        <?= nl2br(htmlspecialchars($v['requisitos'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['beneficios'])): ?>
                <div class="mb-3">
                    <h5 class="h6">Benefícios</h5>
                    <div class="border rounded-3 p-3 bg-light small text-break">
                        <?= nl2br(htmlspecialchars($v['beneficios'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['observacoes'])): ?>
                <div class="mb-3">
                    <h5 class="h6">Observações</h5>
                    <div class="border rounded-3 p-3 bg-light small text-break">
                        <?= nl2br(htmlspecialchars($v['observacoes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Candidatos vinculados -->
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="h6 mb-0">Candidatos vinculados (<?= count($this->data['candidatos'] ?? []) ?>)</h5>
                </div>
                <?php if (empty($this->data['candidatos'])): ?>
                    <p class="text-muted mb-0">Nenhum candidato vinculado a esta vaga.</p>
                <?php else: ?>
                    <?php
                    $stagesSelect = $this->data['pipeline_stages']
                        ?? \App\adms\Models\Services\RhPipelineStageCatalog::all();
                    $canPipeline = !empty($this->data['can_manage_pipeline']);
                    $vagaIdView = (int) ($this->data['vaga']['id'] ?? 0);
                    ?>

                    <!-- Mobile: cards -->
                    <div class="d-md-none vstack gap-2">
                        <?php foreach ($this->data['candidatos'] as $cand):
                            $statusAtualCand = ($cand['status'] ?? '') === 'em_analise'
                                ? 'em_entrevista'
                                : ($cand['status'] ?? 'candidatado');
                            $candId = (int) ($cand['rh_candidato_id'] ?? 0);
                            ?>
                            <div class="border rounded-3 p-3 bg-white" id="card-candidato-<?= $candId ?>">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-break"><?= htmlspecialchars((string) ($cand['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-break text-muted"><?= htmlspecialchars((string) ($cand['candidato_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($cand['candidato_telefone'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars((string) $cand['candidato_telefone'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= $candId ?>"
                                       class="btn btn-sm btn-outline-info flex-shrink-0" title="Ver candidato">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center small mb-2">
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars(\App\adms\Helpers\RhCandidatoOrigemHelper::label((string) ($cand['canal_origem'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="text-muted"><?= htmlspecialchars(FormatHelper::formatDateTime($cand['data_candidatura'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <?php if ($canPipeline): ?>
                                    <select class="form-select form-select-sm status-candidatura"
                                            data-candidato-id="<?= $candId ?>"
                                            data-vaga-id="<?= $vagaIdView ?>">
                                        <?php foreach ($stagesSelect as $stage): ?>
                                            <option value="<?= htmlspecialchars($stage['code']) ?>"
                                                <?= $statusAtualCand === $stage['code'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($stage['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars(\App\adms\Models\Services\RhPipelineStageCatalog::label((string) ($cand['status'] ?? 'candidatado'))) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Desktop: tabela -->
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Canal</th>
                                    <th>Status</th>
                                    <th>Data Candidatura</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['candidatos'] as $cand):
                                    $statusAtualCand = ($cand['status'] ?? '') === 'em_analise'
                                        ? 'em_entrevista'
                                        : ($cand['status'] ?? 'candidatado');
                                    $candId = (int) ($cand['rh_candidato_id'] ?? 0);
                                    ?>
                                    <tr id="row-candidato-<?= $candId ?>">
                                        <td><?= htmlspecialchars((string) ($cand['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-break"><?= htmlspecialchars((string) ($cand['candidato_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($cand['candidato_telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(\App\adms\Helpers\RhCandidatoOrigemHelper::label((string) ($cand['canal_origem'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($canPipeline): ?>
                                                <select class="form-select form-select-sm status-candidatura"
                                                        data-candidato-id="<?= $candId ?>"
                                                        data-vaga-id="<?= $vagaIdView ?>"
                                                        style="min-width: 140px;">
                                                    <?php foreach ($stagesSelect as $stage): ?>
                                                        <option value="<?= htmlspecialchars($stage['code']) ?>"
                                                            <?= $statusAtualCand === $stage['code'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($stage['label']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars(\App\adms\Models\Services\RhPipelineStageCatalog::label((string) ($cand['status'] ?? 'candidatado'))) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= FormatHelper::formatDateTime($cand['data_candidatura'] ?? null) ?></td>
                                        <td>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= $candId ?>"
                                               class="btn btn-sm btn-info" title="Ver candidato">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal Vincular Candidato -->
            <?php if (!empty($this->data['candidatos_disponiveis'])): ?>
            <div class="modal fade" id="modalVincularCandidato" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Vincular Candidato à Vaga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formVincularCandidato">
                            <input type="hidden" name="csrf_token" value="<?= $csrfTokenVinculoAjax ?>">
                            <div class="modal-body">
                                <input type="hidden" name="vaga_id" value="<?= (int)($this->data['vaga']['id'] ?? 0) ?>">
                                <div class="mb-3">
                                    <label for="candidato_id" class="form-label">Selecione os Candidatos *</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select name="candidato_id[]" id="candidato_id" class="form-select" multiple size="10" required style="font-size: 0.9rem;">
                                                <?php foreach ($this->data['candidatos_disponiveis'] as $c): ?>
                                                    <?php
                                                    $cidadeUf      = trim(($c['cidade'] ?? '') . '/' . ($c['estado'] ?? ''), '/');
                                                    $graduacaoText = trim(strip_tags($c['graduacao'] ?? ''));
                                                    $expText       = trim(strip_tags($c['ultima_experiencia'] ?? ''));
                                                    ?>
                                                    <option
                                                        value="<?= $c['id'] ?>"
                                                        title="<?= htmlspecialchars(($c['observacoes'] ?? '') ? substr($c['observacoes'], 0, 100) : '') ?>"
                                                        data-nome="<?= htmlspecialchars($c['nome'] ?? '') ?>"
                                                        data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                                        data-telefone="<?= htmlspecialchars($c['telefone'] ?? '') ?>"
                                                        data-cidadeuf="<?= htmlspecialchars($cidadeUf) ?>"
                                                        data-area="<?= htmlspecialchars($c['area_interesse'] ?? '') ?>"
                                                        data-score="<?= isset($c['score']) ? (int)$c['score'] : '' ?>"
                                                        data-classificacao="<?= htmlspecialchars($c['classificacao'] ?? '') ?>"
                                                        data-graduacao="<?= htmlspecialchars($graduacaoText) ?>"
                                                        data-exp="<?= htmlspecialchars($expText) ?>"
                                                    >
                                                        <?php if (!empty($c['score'])): ?>
                                                            [<?= (int)$c['score'] ?>]
                                                        <?php endif; ?>
                                                        <?php if (!empty($c['classificacao'])): ?>
                                                            [<?= htmlspecialchars($c['classificacao']) ?>]
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($c['nome'] ?? '') ?>
                                                        <?php if (!empty($c['email'])): ?>
                                                            | <?= htmlspecialchars($c['email']) ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-info-circle"></i> Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos candidatos.
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <div id="tabelaCandidatosSelecionados" style="display:none;">
                                                <h6 class="mb-2">Candidatos Selecionados:</h6>
                                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                    <table class="table table-sm table-bordered table-striped">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th style="font-size: 0.85rem;">Nome</th>
                                                                <th style="font-size: 0.85rem;">Área</th>
                                                                <th style="font-size: 0.85rem;">Score</th>
                                                                <th style="font-size: 0.85rem;">Formação</th>
                                                                <th style="font-size: 0.85rem;">Última Exp.</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="tbodyCandidatosSelecionados">
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div id="nenhumCandidatoSelecionado" class="text-muted text-center p-3 border rounded bg-light">
                                                <i class="fas fa-info-circle"></i><br>
                                                <small>Nenhum candidato selecionado.<br>Selecione candidatos na lista ao lado para ver os detalhes.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="observacoes_candidato" class="form-label">Observações</label>
                                    <textarea name="observacoes" id="observacoes_candidato" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success">Vincular</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($this->data['can_manage_pipeline'])): ?>
<div class="modal fade" id="modalMotivoStatusVaga" tabindex="-1" aria-labelledby="modalMotivoStatusVagaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMotivoStatusVagaLabel">Motivo da movimentação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Novo status: <strong id="motivoStatusVagaLabel">-</strong>
                </p>
                <div class="mb-3">
                    <label for="motivo_codigo_vaga" class="form-label">Motivo <span class="text-danger">*</span></label>
                    <select id="motivo_codigo_vaga" class="form-select"></select>
                </div>
                <div class="mb-0">
                    <label for="motivo_observacoes_vaga" class="form-label">Observações</label>
                    <textarea id="motivo_observacoes_vaga" class="form-control" rows="3" placeholder="Obrigatório se o motivo for Outro"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarMotivoStatusVaga">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const motivosPorStatusVaga = <?= json_encode(\App\adms\Models\Services\RhCandidaturaMotivoCatalog::allGrouped(), JSON_UNESCAPED_UNICODE) ?>;
let pendingStatusChange = null;
let modalMotivoStatusVaga = null;

document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalMotivoStatusVaga');
    if (modalEl && window.bootstrap) {
        modalMotivoStatusVaga = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (pendingStatusChange && pendingStatusChange.select) {
                pendingStatusChange.select.value = pendingStatusChange.oldValue;
                pendingStatusChange = null;
            }
        });
    }
    const btnConfirm = document.getElementById('btnConfirmarMotivoStatusVaga');
    if (btnConfirm) {
        btnConfirm.addEventListener('click', confirmarStatusCandidaturaVaga);
    }
});

document.querySelectorAll('.status-candidatura').forEach(function(select) {
    select.dataset.oldValue = select.value;
    select.addEventListener('change', function() {
        const candidatoId = this.dataset.candidatoId;
        const vagaId = this.dataset.vagaId || <?= (int)($this->data['vaga']['id'] ?? 0) ?>;
        const novoStatus = this.value;
        const oldValue = this.dataset.oldValue || 'candidatado';

        if (novoStatus === oldValue) {
            return;
        }

        if (!modalMotivoStatusVaga) {
            this.value = oldValue;
            alert('Não foi possível abrir o formulário de motivo.');
            return;
        }

        pendingStatusChange = {
            select: this,
            candidatoId: candidatoId,
            vagaId: vagaId,
            novoStatus: novoStatus,
            oldValue: oldValue,
            label: this.options[this.selectedIndex].text
        };

        const motivoSelect = document.getElementById('motivo_codigo_vaga');
        const obs = document.getElementById('motivo_observacoes_vaga');
        document.getElementById('motivoStatusVagaLabel').textContent = pendingStatusChange.label;
        obs.value = '';
        motivoSelect.innerHTML = '<option value="">Selecione...</option>';
        const motivos = motivosPorStatusVaga[novoStatus] || {};
        Object.keys(motivos).forEach(function (codigo) {
            const opt = document.createElement('option');
            opt.value = codigo;
            opt.textContent = motivos[codigo];
            motivoSelect.appendChild(opt);
        });
        modalMotivoStatusVaga.show();
    });
});

function confirmarStatusCandidaturaVaga() {
    if (!pendingStatusChange) {
        return;
    }

    const motivoSelect = document.getElementById('motivo_codigo_vaga');
    const obsEl = document.getElementById('motivo_observacoes_vaga');
    const motivo = (motivoSelect && motivoSelect.value) ? motivoSelect.value.trim() : '';
    const observacoes = (obsEl && obsEl.value) ? obsEl.value.trim() : '';

    if (!motivo) {
        alert('Selecione o motivo da movimentação.');
        return;
    }
    if (motivo === 'OUTRO' && !observacoes) {
        alert('Descreva o motivo em observações quando escolher "Outro".');
        return;
    }

    const change = pendingStatusChange;
    pendingStatusChange = null;
    if (modalMotivoStatusVaga) {
        modalMotivoStatusVaga.hide();
    }

    const formData = new FormData();
    formData.append('candidato_id', change.candidatoId);
    formData.append('vaga_id', change.vagaId);
    formData.append('status', change.novoStatus);
    formData.append('motivo_codigo', motivo);
    if (observacoes) {
        formData.append('observacoes', observacoes);
    }
    formData.append('csrf_token', '<?= $csrfTokenStatus ?>');

    fetch('<?php echo $_ENV['URL_ADM']; ?>rh-atualizar-status-candidatura', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            change.select.dataset.oldValue = change.novoStatus;
            location.reload();
        } else {
            alert('Erro: ' + data.message);
            change.select.value = change.oldValue;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar status.');
        change.select.value = change.oldValue;
    });
}

<?php if (!empty($this->data['candidatos_disponiveis'])): ?>
// Atualizar tabela com detalhes de TODOS os candidatos selecionados
(function() {
    const select = document.getElementById('candidato_id');
    const tabelaDiv = document.getElementById('tabelaCandidatosSelecionados');
    const tbody = document.getElementById('tbodyCandidatosSelecionados');
    const nenhumDiv = document.getElementById('nenhumCandidatoSelecionado');
    
    if (!select || !tabelaDiv || !tbody || !nenhumDiv) {
        return;
    }

    function atualizarTabelaCandidatos() {
        const selecionados = Array.from(select.selectedOptions);
        
        if (!selecionados.length) {
            tabelaDiv.style.display = 'none';
            nenhumDiv.style.display = 'block';
            tbody.innerHTML = '';
            return;
        }

        nenhumDiv.style.display = 'none';
        tabelaDiv.style.display = 'block';
        
        tbody.innerHTML = '';
        
        selecionados.forEach(function(opt) {
            const d = opt.dataset;
            const tr = document.createElement('tr');
            
            // Nome
            const tdNome = document.createElement('td');
            tdNome.style.fontSize = '0.85rem';
            let nomeHtml = '<strong>' + (d.nome || '-') + '</strong>';
            if (d.email) nomeHtml += '<br><small class="text-muted">' + d.email + '</small>';
            if (d.telefone) nomeHtml += '<br><small class="text-muted">Tel: ' + d.telefone + '</small>';
            if (d.cidadeuf) nomeHtml += '<br><small class="text-muted">' + d.cidadeuf + '</small>';
            tdNome.innerHTML = nomeHtml;
            
            // Área
            const tdArea = document.createElement('td');
            tdArea.style.fontSize = '0.85rem';
            tdArea.textContent = d.area || '-';
            
            // Score
            const tdScore = document.createElement('td');
            tdScore.style.fontSize = '0.85rem';
            let scoreHtml = '';
            if (d.score) {
                const score = parseInt(d.score);
                let badgeClass = 'badge bg-secondary';
                if (score >= 80) badgeClass = 'badge bg-success';
                else if (score >= 60) badgeClass = 'badge bg-info';
                else if (score >= 40) badgeClass = 'badge bg-warning text-dark';
                else badgeClass = 'badge bg-danger';
                scoreHtml = '<span class="' + badgeClass + '">' + score + '/100</span>';
            }
            if (d.classificacao) {
                if (scoreHtml) scoreHtml += '<br>';
                scoreHtml += '<span class="badge bg-primary">' + d.classificacao + '</span>';
            }
            tdScore.innerHTML = scoreHtml || '-';
            
            // Formação
            const tdFormacao = document.createElement('td');
            tdFormacao.style.fontSize = '0.85rem';
            tdFormacao.innerHTML = d.graduacao ? '<small>' + d.graduacao.substring(0, 80) + (d.graduacao.length > 80 ? '...' : '') + '</small>' : '-';
            
            // Última Experiência
            const tdExp = document.createElement('td');
            tdExp.style.fontSize = '0.85rem';
            tdExp.innerHTML = d.exp ? '<small>' + d.exp.substring(0, 80) + (d.exp.length > 80 ? '...' : '') + '</small>' : '-';
            
            tr.appendChild(tdNome);
            tr.appendChild(tdArea);
            tr.appendChild(tdScore);
            tr.appendChild(tdFormacao);
            tr.appendChild(tdExp);
            
            tbody.appendChild(tr);
        });
    }

    select.addEventListener('change', atualizarTabelaCandidatos);
    select.addEventListener('click', atualizarTabelaCandidatos);
    
    // Atualizar ao abrir a modal também
    const modal = document.getElementById('modalVincularCandidato');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            atualizarTabelaCandidatos();
        });
    }
})();  

// Vincular candidato à vaga (da tela da vaga)
document.getElementById('formVincularCandidato')?.addEventListener('submit', function(e) {
    e.preventDefault();

    // Validar se pelo menos um candidato foi selecionado
    const select = document.getElementById('candidato_id');
    const selecionados = Array.from(select.selectedOptions).map(opt => opt.value);

    if (selecionados.length === 0) {
        alert('Selecione pelo menos um candidato.');
        return;
    }

    const formData = new FormData(this);

    fetch('<?php echo $_ENV['URL_ADM']; ?>rh-vincular-candidato-vaga', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Resposta não é JSON válido:', text);
            alert('Erro ao vincular candidato(s): resposta inesperada do servidor.');
            return;
        }

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        alert('Erro ao vincular candidato(s).');
    });
});
<?php endif; ?>
</script>

