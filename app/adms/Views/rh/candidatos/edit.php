<?php
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Candidato</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="text-decoration-none">Currículos</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <i class="fas fa-id-card me-2"></i>Editar Candidato
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="">
                <input type="hidden" name="form[id]" value="<?= (int)($this->data['form']['id'] ?? 0) ?>">

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" name="form[nome]" id="nome" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="form[email]" id="email" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" name="form[telefone]" id="telefone" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['telefone'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" name="form[cidade]" id="cidade" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['cidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="estado" class="form-label">UF</label>
                        <input type="text" name="form[estado]" id="estado" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['estado'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="origem" class="form-label">Origem</label>
                        <?php
                        $origemAtual = $this->data['form']['origem'] ?? 'manual';
                        $origens = [
                            'email'                => 'E-mail',
                            'whatsapp'             => 'WhatsApp',
                            'form_trabalhe_conosco'=> 'Trabalhe Conosco',
                            'manual'               => 'Manual',
                            'outro'                => 'Outro',
                        ];
                        ?>
                        <select name="form[origem]" id="origem" class="form-select">
                            <?php foreach ($origens as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $origemAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="status_processo" class="form-label">Status do Processo</label>
                        <?php
                        $statusAtual = $this->data['form']['status_processo'] ?? 'recebido';
                        $statusLista = [
                            'recebido'      => 'Recebido',
                            'em_entrevista' => 'Em entrevista',
                            'reprovado'     => 'Reprovado',
                            'banco_talentos'=> 'Banco de talentos',
                            'contratado'    => 'Contratado',
                            'anonimizado'   => 'Anonimizado',
                        ];
                        ?>
                        <select name="form[status_processo]" id="status_processo" class="form-select">
                            <?php foreach ($statusLista as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="form[observacoes]" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($this->data['form']['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="curriculo" class="form-label">Adicionar novo currículo/anexo (PDF, DOC, DOCX)</label>
                    <input type="file" name="curriculo" id="curriculo" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">Opcional. Será adicionado como um novo anexo ao histórico deste candidato.</small>
                </div>

                <?php if (!empty($this->data['anexos'])): ?>
                    <div class="mb-3">
                        <h5>Anexos existentes</h5>
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
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?= urlencode($anexo['arquivo_caminho']); ?>"
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
                <?php endif; ?>

                <hr class="my-3">
                <h5>Confirmação de alteração (LGPD / Ação sensível)</h5>
                <div class="mb-2">
                    <label for="motivo" class="form-label">Justificativa *</label>
                    <textarea name="motivo" id="motivo" class="form-control" rows="2" required><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
                    <small class="text-muted">Descreva o motivo da alteração deste cadastro.</small>
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">Senha *</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                    <small class="text-muted">Informe sua senha para confirmar a alteração.</small>
                </div>

                <div class="mt-3 d-flex justify-content-end gap-2">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)($this->data['form']['id'] ?? 0) ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


