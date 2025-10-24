<?php
$model = $this->data['model'];
$questions = $this->data['questions'] ?? [];
$totalPontos = $this->data['total_pontos'] ?? 0;
$estatisticas = $this->data['estatisticas_status'] ?? [];
$atribuicoesRecentes = $this->data['atribuicoes_recentes'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($model['titulo']) ?>
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- DADOS DO MODELO -->
    <div class="card mb-4 border-primary shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações do Modelo</h5>
            <div>
                <?php if (in_array('UpdateEvaluationModel', $this->data['buttonPermission'])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>update-evaluation-model/<?= $model['id'] ?>" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                <?php endif; ?>
                
                <?php if (in_array('AssignEvaluation', $this->data['buttonPermission'])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>assign-evaluation/<?= $model['id'] ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus"></i> Atribuir
                    </a>
                <?php endif; ?>
                
                <!-- Botão de impressão sempre visível -->
                <a href="<?= $_ENV['URL_ADM'] ?>print-evaluation-blank/<?= $model['id'] ?>" 
                   class="btn btn-info btn-sm" target="_blank">
                    <i class="fas fa-print"></i> Imprimir Formulário
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Treinamento:</strong> <?= htmlspecialchars($model['training_name'] ?? 'N/A') ?></p>
                    <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($model['descricao'] ?? '')) ?></p>
                    <p><strong>Total de Questões:</strong> <?= count($questions) ?></p>
                    <p><strong>Total de Pontos:</strong> <?= number_format($totalPontos, 2) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nota Mínima:</strong> <?= $model['nota_minima_aprovacao'] ?? '7.00' ?></p>
                    <p><strong>Permitir Refazer:</strong> <?= ($model['permitir_refazer'] ?? 1) ? 'Sim' : 'Não' ?></p>
                    <p><strong>Máx Tentativas:</strong> <?= $model['max_tentativas'] ?? 'Ilimitado' ?></p>
                    <p><strong>Mostrar Gabarito:</strong> <?= ($model['mostrar_gabarito'] ?? 1) ? 'Sim' : 'Não' ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= ($model['ativo'] ?? 1) ? 'success' : 'secondary' ?>">
                            <?= ($model['ativo'] ?? 1) ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTATÍSTICAS -->
    <?php if (!empty($estatisticas)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <h4><i class="fas fa-chart-bar"></i> Estatísticas de Atribuições</h4>
            </div>
            <?php foreach ($estatisticas as $status => $total): ?>
                <div class="col-md-2 mb-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="mb-0"><?= $total ?></h3>
                            <small><?= ucfirst($status) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- QUESTÕES -->
    <div class="card mb-4 shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-question-circle"></i> Questões (<?= count($questions) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <div class="alert alert-warning">
                    Este modelo não possui questões cadastradas.
                </div>
            <?php else: ?>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="card mb-2">
                        <div class="card-header bg-light d-flex justify-content-between">
                            <span><strong><?= $index + 1 ?>.</strong> <?= htmlspecialchars($q['pergunta']) ?></span>
                            <span class="badge bg-secondary"><?= number_format($q['pontos'], 2) ?> pts</span>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Tipo:</strong> <?= ucfirst(str_replace('_', ' ', $q['tipo'])) ?></p>
                            <?php if ($q['resposta_correta']): ?>
                                <p class="mb-1">
                                    <i class="fas fa-check text-success"></i> 
                                    <strong>Gabarito:</strong> <?= htmlspecialchars($q['resposta_correta']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($q['explicacao']): ?>
                                <p class="mb-0"><strong>Explicação:</strong> <?= htmlspecialchars($q['explicacao']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mb-4">
        <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>
