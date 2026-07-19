<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes do Candidato</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="text-decoration-none">Currículos</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-id-card me-2"></i>Candidato #<?= (int)($this->data['candidato']['id'] ?? 0) ?></span>
            <div class="btn-group">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-edit/<?= (int)$this->data['candidato']['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="btn btn-outline-secondary btn-sm">
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

            <?php $c = $this->data['candidato'] ?? []; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Dados do candidato</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nome</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['nome'] ?? '') ?></dd>

                        <dt class="col-sm-4">E-mail</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['email'] ?? '') ?></dd>

                        <dt class="col-sm-4">Telefone</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['telefone'] ?? '') ?></dd>

                        <dt class="col-sm-4">Cidade / UF</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars(($c['cidade'] ?? '') . (isset($c['estado']) && $c['estado'] ? ' / ' . $c['estado'] : '')) ?>
                        </dd>

                        <dt class="col-sm-4">Origem</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($c['origem'] ?? '') ?></dd>

                        <dt class="col-sm-4">Status do processo</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($c['status_processo'] ?? '') ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Área de Interesse</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($c['area_interesse'] ?? '-') ?>
                        </dd>

                        <?php if (!empty($c['score']) || !empty($c['classificacao'])): ?>
                        <dt class="col-sm-4">Classificação</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($c['score'])): ?>
                                <span class="badge <?php
                                    $score = (int)$c['score'];
                                    if ($score >= 80) echo 'bg-success';
                                    elseif ($score >= 60) echo 'bg-info';
                                    elseif ($score >= 40) echo 'bg-warning text-dark';
                                    else echo 'bg-danger';
                                ?>">
                                    Score: <?= $score ?>/100
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($c['classificacao'])): ?>
                                <span class="badge bg-primary ms-1">
                                    <?= htmlspecialchars($c['classificacao']) ?>
                                </span>
                            <?php endif; ?>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5>Informações de LGPD</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status LGPD</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($c['lgpd_status'] ?? '') ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Consentimento em</dt>
                        <dd class="col-sm-8">
                            <?= FormatHelper::formatDateTime($c['lgpd_data_consentimento'] ?? null) ?>
                        </dd>

                        <dt class="col-sm-4">Expira em</dt>
                        <dd class="col-sm-8">
                            <?= FormatHelper::formatDateTime($c['lgpd_data_expiracao'] ?? null) ?>
                        </dd>

                        <dt class="col-sm-4">Motivo anonimização</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($c['lgpd_motivo_anonimizacao'] ?? '-') ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <?php if (!empty($c['graduacao'])): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <h5>Graduação / Formação</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($c['graduacao'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($c['ultima_experiencia'])): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <h5>Última Experiência Profissional</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($c['ultima_experiencia'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($c['classificacao_observacoes'])): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <h5>Observações sobre Classificação</h5>
                    <div class="border rounded p-3 bg-info bg-opacity-10">
                        <?= nl2br(htmlspecialchars($c['classificacao_observacoes'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <h5>Observações</h5>
                    <div class="border rounded p-2" style="min-height: 60px;">
                        <?= nl2br(htmlspecialchars($c['observacoes'] ?? '')) ?: '<span class="text-muted">Nenhuma observação registrada.</span>' ?>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>Vagas Vinculadas (<?= count($this->data['vagas'] ?? []) ?>)</h5>
                        <?php if (!empty($this->data['vagas_disponiveis'])): ?>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalVincularVaga">
                                <i class="fas fa-link me-1"></i>Vincular em Vaga
                            </button>
                        <?php endif; ?>
                    </div>
            <?php if (!empty($this->data['vagas'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Título da Vaga</th>
                                        <th>Área</th>
                                        <th>Cargo</th>
                                        <th>Status</th>
                                        <th>Data Candidatura</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['vagas'] as $vaga): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($vaga['vaga_titulo'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($vaga['area_nome'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($vaga['cargo_nome'] ?? '')) ?: '-') ?></td>
                                            <td>
                                                <?php
                                                $vagaStatusClass = match($vaga['status']) {
                                                    'candidatado'   => 'badge bg-info',
                                                    'em_entrevista' => 'badge bg-warning text-dark',
                                                    'em_analise'   => 'badge bg-warning text-dark', // legado
                                                    'aprovado'      => 'badge bg-success',
                                                    'reprovado'     => 'badge bg-danger',
                                                    'desistiu'      => 'badge bg-secondary',
                                                    default         => 'badge bg-secondary',
                                                };
                                                ?>
                                                <span class="<?= $vagaStatusClass ?>">
                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $vaga['status'] ?? ''))) ?>
                                                </span>
                                            </td>
                                            <td><?= FormatHelper::formatDateTime($vaga['data_candidatura'] ?? null) ?></td>
                                            <td>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= $vaga['rh_vaga_id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Ver vaga">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Histórico de Entrevistas -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-light shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Entrevistas (<?= count($this->data['entrevistas'] ?? []) ?>)</h5>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-create?candidato_id=<?= (int)($this->data['candidato']['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Agendar entrevista
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($this->data['entrevistas'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>Tipo</th>
                                                <th>Vaga</th>
                                                <th>Entrevistador</th>
                                                <th>Resultado</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($this->data['entrevistas'] as $ent): ?>
                                                <tr>
                                                    <td><?= FormatHelper::formatDateTime($ent['data_hora'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($ent['tipo'] ?? '-')) ?></td>
                                                    <td><?= htmlspecialchars($ent['vaga_titulo'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($ent['entrevistador_nome'] ?? '-') ?></td>
                                                    <td>
                                                        <?php
                                                        $res = $ent['resultado'] ?? '';
                                                        $resClass = $res === 'aprovado' ? 'badge bg-success' : ($res === 'reprovado' ? 'badge bg-danger' : 'badge bg-warning text-dark');
                                                        ?>
                                                        <span class="<?= $resClass ?>"><?= $res ? htmlspecialchars(ucfirst($res)) : '-' ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-view/<?= (int)$ent['id'] ?>" class="btn btn-xs btn-info btn-sm" title="Ver">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Nenhuma entrevista registrada. Use "Agendar entrevista" para cadastrar.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($this->data['anexos'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Currículos / Anexos</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Arquivo</th>
                                        <th>Enviado em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['anexos'] as $anexo): ?>
                                        <tr>
                                            <td><?= (int)$anexo['id'] ?></td>
                                            <td><?= htmlspecialchars($anexo['tipo'] ?? '') ?></td>
                                            <td>
                                                <?php if (!empty($anexo['arquivo_caminho'])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-download-anexo/<?= (int)$anexo['id']; ?>"
                                                       target="_blank"
                                                       class="text-decoration-underline fw-semibold">
                                                        <i class="fas fa-download me-1"></i>
                                                        <?= htmlspecialchars($anexo['nome_original'] ?? basename($anexo['arquivo_caminho'])) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= \App\adms\Helpers\FormatHelper::formatDateTime($anexo['created_at'] ?? null) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                    <p class="text-muted">Nenhuma vaga vinculada ainda.</p>
                <?php endif; ?>
                </div>
            </div>

            <!-- Modal Vincular em Vaga -->
            <?php if (!empty($this->data['vagas_disponiveis'])): ?>
            <div class="modal fade" id="modalVincularVaga" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Vincular Candidato em Vaga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formVincularVaga">
                            <div class="modal-body">
                                <input type="hidden" name="candidato_id" value="<?= (int)($this->data['candidato']['id'] ?? 0) ?>">
                                <div class="mb-3">
                                    <label for="vaga_id" class="form-label">Selecione as Vagas *</label>
                                    <select name="vaga_id[]" id="vaga_id" class="form-select" multiple size="8" required style="font-size: 0.9rem;">
                                        <?php foreach ($this->data['vagas_disponiveis'] as $v): ?>
                                            <option value="<?= $v['id'] ?>" title="<?= htmlspecialchars(($v['descricao'] ?? '') ? substr(strip_tags($v['descricao']), 0, 150) : '') ?>">
                                                <?= htmlspecialchars($v['titulo']) ?>
                                                <?php if (!empty($v['area_nome'])): ?>
                                                    | Área: <?= htmlspecialchars($v['area_nome']) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($v['cargo_nome'])): ?>
                                                    | Cargo: <?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($v['cargo_nome'] ?? ''))) ?>
                                                <?php endif; ?>
                                                | Tipo: <?= htmlspecialchars($v['tipo_contrato'] ?? 'CLT') ?>
                                                | Status: <?= htmlspecialchars(ucfirst($v['status'] ?? 'aberta')) ?>
                                                <?php if (!empty($v['quantidade_vagas'])): ?>
                                                    | Vagas: <?= (int)$v['quantidade_vagas'] ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle"></i> Segure Ctrl (ou Cmd no Mac) para selecionar múltiplas vagas. 
                                        Informações: Título | Área | Cargo | Tipo Contrato | Status | Quantidade de Vagas
                                    </small>
                                </div>
                                <div class="mb-3">
                                    <label for="observacoes_vaga" class="form-label">Observações</label>
                                    <textarea name="observacoes" id="observacoes_vaga" class="form-control" rows="3"></textarea>
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

<script>
// Vincular candidato em vaga (da tela do candidato)
<?php if (!empty($this->data['vagas_disponiveis'])): ?>
document.getElementById('formVincularVaga')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Garantir que vaga_id seja array
    const vagaSelect = document.getElementById('vaga_id');
    const vagasSelecionadas = Array.from(vagaSelect.selectedOptions).map(opt => opt.value);
    formData.delete('vaga_id[]');
    vagasSelecionadas.forEach(vagaId => {
        formData.append('vaga_id[]', vagaId);
    });
    
    fetch('<?php echo $_ENV['URL_ADM']; ?>rh-vincular-candidato-vaga', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao vincular em vaga.');
    });
});
<?php endif; ?>
</script>


