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
            <div class="btn-group">
                <?php if (!empty($this->data['can_manage_pipeline'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Vincular Candidatos
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-pipeline/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-project-diagram me-1"></i>Ver Pipeline (Kanban)
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

            <div class="row mb-3">
                <div class="col-md-12 mb-3">
                    <h4><?= htmlspecialchars($v['titulo'] ?? '') ?></h4>
                    <div class="d-flex gap-2 mb-2">
                        <?php
                        $statusClass = match($v['status']) {
                            'aberta'   => 'badge bg-success',
                            'pausada'  => 'badge bg-warning text-dark',
                            'fechada'  => 'badge bg-secondary',
                            'cancelada'=> 'badge bg-danger',
                            default    => 'badge bg-secondary',
                        };
                        ?>
                        <span class="<?= $statusClass ?>">
                            <?= htmlspecialchars(ucfirst($v['status'] ?? '')) ?>
                        </span>
                        <?php if (!empty($v['publicada'])): ?>
                            <span class="badge bg-primary">Publicada (portal)</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Não publicada</span>
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
                        <?php if (!empty($v['cargo_nome'])): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($v['cargo_nome'] ?? ''))) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Informações da Vaga</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Área/Departamento</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($v['area_nome'] ?? '-') ?></dd>

                        <dt class="col-sm-5">Cargo</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($v['cargo_nome'] ?? '')) ?: '-') ?></dd>

                        <dt class="col-sm-5">Tipo de Contrato</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($v['tipo_contrato'] ?? '-') ?></dd>

                        <?php if (!empty($v['salario_min']) || !empty($v['salario_max'])): ?>
                            <dt class="col-sm-5">Faixa Salarial</dt>
                            <dd class="col-sm-7">
                                <?php
                                $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float)$v['salario_min'], 2, ',', '.') : '';
                                $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float)$v['salario_max'], 2, ',', '.') : '';
                                if ($salMin && $salMax) {
                                    echo $salMin . ' - ' . $salMax;
                                } elseif ($salMin) {
                                    echo 'A partir de ' . $salMin;
                                } elseif ($salMax) {
                                    echo 'Até ' . $salMax;
                                }
                                ?>
                                <?php if (empty($v['mostrar_salario'])): ?>
                                    <span class="text-muted">(não divulgado)</span>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>

                        <dt class="col-sm-5">Quantidade de Vagas</dt>
                        <dd class="col-sm-7"><?= (int)($v['quantidade_vagas'] ?? 1) ?></dd>

                        <dt class="col-sm-5">Local de Trabalho</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($v['local_trabalho'] ?? '-') ?></dd>

                        <dt class="col-sm-5">Jornada de Trabalho</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($v['jornada_trabalho'] ?? '-') ?></dd>

                        <dt class="col-sm-5">Responsável</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($v['responsavel_nome'] ?? '-') ?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5>Datas e Status</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Data de Abertura</dt>
                        <dd class="col-sm-7"><?= FormatHelper::formatDateTime($v['data_abertura'] ?? null) ?></dd>

                        <dt class="col-sm-5">Data Limite de Inscrição</dt>
                        <dd class="col-sm-7"><?= FormatHelper::formatDateTime($v['data_limite_inscricao'] ?? null) ?></dd>

                        <dt class="col-sm-5">Data de Fechamento</dt>
                        <dd class="col-sm-7"><?= FormatHelper::formatDateTime($v['data_fechamento'] ?? null) ?></dd>

                        <dt class="col-sm-5">Divulgação</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars(\App\adms\Models\Services\RhVagaDivulgacaoService::labelVisibilidade((string) ($v['visibilidade'] ?? 'externa'))) ?>
                        </dd>

                        <dt class="col-sm-5">Publicação (portal)</dt>
                        <dd class="col-sm-7">
                            <?php if (!empty($v['publicada'])): ?>
                                Sim
                                <?php if (!empty($v['publicado_em'])): ?>
                                    <small class="text-muted">(desde <?= FormatHelper::formatDateTime($v['publicado_em']) ?>)</small>
                                <?php endif; ?>
                                <br><small class="text-muted">Candidatura pública em <code>vagas-abertas/<?= (int) ($v['id'] ?? 0) ?></code>.</small>
                            <?php else: ?>
                                Não
                                <?php if (\App\adms\Models\Services\RhVagaDivulgacaoService::permiteDivulgacaoInterna((string) ($v['visibilidade'] ?? ''))): ?>
                                    <br><small class="text-muted">Para o público interno, use Informativos no app (bloco abaixo).</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="<?= $statusClass ?>">
                                <?= htmlspecialchars(ucfirst($v['status'] ?? '')) ?>
                            </span>
                        </dd>
                    </dl>
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
            <div class="card mb-4 border-light shadow">
                <div class="card-header"><i class="fas fa-share-alt me-2"></i>Divulgação</div>
                <div class="card-body">
                    <?php if ($showExternalLinks): ?>
                        <p class="small text-muted mb-2">
                            Copie o link adequado para cada canal. O formulário público exige CSRF, honeypot, LGPD
                            <?= !empty($this->data['captcha_hint']) ? ' e CAPTCHA' : '' ?>
                            (configurável em Portal de Vagas).
                        </p>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Canal</th>
                                        <th>URL</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linksExt as $linkRow): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($linkRow['label']) ?></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm font-monospace"
                                                       id="share-url-<?= htmlspecialchars($linkRow['canal']) ?>"
                                                       value="<?= htmlspecialchars($linkRow['url']) ?>" readonly>
                                            </td>
                                            <td class="text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        data-copy-target="share-url-<?= htmlspecialchars($linkRow['canal']) ?>">
                                                    Copiar
                                                </button>
                                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                                   href="<?= htmlspecialchars($linkRow['url']) ?>">Abrir</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($visNow === 'interna'): ?>
                        <p class="small text-muted mb-2">
                            Esta vaga é <strong>só interna</strong>: não aparece em <code>vagas-abertas</code>.
                            Divulgue no app pelos Informativos (colaboradores logados, com push/ciência se quiser).
                        </p>
                    <?php endif; ?>

                    <?php if ($showInternalHint): ?>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="<?= htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-internas/' . (int) ($v['id'] ?? 0)) ?>"
                               class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                Abrir no portal interno
                            </a>
                            <?php if ($canCreateInformativo): ?>
                                <a href="<?= htmlspecialchars($informativoUrl) ?>" class="btn btn-sm btn-success">
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
                document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-copy-target');
                        var input = id ? document.getElementById(id) : null;
                        if (!input) return;
                        var text = input.value || '';
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(text).then(function () {
                                btn.textContent = 'Copiado';
                                setTimeout(function () { btn.textContent = 'Copiar'; }, 1500);
                            });
                        } else {
                            input.select();
                            document.execCommand('copy');
                            btn.textContent = 'Copiado';
                            setTimeout(function () { btn.textContent = 'Copiar'; }, 1500);
                        }
                    });
                });
            })();
            </script>
            <?php endif; ?>

            <?php if (!empty($v['descricao'])): ?>
                <div class="mb-3">
                    <h5>Descrição</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($v['descricao'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['requisitos'])): ?>
                <div class="mb-3">
                    <h5>Requisitos</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($v['requisitos'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['beneficios'])): ?>
                <div class="mb-3">
                    <h5>Benefícios</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($v['beneficios'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['observacoes'])): ?>
                <div class="mb-3">
                    <h5>Observações</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($v['observacoes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Candidatos vinculados -->
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>Candidatos Vinculados (<?= count($this->data['candidatos'] ?? []) ?>)</h5>
                </div>
                <?php if (empty($this->data['candidatos'])): ?>
                    <p class="text-muted">Nenhum candidato vinculado a esta vaga.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Status</th>
                                    <th>Data Candidatura</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['candidatos'] as $cand): ?>
                                    <tr id="row-candidato-<?= $cand['rh_candidato_id'] ?>">
                                        <td><?= htmlspecialchars($cand['candidato_nome'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cand['candidato_email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cand['candidato_telefone'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($this->data['can_manage_pipeline'])): ?>
                                                <select class="form-select form-select-sm status-candidatura" 
                                                        data-candidato-id="<?= $cand['rh_candidato_id'] ?>"
                                                        data-vaga-id="<?= (int)($this->data['vaga']['id'] ?? 0) ?>"
                                                        style="min-width: 140px;">
                                                    <?php
                                                    $statusAtualCand = ($cand['status'] ?? '') === 'em_analise'
                                                        ? 'em_entrevista'
                                                        : ($cand['status'] ?? 'candidatado');
                                                    $stagesSelect = $this->data['pipeline_stages']
                                                        ?? \App\adms\Models\Services\RhPipelineStageCatalog::all();
                                                    foreach ($stagesSelect as $stage):
                                                    ?>
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
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= $cand['rh_candidato_id'] ?>" 
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

