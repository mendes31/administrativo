<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_create_rh_entrevista');
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Entrevista</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="text-decoration-none">Entrevistas</a></li>
            <li class="breadcrumb-item active">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>Nova Entrevista</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="rh_candidato_id" class="form-label">Candidato *</label>
                        <select name="form[rh_candidato_id]" id="rh_candidato_id" class="form-select" required>
                            <option value="">Selecione o candidato...</option>
                            <?php foreach ($this->data['candidatos'] ?? [] as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ($form['rh_candidato_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nome'] ?? '') ?> (<?= htmlspecialchars($c['email'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="rh_vaga_id" class="form-label">Vaga</label>
                        <select name="form[rh_vaga_id]" id="rh_vaga_id" class="form-select">
                            <option value="">Nenhuma (entrevista geral)</option>
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
                        <label for="entrevistador_id" class="form-label">Entrevistador</label>
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
                               value="<?= htmlspecialchars($form['data_hora'] ?? date('Y-m-d\TH:i')) ?>" required>
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
                            Recebem convite (notificação e e-mail) e só ficam ativos após aceitar. Segure Ctrl/Cmd para selecionar vários.
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="local" class="form-label">Local</label>
                        <input type="text" name="form[local]" id="local" class="form-control"
                               value="<?= htmlspecialchars($form['local'] ?? '') ?>" placeholder="Ex.: Sala 3, Google Meet">
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
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
