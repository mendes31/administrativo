<?php

use App\adms\Helpers\CSRFHelper;

$form = $this->data['form'] ?? [];
$url = (string) ($_ENV['URL_ADM'] ?? '');
$modoRapido = !empty($this->data['modo_rapido']);
$visitanteModo = (string) ($form['visitante_modo'] ?? ($modoRapido ? 'novo' : 'existente'));
?>
<div class="container-fluid px-4">
    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-stretch align-items-md-center gap-2">
            <h1 class="h4 mb-0"><?= $modoRapido ? 'Liberação rápida (não programado)' : 'Agendar / cadastrar autorização' ?></h1>
            <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-outline-secondary <?= !$modoRapido ? 'active' : '' ?>" href="<?= $url ?>portaria-autorizacoes-create">Agendar</a>
                <a class="btn btn-outline-success <?= $modoRapido ? 'active' : '' ?>" href="<?= $url ?>portaria-autorizacoes-create?modo=rapido">Não programado</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['errors'])): ?>
                <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $this->data['errors'])) ?></div>
            <?php endif; ?>

            <?php if ($modoRapido): ?>
                <div class="alert alert-info mb-3">
                    Fluxo da portaria: informe o visitante (novo ou já cadastrado), o anfitrião e o motivo.
                    Em seguida notifique o anfitrião (push/WhatsApp) ou registre a ligação na ficha da autorização.
                </div>
            <?php else: ?>
                <p class="text-muted small mb-3">
                    Para visita prevista, use <strong>Agendar</strong>. Para chegada sem aviso, use
                    <strong>Não programado</strong> — pode cadastrar o visitante na hora, sem ir à tela de Visitantes.
                </p>
            <?php endif; ?>

            <form method="post" id="formPortariaAutorizacao">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_portaria_autorizacao') ?>">
                <input type="hidden" name="origem" value="<?= $modoRapido ? 'nao_programada' : 'agendada' ?>">
                <input type="hidden" name="tipo" value="periodo">
                <input type="hidden" name="status" value="aguardando">

                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="fw-semibold mb-2">Visitante</div>
                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="visitante_modo" id="modo_novo" value="novo" <?= $visitanteModo === 'novo' ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-outline-primary" for="modo_novo">Cadastrar agora</label>
                        <input type="radio" class="btn-check" name="visitante_modo" id="modo_existente" value="existente" <?= $visitanteModo === 'existente' ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-outline-primary" for="modo_existente">Já cadastrado</label>
                    </div>

                    <div id="bloco_novo_visitante" class="<?= $visitanteModo === 'novo' ? '' : 'd-none' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome *</label>
                                <input class="form-control" name="novo_nome" value="<?= htmlspecialchars((string) ($form['novo_nome'] ?? '')) ?>" placeholder="Nome completo">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Documento</label>
                                <input class="form-control" name="novo_documento" value="<?= htmlspecialchars((string) ($form['novo_documento'] ?? '')) ?>" placeholder="RG/CPF">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telefone</label>
                                <input class="form-control" name="novo_telefone" value="<?= htmlspecialchars((string) ($form['novo_telefone'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Empresa</label>
                                <input class="form-control" name="novo_empresa" value="<?= htmlspecialchars((string) ($form['novo_empresa'] ?? '')) ?>">
                            </div>
                        </div>
                        <small class="text-muted">Se o documento já existir, o sistema reutiliza o cadastro.</small>
                    </div>

                    <div id="bloco_visitante_existente" class="<?= $visitanteModo === 'existente' ? '' : 'd-none' ?>">
                        <label class="form-label">Selecionar visitante *</label>
                        <select class="form-select" name="visitante_id">
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['visitantes'] ?? []) as $v): ?>
                                <option value="<?= (int) $v['id'] ?>" <?= (int) ($form['visitante_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $v['nome']) ?><?= !empty($v['documento']) ? ' — ' . htmlspecialchars((string) $v['documento']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Anfitrião *</label>
                        <select required class="form-select" name="anfitriao_user_id">
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['usuarios'] ?? []) as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" <?= (int) ($form['anfitriao_user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Departamento de destino</label>
                        <select class="form-select" name="destino_departamento_id">
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['departamentos'] ?? []) as $d): ?>
                                <option value="<?= (int) $d['id'] ?>" <?= (int) ($form['destino_departamento_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data inicial *</label>
                        <input required type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars((string) ($form['data_inicio'] ?? date('Y-m-d'))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data final *</label>
                        <input required type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars((string) ($form['data_fim'] ?? date('Y-m-d'))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hora inicial</label>
                        <input type="time" class="form-control" name="hora_inicio" value="<?= htmlspecialchars((string) ($form['hora_inicio'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hora final</label>
                        <input type="time" class="form-control" name="hora_fim" value="<?= htmlspecialchars((string) ($form['hora_fim'] ?? '')) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Motivo<?= $modoRapido ? ' *' : '' ?></label>
                        <input class="form-control" name="motivo" <?= $modoRapido ? 'required' : '' ?> value="<?= htmlspecialchars((string) ($form['motivo'] ?? '')) ?>" placeholder="<?= $modoRapido ? 'Ex.: reunião, entrega, manutenção' : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Placa do veículo</label>
                        <input class="form-control" name="veiculo_placa" value="<?= htmlspecialchars((string) ($form['veiculo_placa'] ?? '')) ?>">
                    </div>
                    <?php if (!$modoRapido): ?>
                        <div class="col-md-6">
                            <label class="form-label">Dias permitidos</label>
                            <input class="form-control" name="dias_permitidos" placeholder="1,2,3,4,5" value="<?= htmlspecialchars((string) ($form['dias_permitidos'] ?? '')) ?>">
                            <small class="text-muted">1=seg … 7=dom. Vazio = todos os dias do período.</small>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a class="btn btn-secondary" href="<?= $url ?>portaria-autorizacoes">Voltar</a>
                    <button class="btn btn-success">
                        <?= $modoRapido ? 'Registrar e solicitar autorização' : 'Criar autorização' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const novo = document.getElementById('modo_novo');
    const existente = document.getElementById('modo_existente');
    const blocoNovo = document.getElementById('bloco_novo_visitante');
    const blocoExistente = document.getElementById('bloco_visitante_existente');
    function sync() {
        const isNovo = novo && novo.checked;
        if (blocoNovo) blocoNovo.classList.toggle('d-none', !isNovo);
        if (blocoExistente) blocoExistente.classList.toggle('d-none', !!isNovo);
    }
    if (novo) novo.addEventListener('change', sync);
    if (existente) existente.addEventListener('change', sync);
    sync();
})();
</script>
