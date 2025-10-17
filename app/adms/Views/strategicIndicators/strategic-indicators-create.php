<?php
$plans = $this->data['plans'] ?? [];
$users = $this->data['users'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Indicador Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-list" class="text-decoration-none">Indicadores Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-chart-line me-2"></i>Cadastrar Indicador Estratégico</span>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="strategic_plan_id" class="form-label">
                            <strong>Plano Estratégico *</strong>
                        </label>
                        <select name="strategic_plan_id" id="strategic_plan_id" class="form-select" required>
                            <option value="">Selecione o plano estratégico</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Plano estratégico relacionado</div>
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">
                            <strong>Nome do Indicador *</strong>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               class="form-control" 
                               placeholder="Digite o nome do indicador"
                               required>
                        <div class="form-text">Nome descritivo do indicador estratégico</div>
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label">
                            <strong>Descrição</strong>
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Descreva o indicador e como ele será medido"></textarea>
                        <div class="form-text">Descrição detalhada do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="target_value" class="form-label">
                            <strong>Meta</strong>
                        </label>
                        <input type="text" 
                               name="target_value" 
                               id="target_value" 
                               class="form-control" 
                               placeholder="Ex: 95%, 1000 unidades">
                        <div class="form-text">Meta ou objetivo do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="current_value" class="form-label">
                            <strong>Valor Atual</strong>
                        </label>
                        <input type="text" 
                               name="current_value" 
                               id="current_value" 
                               class="form-control" 
                               placeholder="Ex: 85%, 750 unidades">
                        <div class="form-text">Valor atual do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="unit" class="form-label">
                            <strong>Unidade de Medida</strong>
                        </label>
                        <input type="text" 
                               name="unit" 
                               id="unit" 
                               class="form-control" 
                               placeholder="Ex: %, unidades, R$, etc.">
                        <div class="form-text">Unidade de medida do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="frequency" class="form-label">
                            <strong>Frequência de Medição</strong>
                        </label>
                        <select name="frequency" id="frequency" class="form-select">
                            <option value="">Selecione a frequência</option>
                            <option value="Diária">Diária</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Mensal">Mensal</option>
                            <option value="Trimestral">Trimestral</option>
                            <option value="Anual">Anual</option>
                        </select>
                        <div class="form-text">Com que frequência o indicador será medido (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="responsible_id" class="form-label">
                            <strong>Responsável</strong>
                        </label>
                        <select name="responsible_id" id="responsible_id" class="form-select">
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pessoa responsável pelo indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            <strong>Status *</strong>
                        </label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Ativo" selected>Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                        <div class="form-text">Status atual do indicador</div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-list" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
