<?php
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Candidato</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="text-decoration-none">Currículos</a>
            </li>
            <li class="breadcrumb-item active">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <i class="fas fa-id-card me-2"></i>Novo Candidato
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="" enctype="multipart/form-data">
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
                    <label for="curriculo" class="form-label">Currículo (PDF, DOC, DOCX)</label>
                    <input type="file" name="curriculo" id="curriculo" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">Opcional. Máximo conforme configuração do servidor.</small>
                </div>

                <div class="mt-3 d-flex justify-content-end gap-2">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="btn btn-secondary btn-sm">
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


