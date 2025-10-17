<?php
$indicator = $this->data['indicator'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Indicador Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/adms/strategic-indicators-list" class="text-decoration-none">Indicadores Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-chart-line me-2"></i>Editar Indicador Estratégico</span>
        </div>
        <div class="card-body">
            <form method="post" action="/adms/strategic-indicators-edit/<?= $indicator['id'] ?? '' ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">
                            <strong>Nome do Indicador *</strong>
                        </label>
                        <input type="text" 
                               name="nome" 
                               id="nome" 
                               class="form-control" 
                               placeholder="Digite o nome do indicador"
                               value="<?= htmlspecialchars($indicator['nome'] ?? '') ?>"
                               required>
                        <div class="form-text">Nome descritivo do indicador estratégico</div>
                    </div>

                    <div class="col-md-6">
                        <label for="tipo" class="form-label">
                            <strong>Tipo *</strong>
                        </label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            <option value="Quantitativo" <?= ($indicator['tipo'] ?? '') == 'Quantitativo' ? 'selected' : '' ?>>Quantitativo</option>
                            <option value="Qualitativo" <?= ($indicator['tipo'] ?? '') == 'Qualitativo' ? 'selected' : '' ?>>Qualitativo</option>
                        </select>
                        <div class="form-text">Tipo de medição do indicador</div>
                    </div>

                    <div class="col-md-6">
                        <label for="unidade_medida" class="form-label">
                            <strong>Unidade de Medida</strong>
                        </label>
                        <input type="text" 
                               name="unidade_medida" 
                               id="unidade_medida" 
                               class="form-control" 
                               placeholder="Ex: %, unidades, R$, etc."
                               value="<?= htmlspecialchars($indicator['unidade_medida'] ?? '') ?>">
                        <div class="form-text">Unidade de medida do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="meta" class="form-label">
                            <strong>Meta</strong>
                        </label>
                        <input type="text" 
                               name="meta" 
                               id="meta" 
                               class="form-control" 
                               placeholder="Ex: 95%, 1000 unidades"
                               value="<?= htmlspecialchars($indicator['meta'] ?? '') ?>">
                        <div class="form-text">Meta ou objetivo do indicador (opcional)</div>
                    </div>

                    <div class="col-md-12">
                        <label for="descricao" class="form-label">
                            <strong>Descrição</strong>
                        </label>
                        <textarea name="descricao" 
                                  id="descricao" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Descreva o indicador e como ele será medido"><?= htmlspecialchars($indicator['descricao'] ?? '') ?></textarea>
                        <div class="form-text">Descrição detalhada do indicador (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="frequencia_medicao" class="form-label">
                            <strong>Frequência de Medição</strong>
                        </label>
                        <select name="frequencia_medicao" id="frequencia_medicao" class="form-select">
                            <option value="">Selecione a frequência</option>
                            <option value="Diária" <?= ($indicator['frequencia_medicao'] ?? '') == 'Diária' ? 'selected' : '' ?>>Diária</option>
                            <option value="Semanal" <?= ($indicator['frequencia_medicao'] ?? '') == 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="Mensal" <?= ($indicator['frequencia_medicao'] ?? '') == 'Mensal' ? 'selected' : '' ?>>Mensal</option>
                            <option value="Trimestral" <?= ($indicator['frequencia_medicao'] ?? '') == 'Trimestral' ? 'selected' : '' ?>>Trimestral</option>
                            <option value="Anual" <?= ($indicator['frequencia_medicao'] ?? '') == 'Anual' ? 'selected' : '' ?>>Anual</option>
                        </select>
                        <div class="form-text">Com que frequência o indicador será medido (opcional)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            <strong>Status *</strong>
                        </label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">Selecione o status</option>
                            <option value="Ativo" <?= ($indicator['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($indicator['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                        <div class="form-text">Status atual do indicador</div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                            <a href="/adms/view-strategic-indicator/<?= $indicator['id'] ?? '' ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Visualizar
                            </a>
                            <a href="/adms/strategic-indicators-list" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>



