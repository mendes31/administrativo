<?php
use App\adms\Helpers\FormatHelper;

$s = $this->data['solicitacao'] ?? [];
$catalogo = $this->data['catalogo'] ?? [];
$direitos = $this->data['direitos'] ?? [];
$h = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
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
        <div class="col-lg-7">
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
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><strong>Atendimento (prazo 15 dias)</strong></div>
                <div class="card-body">
                    <p class="small text-muted">Prazo: <strong><?php echo !empty($s['prazo']) ? FormatHelper::formatDate($s['prazo'], 'd/m/Y') : '—'; ?></strong>
                        · Status atual: <?php echo $h($s['status'] ?? ''); ?></p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $h($this->data['csrf_token'] ?? ''); ?>">
                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['Pendente', 'Em andamento', 'Concluída', 'Vencida'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo (($s['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="form-label">Nota interna / resposta</label>
                            <textarea class="form-control" name="observacao_atendimento" rows="5"><?php echo $h($s['observacao_atendimento'] ?? ''); ?></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Guardar atendimento</button>
                        <a class="btn btn-secondary" href="<?php echo $_ENV['URL_ADM']; ?>lgpd-solicitacoes-titulares">Voltar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
