<?php
use App\adms\Helpers\FormatHelper;

$s = $this->data['solicitacao'] ?? [];
$catalogo = $this->data['catalogo'] ?? [];
$direitos = $this->data['direitos'] ?? [];
$comunicacoes = $this->data['comunicacoes'] ?? [];
$destinatario = $this->data['destinatario'] ?? ['email' => '', 'nome' => ''];
$remetente = $this->data['remetente'] ?? ['email' => 'encarregado@tiaraju.com.br', 'nome' => 'Encarregado'];
$templates = $this->data['templates'] ?? [];
$h = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$tipoLabels = [
    'contatar' => 'Contato',
    'solicitar_info' => 'Pedido de informações',
    'responder' => 'Resposta',
    'finalizar' => 'Finalização',
    'interno' => 'Nota interna',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user-shield text-info"></i>
            <?php echo $h($s['protocolo'] ?? 'Solicitação'); ?>
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-solicitacoes-titulares" class="text-decoration-none">Solicitações</a></li>
            <li class="breadcrumb-item">Detalhe</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <strong>Titular</strong>
                    <?php
                    $log_resumo = $this->data['log_resumo'] ?? [];
                    $log_btn_class = 'btn btn-outline-info btn-sm';
                    include __DIR__ . '/../../partials/button_log_alteracoes.php';
                    ?>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nome</dt><dd class="col-sm-8"><?php echo $h($s['titular_nome'] ?? ''); ?></dd>
                        <dt class="col-sm-4">CPF</dt><dd class="col-sm-8"><?php echo $h($s['titular_cpf'] ?? '—'); ?></dd>
                        <dt class="col-sm-4">E-mail</dt><dd class="col-sm-8"><?php echo $h($s['titular_email'] ?? ''); ?></dd>
                        <dt class="col-sm-4">Telefone</dt><dd class="col-sm-8"><?php echo $h($s['titular_telefone'] ?? '—'); ?></dd>
                        <dt class="col-sm-4">Nascimento</dt>
                        <dd class="col-sm-8"><?php echo !empty($s['titular_nascimento']) ? FormatHelper::formatDate($s['titular_nascimento'], 'd/m/Y') : '—'; ?></dd>
                        <dt class="col-sm-4">Categoria</dt>
                        <dd class="col-sm-8"><?php echo $h($s['titular_categoria'] ?? '—'); ?>
                            <?php if (!empty($s['titular_categoria_outro'])): ?>
                                (<?php echo $h($s['titular_categoria_outro']); ?>)
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Endereço</dt><dd class="col-sm-8"><?php echo nl2br($h($s['titular_endereco'] ?? '—')); ?></dd>
                        <dt class="col-sm-4">Procurador</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($s['por_procurador'])): ?>
                                <?php echo $h($s['procurador_nome'] ?? ''); ?>
                                · CPF <?php echo $h($s['procurador_cpf'] ?? ''); ?>
                                · <?php echo $h($s['procurador_email'] ?? ''); ?>
                            <?php else: ?>Não<?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Comunicação</dt>
                        <dd class="col-sm-8"><?php echo $h($s['comunicacao_meio'] ?? 'email'); ?>
                            <?php if (!empty($s['comunicacao_outro'])): ?> — <?php echo $h($s['comunicacao_outro']); ?><?php endif; ?>
                        </dd>
                        <dt class="col-sm-4">Informações adicionais</dt>
                        <dd class="col-sm-8"><?php echo nl2br($h($s['informacoes_adicionais'] ?? '—')); ?></dd>
                        <dt class="col-sm-4">IP / entrada</dt>
                        <dd class="col-sm-8"><?php echo $h($s['ip_address'] ?? '—'); ?>
                            · <?php echo !empty($s['created_at']) ? FormatHelper::formatDate($s['created_at'], 'd/m/Y H:i') : ''; ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Direitos assinalados</strong></div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach ($catalogo as $key => $meta): ?>
                            <?php $sim = ($direitos[$key] ?? 'nao') === 'sim'; ?>
                            <li class="<?php echo $sim ? 'fw-semibold' : 'text-muted'; ?>">
                                <?php echo $sim ? 'Sim' : 'Não'; ?> —
                                <?php echo $h($meta['titulo']); ?>
                                <small>(<?php echo $h($meta['artigo']); ?>)</small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Histórico de comunicações</strong></div>
                <div class="card-body">
                    <?php if ($comunicacoes === []): ?>
                        <p class="text-muted mb-0 small">Nenhum e-mail enviado ainda por esta tela.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($comunicacoes as $c): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <strong><?php echo $h($tipoLabels[$c['tipo'] ?? ''] ?? ($c['tipo'] ?? '')); ?></strong>
                                        <span class="small text-muted">
                                            <?php echo !empty($c['created_at']) ? FormatHelper::formatDate($c['created_at'], 'd/m/Y H:i') : ''; ?>
                                            <?php if (!empty($c['user_nome'])): ?> · <?php echo $h($c['user_nome']); ?><?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="small">
                                        Para: <?php echo $h($c['destinatario_email'] ?? '—'); ?>
                                        ·
                                        <?php if (!empty($c['enviado'])): ?>
                                            <span class="text-success">Enviado</span>
                                        <?php else: ?>
                                            <span class="text-danger">Falha</span>
                                            <?php if (!empty($c['erro_envio'])): ?>
                                                — <?php echo $h($c['erro_envio']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted mt-1"><?php echo $h($c['assunto'] ?? ''); ?></div>
                                    <div class="small mt-1" style="white-space:pre-wrap;"><?php echo $h($c['mensagem'] ?? ''); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><strong>Atendimento (prazo 15 dias)</strong></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Prazo: <strong><?php echo !empty($s['prazo']) ? FormatHelper::formatDate($s['prazo'], 'd/m/Y') : '—'; ?></strong>
                        · Status atual: <strong><?php echo $h($s['status'] ?? ''); ?></strong>
                    </p>

                    <div class="alert alert-light border small mb-3">
                        <div>
                            Destinatário:
                            <strong><?php echo $h($destinatario['nome'] ?? ''); ?></strong>
                            &lt;<?php echo $h($destinatario['email'] ?? '—'); ?>&gt;
                            <?php if (!empty($s['por_procurador'])): ?>
                                <span class="badge text-bg-secondary">procurador</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-1">
                            Remetente (From / Reply-To):
                            <strong><?php echo $h($remetente['nome'] ?? ''); ?></strong>
                            &lt;<?php echo $h($remetente['email'] ?? 'encarregado@tiaraju.com.br'); ?>&gt;
                        </div>
                    </div>

                    <form method="post" id="formAtendimentoLgpd">
                        <input type="hidden" name="csrf_token" value="<?php echo $h($this->data['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="acao" id="acaoAtendimento" value="guardar">

                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="statusAtendimento">
                                <?php foreach (['Pendente', 'Em andamento', 'Aguardando titular', 'Concluída', 'Vencida'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo (($s['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Ao enviar e-mail, o status pode ser ajustado automaticamente conforme a ação.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select">
                                <?php foreach (['Baixa', 'Média', 'Alta', 'Crítica'] as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo (($s['prioridade'] ?? '') === $p) ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Responsável</label>
                            <input class="form-control" name="responsavel" value="<?php echo $h($s['responsavel'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nota interna</label>
                            <textarea class="form-control" name="observacao_atendimento" rows="3"
                                      placeholder="Registo interno do andamento (não enviado automaticamente)"><?php echo $h($s['observacao_atendimento'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="mensagem_email">Mensagem ao titular (e-mail)</label>
                            <textarea class="form-control" name="mensagem_email" id="mensagem_email" rows="7"
                                      placeholder="Texto enviado nas ações Contatar / Solicitar info / Responder / Finalizar"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-secondary" type="submit"
                                    onclick="document.getElementById('acaoAtendimento').value='guardar';">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar só atendimento
                            </button>
                            <button class="btn btn-outline-primary" type="submit"
                                    onclick="return prepararEnvio('contatar');">
                                <i class="fa-solid fa-envelope-open-text me-1"></i> Contatar (confirmação)
                            </button>
                            <button class="btn btn-outline-warning" type="submit"
                                    onclick="return prepararEnvio('solicitar_info');">
                                <i class="fa-solid fa-circle-question me-1"></i> Solicitar mais informações
                            </button>
                            <button class="btn btn-outline-info" type="submit"
                                    onclick="return prepararEnvio('responder');">
                                <i class="fa-solid fa-reply me-1"></i> Responder
                            </button>
                            <button class="btn btn-success" type="submit"
                                    onclick="return prepararEnvio('finalizar');">
                                <i class="fa-solid fa-check me-1"></i> Finalizar e enviar resposta
                            </button>
                            <a class="btn btn-secondary" href="<?php echo $_ENV['URL_ADM']; ?>lgpd-solicitacoes-titulares">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const templates = <?php echo json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS); ?>;
    const msg = document.getElementById('mensagem_email');

    window.prepararEnvio = function (acao) {
        document.getElementById('acaoAtendimento').value = acao;
        if (!msg) return true;
        if (!msg.value.trim() && templates[acao]) {
            msg.value = templates[acao];
        }
        if (!msg.value.trim()) {
            alert('Informe a mensagem do e-mail antes de enviar.');
            msg.focus();
            return false;
        }
        const labels = {
            contatar: 'enviar a confirmação de recebimento',
            solicitar_info: 'solicitar mais informações ao titular',
            responder: 'enviar a resposta intermediária',
            finalizar: 'finalizar o atendimento e enviar a resposta'
        };
        return confirm('Confirma ' + (labels[acao] || 'enviar o e-mail') + '?');
    };
})();
</script>
