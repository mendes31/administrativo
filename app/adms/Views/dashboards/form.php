<?php
$this->layout('layouts/main', ['pageTitle' => ($dashboard ? 'Editar' : 'Criar') . ' Dashboard KPI']); ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $dashboard ? 'Editar' : 'Criar' ?> Dashboard de KPI</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-kpi-dashboards">Dashboards KPI</a></li>
        <li class="breadcrumb-item active"><?= $dashboard ? 'Editar' : 'Criar' ?></li>
    </ol>

    <?= $_SESSION['msg'] ?? ''; unset($_SESSION['msg']); ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Informações do Dashboard
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?><?= $dashboard ? 'update-kpi-dashboard' : 'create-kpi-dashboard' ?>" id="dashboardForm">
                <?php if ($dashboard): ?>
                    <input type="hidden" name="id" value="<?= $dashboard['id'] ?>">
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="name" class="form-label">Nome do Dashboard *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($dashboard['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="layout" class="form-label">Layout</label>
                        <select class="form-select" id="layout" name="layout">
                            <option value="grid" <?= ($dashboard['layout'] ?? 'grid') === 'grid' ? 'selected' : '' ?>>Grade (Grid)</option>
                            <option value="flex" <?= ($dashboard['layout'] ?? '') === 'flex' ? 'selected' : '' ?>>Flexível</option>
                            <option value="custom" <?= ($dashboard['layout'] ?? '') === 'custom' ? 'selected' : '' ?>>Personalizado</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($dashboard['description'] ?? '') ?></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="refresh_interval" class="form-label">Intervalo de Atualização (segundos)</label>
                        <input type="number" class="form-control" id="refresh_interval" name="refresh_interval" 
                               value="<?= $dashboard['refresh_interval'] ?? '' ?>" min="0" step="1">
                        <small class="text-muted">0 = sem atualização automática</small>
                    </div>
                    <div class="col-md-6">
                        <label for="is_public" class="form-label">Visibilidade</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                                   <?= ($dashboard['is_public'] ?? false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_public">
                                Dashboard público (visível para todos)
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h4>Widgets (KPIs)</h4>
                <p class="text-muted">Configure os indicadores que serão exibidos no dashboard</p>

                <div id="widgets-container">
                    <!-- Widgets serão adicionados aqui dinamicamente -->
                </div>

                <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addWidget()">
                    <i class="fas fa-plus"></i> Adicionar Widget
                </button>

                <hr class="my-4">

                <div class="d-flex justify-content-between">
                    <a href="<?= $_ENV['URL_ADM'] ?>list-kpi-dashboards" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Dashboard
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let widgetIndex = 0;
const reports = <?= json_encode($reports ?? []) ?>;

function addWidget() {
    const container = document.getElementById('widgets-container');
    const index = widgetIndex++;
    
    const widgetHtml = `
        <div class="card mb-3 widget-item" data-index="${index}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Widget #${index + 1}</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeWidget(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label>Título *</label>
                        <input type="text" class="form-control" name="widgets[${index}][title]" required>
                    </div>
                    <div class="col-md-6">
                        <label>Relatório Vinculado</label>
                        <select class="form-select" name="widgets[${index}][report_id]">
                            <option value="">-- Selecione um relatório --</option>
                            ${reports.map(r => `<option value="${r.id}">${r.name}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>Tipo de Widget</label>
                        <select class="form-select" name="widgets[${index}][widget_type]">
                            <option value="number">Número</option>
                            <option value="chart_bar">Gráfico de Barras</option>
                            <option value="chart_line">Gráfico de Linhas</option>
                            <option value="chart_pie">Gráfico de Pizza</option>
                            <option value="chart_doughnut">Gráfico Rosca</option>
                            <option value="table">Tabela</option>
                            <option value="gauge">Medidor</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Tamanho</label>
                        <select class="form-select" name="widgets[${index}][size]">
                            <option value="small">Pequeno</option>
                            <option value="medium" selected>Médio</option>
                            <option value="large">Grande</option>
                            <option value="full">Largura Total</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Esquema de Cores</label>
                        <select class="form-select" name="widgets[${index}][color_scheme]">
                            <option value="primary">Azul (Primary)</option>
                            <option value="success">Verde (Success)</option>
                            <option value="danger">Vermelho (Danger)</option>
                            <option value="warning">Amarelo (Warning)</option>
                            <option value="info">Ciano (Info)</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>Formato do Valor</label>
                        <select class="form-select" name="widgets[${index}][value_format]">
                            <option value="number">Número</option>
                            <option value="currency">Moeda (R$)</option>
                            <option value="percentage">Porcentagem (%)</option>
                            <option value="text">Texto</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Ícone (FontAwesome)</label>
                        <input type="text" class="form-control" name="widgets[${index}][icon]" 
                               placeholder="fas fa-chart-line">
                    </div>
                    <div class="col-md-4">
                        <label>Valor Meta (opcional)</label>
                        <input type="number" class="form-control" name="widgets[${index}][target_value]" 
                               step="0.01">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', widgetHtml);
}

function removeWidget(index) {
    const widget = document.querySelector(`[data-index="${index}"]`);
    if (widget) {
        widget.remove();
    }
}

// Adicionar primeiro widget automaticamente
if (document.getElementById('widgets-container').children.length === 0) {
    addWidget();
}
</script>

