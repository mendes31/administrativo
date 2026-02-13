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
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus me-1"></i>Vincular Candidatos
                </a>
                <?php if (!empty($this->data['buttonPermission']['RhVagasEdit'])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-edit/<?= (int)$this->data['vaga']['id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <?php if (!empty($this->data['log_resumo']['has_logs'])): ?>
                    <a href="<?= htmlspecialchars($this->data['log_resumo']['list_url']); ?>"
                       class="btn btn-outline-info btn-sm">
                        <i class="fas fa-history me-1"></i>
                        Log de Alterações (<?= (int)$this->data['log_resumo']['count']; ?>)
                    </a>
                <?php endif; ?>
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
                        <?php if (!empty($v['area_nome'])): ?>
                            <span class="badge bg-info"><?= htmlspecialchars($v['area_nome']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($v['cargo_nome'])): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars($v['cargo_nome']) ?></span>
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
                        <dd class="col-sm-7"><?= htmlspecialchars($v['cargo_nome'] ?? '-') ?></dd>

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

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="<?= $statusClass ?>">
                                <?= htmlspecialchars(ucfirst($v['status'] ?? '')) ?>
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

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
                    <?php if (!empty($this->data['can_manage_pipeline'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= (int)($this->data['vaga']['id'] ?? 0) ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus me-1"></i>Vincular Candidatos
                        </a>
                    <?php endif; ?>
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
                                                    <option value="candidatado" <?= ($cand['status'] ?? '') === 'candidatado' ? 'selected' : '' ?>>Candidatado</option>
                                                    <option value="em_analise" <?= ($cand['status'] ?? '') === 'em_analise' ? 'selected' : '' ?>>Em Análise</option>
                                                    <option value="aprovado" <?= ($cand['status'] ?? '') === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                                    <option value="reprovado" <?= ($cand['status'] ?? '') === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                                                    <option value="desistiu" <?= ($cand['status'] ?? '') === 'desistiu' ? 'selected' : '' ?>>Desistiu</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cand['status'] ?? 'candidatado'))) ?>
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

<script>
// Pipeline: Atualizar status de candidatura
document.querySelectorAll('.status-candidatura').forEach(function(select) {
    select.addEventListener('change', function() {
        const candidatoId = this.dataset.candidatoId;
        const vagaId = this.dataset.vagaId || <?= (int)($this->data['vaga']['id'] ?? 0) ?>;
        const novoStatus = this.value;
        
        if (!confirm('Deseja alterar o status da candidatura para "' + this.options[this.selectedIndex].text + '"?')) {
            this.value = this.dataset.oldValue || 'candidatado';
            return;
        }
        
        const formData = new FormData();
        formData.append('candidato_id', candidatoId);
        formData.append('vaga_id', vagaId);
        formData.append('status', novoStatus);
        formData.append('observacoes', 'Status alterado via pipeline');
        formData.append('csrf_token', '<?= $csrfTokenStatus ?>');
        
        fetch('<?php echo $_ENV['URL_ADM']; ?>rh-atualizar-status-candidatura', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.dataset.oldValue = novoStatus;
                // Atualizar badge visualmente (opcional)
                location.reload(); // Recarregar para refletir mudanças
            } else {
                alert('Erro: ' + data.message);
                this.value = this.dataset.oldValue || 'candidatado';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar status.');
            this.value = this.dataset.oldValue || 'candidatado';
        });
    });
    
    // Salvar valor inicial
    select.dataset.oldValue = select.value;
});

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

