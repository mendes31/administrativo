<?php

use App\adms\Helpers\CSRFHelper;

$form = $this->data['form'] ?? [];
$url = (string) ($_ENV['URL_ADM'] ?? '');
$busca = (string) ($this->data['busca'] ?? '');
$termoAtivo = $this->data['termo_ativo'] ?? null;
$visitante = $this->data['visitante_selecionado'] ?? null;
$termoVigente = $this->data['termo_vigente'] ?? null;
$resultados = $this->data['resultados_busca'] ?? [];
$visitantesBusca = $this->data['visitantes_busca'] ?? [];
$autorizacoesVisitante = $this->data['autorizacoes_visitante'] ?? [];
$csrf = CSRFHelper::generateCSRFToken('form_portaria_movimentacao');
$visitanteId = (int) ($form['visitante_id'] ?? 0);
$autorizacaoId = (int) ($form['autorizacao_id'] ?? 0);
$pontoId = (int) ($form['ponto_controle_id'] ?? 0);
$termoPendente = !empty($this->data['termo_pendente']) || !empty($this->data['exigir_termo']);
?>
<div class="container-fluid px-4">
    <h1 class="h3 mt-4 mb-3">Movimentações da Portaria</h1>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (!empty($this->data['errors'])): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $this->data['errors'])) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-header">1. Localizar visitante / agenda</div>
        <div class="card-body">
            <form method="get" class="row g-2">
                <div class="col-12 col-md-8">
                    <label class="form-label">Documento ou nome do visitante</label>
                    <input class="form-control" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Ex.: CPF, RG ou nome" autofocus>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-fill">Buscar</button>
                    <?php if ($busca !== ''): ?>
                        <a class="btn btn-outline-secondary" href="<?= $url ?>portaria-movimentacoes">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($busca !== ''): ?>
                <hr>
                <h2 class="h6">Autorizações / agenda encontradas</h2>
                <?php if ($resultados === []): ?>
                    <p class="text-muted small">Nenhuma autorização vigente hoje para essa busca.</p>
                <?php else: ?>
                    <div class="table-responsive d-none d-md-block list-desktop">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Visitante</th>
                                    <th>Documento</th>
                                    <th>Protocolo</th>
                                    <th>Anfitrião</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $a['visitante_nome']) ?></td>
                                        <td><?= htmlspecialchars((string) ($a['visitante_documento'] ?? '—')) ?></td>
                                        <td><?= htmlspecialchars((string) $a['protocolo']) ?></td>
                                        <td><?= htmlspecialchars((string) ($a['anfitriao_nome'] ?? '—')) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span></td>
                                        <td>
                                            <a class="btn btn-sm btn-success"
                                               href="<?= $url ?>portaria-movimentacoes?<?= http_build_query([
                                                   'busca' => $busca,
                                                   'visitante_id' => (int) $a['visitante_id'],
                                                   'autorizacao_id' => (int) $a['id'],
                                               ]) ?>">Selecionar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none list-mobile">
                        <?php foreach ($resultados as $a): ?>
                            <div class="card mb-2 shadow-sm">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between gap-2 mb-1">
                                        <strong><?= htmlspecialchars((string) $a['visitante_nome']) ?></strong>
                                        <span class="badge bg-secondary"><?= htmlspecialchars((string) $a['status']) ?></span>
                                    </div>
                                    <div class="small mb-1"><b>Doc:</b> <?= htmlspecialchars((string) ($a['visitante_documento'] ?? '—')) ?></div>
                                    <div class="small mb-1"><b>Protocolo:</b> <?= htmlspecialchars((string) $a['protocolo']) ?></div>
                                    <div class="small mb-2"><b>Anfitrião:</b> <?= htmlspecialchars((string) ($a['anfitriao_nome'] ?? '—')) ?></div>
                                    <a class="btn btn-sm btn-success w-100"
                                       href="<?= $url ?>portaria-movimentacoes?<?= http_build_query([
                                           'busca' => $busca,
                                           'visitante_id' => (int) $a['visitante_id'],
                                           'autorizacao_id' => (int) $a['id'],
                                       ]) ?>">Selecionar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($visitantesBusca !== []): ?>
                    <h2 class="h6 mt-3">Visitantes cadastrados (sem filtrar agenda)</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($visitantesBusca as $v): ?>
                            <a class="btn btn-outline-primary btn-sm"
                               href="<?= $url ?>portaria-movimentacoes?<?= http_build_query([
                                   'busca' => $busca,
                                   'visitante_id' => (int) $v['id'],
                               ]) ?>">
                                <?= htmlspecialchars((string) $v['nome']) ?>
                                <?php if (!empty($v['documento'])): ?>
                                    <span class="text-muted">(<?= htmlspecialchars((string) $v['documento']) ?>)</span>
                                <?php endif; ?>
                                — <?= htmlspecialchars((string) ($v['termo_status'] ?? 'ausente')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($termoPendente && $visitanteId > 0): ?>
        <div class="card border-warning shadow-sm mb-3">
            <div class="card-header bg-warning-subtle">2. Assinatura do termo (obrigatória antes da entrada)</div>
            <div class="card-body">
                <p class="mb-2">
                    <?= htmlspecialchars((string) ($this->data['warning'] ?? 'O visitante não possui termo vigente. Solicite a assinatura antes de registrar a entrada.')) ?>
                </p>
                <?php if ($termoAtivo): ?>
                    <div class="border rounded p-3 mb-3 bg-light" style="max-height: 280px; overflow: auto;">
                        <h3 class="h6"><?= htmlspecialchars((string) ($termoAtivo['titulo'] ?? 'Termo de acesso')) ?>
                            <span class="text-muted">(v. <?= htmlspecialchars((string) ($termoAtivo['versao'] ?? '')) ?>)</span>
                        </h3>
                        <div class="small"><?= (string) ($termoAtivo['conteudo'] ?? '') ?></div>
                    </div>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
                        <input type="hidden" name="visitante_id" value="<?= $visitanteId ?>">
                        <input type="hidden" name="autorizacao_id" value="<?= $autorizacaoId ?>">
                        <input type="hidden" name="observacoes" value="<?= htmlspecialchars((string) ($form['observacoes'] ?? '')) ?>">
                        <div class="col-md-4">
                            <label class="form-label">Ponto de controle *</label>
                            <select required class="form-select" name="ponto_controle_id">
                                <option value="">Selecione</option>
                                <?php foreach (($this->data['pontos'] ?? []) as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= $pontoId === (int) $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $p['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Validade (meses)</label>
                            <input type="number" min="1" max="60" class="form-control" name="validade_meses" value="12">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Observações do aceite</label>
                            <input class="form-control" name="termo_observacoes" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <div class="form-check mb-2">
                                <input required class="form-check-input" type="checkbox" name="aceite_termo" value="1" id="aceite_termo">
                                <label class="form-check-label" for="aceite_termo">
                                    Visitante leu e aceita o termo de acesso às dependências
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input required class="form-check-input" type="checkbox" name="conferencia_identidade" value="1" id="conf_id">
                                <label class="form-check-label" for="conf_id">
                                    Confirmei a identidade por documento oficial com foto
                                </label>
                            </div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button name="acao" value="formalizar_termo_e_entrar" class="btn btn-warning">
                                Assinar termo e registrar entrada
                            </button>
                            <button name="acao" value="formalizar_termo" class="btn btn-outline-warning">
                                Só assinar o termo
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">
                        Cadastre um termo ativo do tipo <strong>Acesso às Dependências (Portaria)</strong>
                        em <a href="<?= $url ?>lgpd-termos-create">LGPD → Termos</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (!empty($this->data['warning']) && !empty($this->data['entrada_aberta'])): ?>
        <div class="alert alert-warning">
            <strong><?= htmlspecialchars((string) $this->data['warning']) ?></strong>
            <form method="post" class="row g-2 mt-2">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
                <?php foreach (['visitante_id', 'autorizacao_id', 'ponto_controle_id', 'observacoes'] as $field): ?>
                    <input type="hidden" name="<?= $field ?>" value="<?= htmlspecialchars((string) ($form[$field] ?? '')) ?>">
                <?php endforeach; ?>
                <input type="hidden" name="acao" value="regularizar">
                <div class="col-md-8">
                    <input required class="form-control" name="motivo_regularizacao" placeholder="Motivo da saída não registrada">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning w-100">Regularizar saída e entrar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">3. Registrar entrada ou saída</div>
        <div class="card-body">
            <?php if ($visitante): ?>
                <div class="alert alert-light border mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <strong><?= htmlspecialchars((string) $visitante['nome']) ?></strong>
                            <?php if (!empty($visitante['documento'])): ?>
                                <span class="text-muted">— <?= htmlspecialchars((string) $visitante['documento']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($termoVigente): ?>
                                <span class="badge bg-success">Termo vigente até <?= htmlspecialchars((string) $termoVigente['valido_ate']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Sem termo vigente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Busque e selecione o visitante (documento ou nome) para continuar.</p>
            <?php endif; ?>

            <p class="text-muted small mb-3">
                A <strong>entrada</strong> exige termo vigente: se não houver aceite no prazo, o sistema solicita a assinatura primeiro.
                A <strong>saída</strong> nunca é bloqueada.
            </p>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
                <input type="hidden" name="visitante_id" value="<?= $visitanteId ?>">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Autorização / agenda</label>
                        <select class="form-select" name="autorizacao_id" <?= $visitanteId <= 0 ? 'disabled' : '' ?>>
                            <option value="">Sem vínculo</option>
                            <?php foreach ($autorizacoesVisitante as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $autorizacaoId === (int) $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $a['protocolo'] . ' — ' . (string) $a['status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ponto *</label>
                        <select required class="form-select" name="ponto_controle_id" <?= $visitanteId <= 0 ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['pontos'] ?? []) as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= $pontoId === (int) $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $p['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Observações</label>
                        <input class="form-control" name="observacoes" value="<?= htmlspecialchars((string) ($form['observacoes'] ?? '')) ?>" <?= $visitanteId <= 0 ? 'disabled' : '' ?>>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button name="acao" value="entrada" class="btn btn-success flex-fill flex-md-grow-0" <?= $visitanteId <= 0 ? 'disabled' : '' ?>>
                        Registrar entrada
                    </button>
                    <button name="acao" value="saida" class="btn btn-danger flex-fill flex-md-grow-0" <?= $visitanteId <= 0 ? 'disabled' : '' ?>>
                        Registrar saída
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Presentes agora</div>
        <div class="card-body">
            <?php $presentes = $this->data['presentes'] ?? []; ?>
            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Visitante</th>
                            <th>Entrada</th>
                            <th>Ponto</th>
                            <th>Protocolo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($presentes === []): ?>
                            <tr><td colspan="4" class="text-muted text-center">Nenhum visitante presente.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($presentes as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $p['visitante_nome']) ?></td>
                                <td><?= htmlspecialchars((string) $p['ocorrido_em']) ?></td>
                                <td><?= htmlspecialchars((string) ($p['ponto_nome'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['protocolo'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-block d-md-none list-mobile">
                <?php if ($presentes === []): ?>
                    <p class="text-muted text-center mb-0">Nenhum visitante presente.</p>
                <?php endif; ?>
                <?php foreach ($presentes as $p): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body py-3">
                            <h6 class="mb-2"><?= htmlspecialchars((string) $p['visitante_nome']) ?></h6>
                            <div class="small mb-1"><b>Entrada:</b> <?= htmlspecialchars((string) $p['ocorrido_em']) ?></div>
                            <div class="small mb-1"><b>Ponto:</b> <?= htmlspecialchars((string) ($p['ponto_nome'] ?? '—')) ?></div>
                            <div class="small mb-0"><b>Protocolo:</b> <?= htmlspecialchars((string) ($p['protocolo'] ?? '—')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
