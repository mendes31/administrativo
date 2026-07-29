<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_create_rh_candidato');
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
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
                        $origens = \App\adms\Helpers\RhCandidatoOrigemHelper::opcoesSelect();
                        ?>
                        <select name="form[origem]" id="origem" class="form-select">
                            <?php foreach ($origens as $valor => $label): ?>
                                <option value="<?= htmlspecialchars($valor) ?>" <?= $origemAtual === $valor ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Status do Processo</label>
                        <div class="form-control bg-light">Candidatado</div>
                        <input type="hidden" name="form[status_processo]" value="candidatado">
                        <div class="form-text small">
                            Inicia como <strong>Candidatado</strong>. O status geral passa a ser projeção automática conforme os vínculos e o pipeline.
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="area_interesse" class="form-label">Área de Interesse *</label>
                        <?php
                        $areaAtual = $this->data['form']['area_interesse'] ?? '';
                        $areas = [
                            '' => 'Selecione...',
                            'TI / Tecnologia' => 'TI / Tecnologia',
                            'Administração' => 'Administração',
                            'Vendas / Comercial' => 'Vendas / Comercial',
                            'Recursos Humanos' => 'Recursos Humanos',
                            'Financeiro / Contábil' => 'Financeiro / Contábil',
                            'Produção / Operações' => 'Produção / Operações',
                            'Qualidade' => 'Qualidade',
                            'Logística' => 'Logística',
                            'Marketing' => 'Marketing',
                            'Jurídico' => 'Jurídico',
                            'Saúde / Segurança' => 'Saúde / Segurança',
                            'Outro' => 'Outro',
                        ];
                        ?>
                        <select name="form[area_interesse]" id="area_interesse" class="form-select" required>
                            <?php foreach ($areas as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $areaAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Área de atuação de interesse do candidato</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="graduacao" class="form-label">Graduação / Formação *</label>
                        <textarea name="form[graduacao]" id="graduacao" class="form-control" rows="4" required placeholder="Ex: Engenharia de Software - Universidade X - Concluído em 2020"><?= htmlspecialchars($this->data['form']['graduacao'] ?? '') ?></textarea>
                        <small class="text-muted">Informe: Curso, Instituição, Ano de conclusão (ou em andamento)</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="ultima_experiencia" class="form-label">Última Experiência Profissional *</label>
                        <textarea name="form[ultima_experiencia]" id="ultima_experiencia" class="form-control" rows="5" required placeholder="Ex: Analista de Sistemas - Empresa Y - 2020 a 2023&#10;Principais atividades: Desenvolvimento de sistemas, análise de requisitos..."><?= htmlspecialchars($this->data['form']['ultima_experiencia'] ?? '') ?></textarea>
                        <small class="text-muted">Informe: Cargo, Empresa, Período e principais atividades desenvolvidas</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label for="score" class="form-label">Score (0-100)</label>
                        <input type="number" name="form[score]" id="score" class="form-control" 
                               min="0" max="100" 
                               value="<?= htmlspecialchars($this->data['form']['score'] ?? '') ?>">
                        <small class="text-muted">Classificação numérica de 0 a 100</small>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="classificacao" class="form-label">Classificação</label>
                        <?php
                        $classificacaoAtual = $this->data['form']['classificacao'] ?? '';
                        $classificacoes = [
                            '' => 'Selecione...',
                            'Excelente' => 'Excelente',
                            'Muito Bom' => 'Muito Bom',
                            'Bom' => 'Bom',
                            'Regular' => 'Regular',
                            'Baixo' => 'Baixo',
                        ];
                        ?>
                        <select name="form[classificacao]" id="classificacao" class="form-select">
                            <?php foreach ($classificacoes as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $classificacaoAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="classificacao_observacoes" class="form-label">Obs. Classificação</label>
                        <textarea name="form[classificacao_observacoes]" id="classificacao_observacoes" 
                                  class="form-control" rows="2" 
                                  placeholder="Observações sobre a classificação/score"><?= htmlspecialchars($this->data['form']['classificacao_observacoes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="form[observacoes]" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($this->data['form']['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="curriculo" class="form-label">Currículo (PDF, DOC, DOCX)</label>
                    <input type="file" name="curriculo" id="curriculo" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">Opcional. Máximo 10 MB. Arquivo armazenado em área privada.</small>
                </div>

                <?php
                $lgpdTermo = $this->data['lgpd_termo'] ?? null;
                $termoTitulo = htmlspecialchars((string) ($lgpdTermo['titulo'] ?? 'Termo de tratamento de dados — currículos'));
                $termoVersao = htmlspecialchars((string) ($lgpdTermo['versao'] ?? ''));
                ?>
                <div class="mb-3 p-3 border rounded bg-light">
                    <h6 class="mb-2"><i class="fas fa-shield-alt me-1"></i>Consentimento LGPD</h6>
                    <?php if ($lgpdTermo): ?>
                        <p class="small mb-2">
                            Termo ativo: <strong><?= $termoTitulo ?></strong>
                            <?php if ($termoVersao !== ''): ?>
                                (versão <?= $termoVersao ?>)
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($lgpdTermo['conteudo'])): ?>
                            <div class="small text-muted mb-2" style="max-height: 120px; overflow:auto;">
                                <?= nl2br(htmlspecialchars((string) $lgpdTermo['conteudo'])) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="lgpd_consent" name="lgpd_consent"
                                <?= !empty($_POST['lgpd_consent']) ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="lgpd_consent">
                                Declaro que o titular (candidato) consentiu o tratamento dos dados pessoais e do currículo
                                conforme o termo acima, para fins de recrutamento e seleção.
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0 small">
                            Não há termo LGPD ativo do tipo <code>curriculo_candidato</code>.
                            Cadastre o termo no módulo LGPD antes de incluir candidatos.
                        </div>
                    <?php endif; ?>
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


