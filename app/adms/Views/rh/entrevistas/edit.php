<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_edit_rh_entrevista');
$form = $this->data['form'] ?? [];
$dataHora = $form['data_hora'] ?? '';
if ($dataHora && strpos($dataHora, ' ') !== false) {
    $dataHora = str_replace(' ', 'T', substr($dataHora, 0, 16));
} elseif ($dataHora && strlen($dataHora) >= 16) {
    $dataHora = substr($dataHora, 0, 16);
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Entrevista</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="text-decoration-none">Entrevistas</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>Entrevista #<?= (int)($this->data['entrevista']['id'] ?? 0) ?></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Candidato</label>
                        <p class="form-control-plaintext fw-bold">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)($form['rh_candidato_id'] ?? 0) ?>">
                                <?= htmlspecialchars($this->data['entrevista']['candidato_nome'] ?? '') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="rh_vaga_id" class="form-label">Vaga</label>
                        <select name="form[rh_vaga_id]" id="rh_vaga_id" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($this->data['vagas'] ?? [] as $v): ?>
                                <option value="<?= (int)$v['id'] ?>" <?= ($form['rh_vaga_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['titulo'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select name="form[tipo]" id="tipo" class="form-select">
                            <option value="presencial" <?= ($form['tipo'] ?? 'presencial') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                            <option value="online" <?= ($form['tipo'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                            <option value="telefone" <?= ($form['tipo'] ?? '') === 'telefone' ? 'selected' : '' ?>>Telefone</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="entrevistador_id" class="form-label">Entrevistador principal</label>
                        <select name="form[entrevistador_id]" id="entrevistador_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= ($form['entrevistador_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="data_hora" class="form-label">Data e Hora *</label>
                        <input type="datetime-local" name="form[data_hora]" id="data_hora" class="form-control"
                               value="<?= htmlspecialchars($dataHora) ?>" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="resultado" class="form-label">Resultado</label>
                        <select name="form[resultado]" id="resultado" class="form-select">
                            <option value="pendente" <?= in_array($form['resultado'] ?? '', ['', 'pendente']) ? 'selected' : '' ?>>Pendente</option>
                            <option value="agendado" <?= ($form['resultado'] ?? '') === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="aprovado" <?= ($form['resultado'] ?? '') === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                            <option value="reprovado" <?= ($form['resultado'] ?? '') === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="avaliadores_adicionais" class="form-label">Avaliadores adicionais</label>
                        <?php
                        $avaliadoresSel = array_map('intval', (array) ($form['avaliadores_adicionais'] ?? []));
                        $principalSel = (int) ($form['entrevistador_id'] ?? 0);
                        ?>
                        <select name="form[avaliadores_adicionais][]" id="avaliadores_adicionais" class="form-select" multiple size="5">
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <?php
                                $uid = (int) ($u['id'] ?? 0);
                                if ($uid <= 0 || $uid === $principalSel) {
                                    continue;
                                }
                                ?>
                                <option value="<?= $uid ?>" <?= in_array($uid, $avaliadoresSel, true) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Painel interno apenas — não envia convite nem concede permissão automaticamente. Segure Ctrl/Cmd para selecionar vários.
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="local" class="form-label">Local</label>
                        <input type="text" name="form[local]" id="local" class="form-control" value="<?= htmlspecialchars($form['local'] ?? '') ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea name="form[observacoes]" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($form['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="feedback" class="form-label">Feedback</label>
                        <textarea name="form[feedback]" id="feedback" class="form-control" rows="3"><?= htmlspecialchars($form['feedback'] ?? '') ?></textarea>
                    </div>
                </div>

                <?php
                $sc = $this->data['scorecard'] ?? [];
                $scItens = $sc['itens'] ?? [];
                ?>
                <hr class="my-4">
                <h5 class="mb-2"><i class="fas fa-clipboard-check me-2"></i>Scorecard (sua avaliação)</h5>
                <p class="text-muted small mb-3">
                    Notas de 0 a 10 por critério. A nota ponderada é informativa e <strong>não altera</strong> o resultado nem o pipeline.
                    <?php if (!empty($sc['nota_ponderada'])): ?>
                        Nota atual: <strong><?= number_format((float) $sc['nota_ponderada'], 2, ',', '.') ?></strong>
                        (<?= htmlspecialchars($sc['status'] ?? 'rascunho') ?>).
                    <?php endif; ?>
                </p>
                <?php if (empty($scItens)): ?>
                    <p class="text-muted">Critérios indisponíveis. Verifique se a migration do scorecard foi aplicada.</p>
                <?php else: ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Critério</th>
                                    <th style="width:6rem">Peso</th>
                                    <th style="width:8rem">Nota (0–10)</th>
                                    <th>Comentário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scItens as $item): ?>
                                    <?php $codigo = (string) ($item['codigo'] ?? ''); ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($item['label'] ?? $codigo)) ?></td>
                                        <td><?= (int) ($item['peso'] ?? 0) ?></td>
                                        <td>
                                            <input type="number" min="0" max="10" step="1"
                                                   name="scorecard[itens][<?= htmlspecialchars($codigo) ?>][nota]"
                                                   class="form-control form-control-sm"
                                                   value="<?= htmlspecialchars((string) ($item['nota'] ?? '')) ?>">
                                        </td>
                                        <td>
                                            <input type="text" maxlength="500"
                                                   name="scorecard[itens][<?= htmlspecialchars($codigo) ?>][comentario]"
                                                   class="form-control form-control-sm"
                                                   value="<?= htmlspecialchars((string) ($item['comentario'] ?? '')) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label for="scorecard_parecer" class="form-label">Parecer do scorecard</label>
                        <textarea name="scorecard[parecer]" id="scorecard_parecer" class="form-control" rows="2"><?= htmlspecialchars((string) ($sc['parecer'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="scorecard_finalizar"
                               name="scorecard[finalizar]" <?= !empty($sc['finalizar']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="scorecard_finalizar">Marcar scorecard como finalizado</label>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Atualizar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-view/<?= (int)($this->data['entrevista']['id'] ?? 0) ?>" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
