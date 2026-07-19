<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_create_rh_vaga');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Vaga</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="text-decoration-none">Vagas</a>
            </li>
            <li class="breadcrumb-item active">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <i class="fas fa-briefcase me-2"></i>Nova Vaga de Emprego
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="titulo" class="form-label">Título da Vaga *</label>
                        <input type="text" name="form[titulo]" id="titulo" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['titulo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="area_id" class="form-label">Área/Departamento</label>
                        <select name="form[area_id]" id="area_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['departments'] as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($this->data['form']['area_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="cargo_id" class="form-label">Cargo</label>
                        <select name="form[cargo_id]" id="cargo_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['positions'] as $pos): ?>
                                <option value="<?= $pos['id'] ?>" <?= ($this->data['form']['cargo_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="descricao" class="form-label">Descrição da Vaga</label>
                        <textarea name="form[descricao]" id="descricao" class="form-control" rows="5"><?= htmlspecialchars($this->data['form']['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="requisitos" class="form-label">Requisitos</label>
                        <textarea name="form[requisitos]" id="requisitos" class="form-control" rows="4"><?= htmlspecialchars($this->data['form']['requisitos'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-2">
                        <label for="beneficios" class="form-label">Benefícios</label>
                        <textarea name="form[beneficios]" id="beneficios" class="form-control" rows="3"><?= htmlspecialchars($this->data['form']['beneficios'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label for="tipo_contrato" class="form-label">Tipo de Contrato</label>
                        <?php
                        $tipoAtual = $this->data['form']['tipo_contrato'] ?? 'CLT';
                        $tipos = [
                            'CLT'       => 'CLT',
                            'PJ'        => 'PJ',
                            'Estágio'   => 'Estágio',
                            'Temporário'=> 'Temporário',
                            'Freelancer'=> 'Freelancer',
                        ];
                        ?>
                        <select name="form[tipo_contrato]" id="tipo_contrato" class="form-select">
                            <?php foreach ($tipos as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $tipoAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="salario_min" class="form-label">Salário Mín.</label>
                        <input type="number" name="form[salario_min]" id="salario_min" class="form-control" step="0.01"
                               value="<?= htmlspecialchars($this->data['form']['salario_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="salario_max" class="form-label">Salário Máx.</label>
                        <input type="number" name="form[salario_max]" id="salario_max" class="form-control" step="0.01"
                               value="<?= htmlspecialchars($this->data['form']['salario_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="mostrar_salario" class="form-label">Mostrar Salário</label>
                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" name="form[mostrar_salario]" id="mostrar_salario" class="form-check-input" value="1"
                                   <?= !empty($this->data['form']['mostrar_salario']) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="publicada" class="form-label">Publicar (portal)</label>
                        <div class="form-check form-switch mt-2">
                            <input type="checkbox" name="form[publicada]" id="publicada" class="form-check-input" value="1"
                                   <?= !empty($this->data['form']['publicada']) ? 'checked' : '' ?>>
                        </div>
                        <div class="form-text">Só com status Aberta. Aparece em <code>vagas-abertas</code> (somente leitura).</div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="quantidade_vagas" class="form-label">Quantidade de Vagas</label>
                        <input type="number" name="form[quantidade_vagas]" id="quantidade_vagas" class="form-control" min="1" value="<?= $this->data['form']['quantidade_vagas'] ?? 1 ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label for="local_trabalho" class="form-label">Local de Trabalho</label>
                        <input type="text" name="form[local_trabalho]" id="local_trabalho" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['local_trabalho'] ?? '') ?>" placeholder="Ex: Presencial, Remoto, Híbrido">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="jornada_trabalho" class="form-label">Jornada de Trabalho</label>
                        <input type="text" name="form[jornada_trabalho]" id="jornada_trabalho" class="form-control"
                               value="<?= htmlspecialchars($this->data['form']['jornada_trabalho'] ?? '') ?>" placeholder="Ex: 40h, 44h, Parcial">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="data_limite_inscricao" class="form-label">Data Limite de Inscrição</label>
                        <input type="datetime-local" name="form[data_limite_inscricao]" id="data_limite_inscricao" class="form-control"
                               value="<?= !empty($this->data['form']['data_limite_inscricao']) ? date('Y-m-d\TH:i', strtotime($this->data['form']['data_limite_inscricao'])) : '' ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="status" class="form-label">Status</label>
                        <?php
                        $statusAtual = $this->data['form']['status'] ?? 'aberta';
                        $statusLista = [
                            'aberta'   => 'Aberta',
                            'pausada'  => 'Pausada',
                            'fechada'  => 'Fechada',
                            'cancelada'=> 'Cancelada',
                        ];
                        ?>
                        <select name="form[status]" id="status" class="form-select">
                            <?php foreach ($statusLista as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="responsavel_id" class="form-label">Responsável pela Vaga</label>
                        <select name="form[responsavel_id]" id="responsavel_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($this->data['form']['responsavel_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="form[observacoes]" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($this->data['form']['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="mt-3 d-flex justify-content-end gap-2">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="btn btn-secondary btn-sm">
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

