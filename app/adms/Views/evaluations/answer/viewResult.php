<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
$attempt = $this->data['attempt'];
$respostas = $this->data['respostas'];
$aprovado = $this->data['aprovado'];
$feedback = $this->data['feedback'];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-clipboard-check"></i> Resultado da Avaliação
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="text-decoration-none">Minhas Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Resultado</li>
        </ol>
    </div>

    <?php
    $log_resumo = $this->data['log_resumo'] ?? [];
    $log_btn_class = 'btn btn-outline-info btn-sm mb-3';
    include __DIR__ . '/../../partials/button_log_alteracoes.php';
    ?>

    <!-- CARD DE RESULTADO GERAL -->
    <div class="card mb-4 border-<?= $aprovado ? 'success' : 'danger' ?> border-3 shadow-lg">
        <div class="card-header bg-<?= $aprovado ? 'success' : 'danger' ?> text-white">
            <h3 class="mb-0 text-center">
                <?php if ($aprovado): ?>
                    <i class="fas fa-check-circle"></i> APROVADO!
                <?php else: ?>
                    <i class="fas fa-times-circle"></i> REPROVADO
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <!-- ESTATÍSTICAS -->
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <h2 class="text-<?= $aprovado ? 'success' : 'danger' ?> mb-0">
                            <?= number_format($attempt['nota_obtida'], 2) ?>
                        </h2>
                        <p class="text-muted mb-0"><small>Nota Final</small></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <h2 class="text-info mb-0">
                            <?= $attempt['questoes_corretas'] ?>/<?= $attempt['total_questoes'] ?>
                        </h2>
                        <p class="text-muted mb-0"><small>Questões Corretas</small></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <h2 class="text-warning mb-0">
                            <?= number_format($attempt['percentual'], 1) ?>%
                        </h2>
                        <p class="text-muted mb-0"><small>Percentual</small></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <h2 class="text-secondary mb-0">
                            #<?= $attempt['tentativa_numero'] ?>
                        </h2>
                        <p class="text-muted mb-0"><small>Tentativa</small></p>
                    </div>
                </div>
            </div>

            <!-- FEEDBACK -->
            <div class="alert alert-<?= $aprovado ? 'success' : 'warning' ?> mb-3">
                <h5 class="alert-heading"><i class="fas fa-comment-dots"></i> Feedback:</h5>
                <p class="mb-0"><?= htmlspecialchars($feedback) ?></p>
            </div>

            <!-- INFORMAÇÕES ADICIONAIS -->
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Nota Mínima:</strong> <?= number_format($attempt['nota_minima_aprovacao'], 2) ?></p>
                    <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($attempt['data_finalizacao'])) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (!$aprovado && $attempt['permitir_refazer']): ?>
                        <?php
                        $podeFazer = true;
                        if (isset($attempt['max_tentativas']) && $attempt['max_tentativas']) {
                            $podeFazer = $attempt['tentativas'] < $attempt['max_tentativas'];
                        }
                        ?>
                        
                        <?php if ($podeFazer): ?>
                            <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="btn btn-warning btn-lg">
                                <i class="fas fa-redo"></i> Refazer Avaliação
                            </a>
                        <?php else: ?>
                            <div class="alert alert-danger mb-0">
                                Limite de tentativas atingido!
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($attempt['mostrar_gabarito']): ?>
        <!-- CORREÇÃO DETALHADA -->
        <h4 class="mb-3"><i class="fas fa-list-check"></i> Correção Detalhada</h4>
        
        <?php foreach ($respostas as $index => $resp): ?>
            <?php 
            $correta = $resp['correta'] ?? false;
            $avaliavel = $resp['avaliavel'] ?? true;
            ?>
            
            <div class="card mb-3 border-start border-<?= $correta ? 'success' : ($avaliavel ? 'danger' : 'secondary') ?> border-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                        <strong><?= htmlspecialchars($resp['questao_texto']) ?></strong>
                    </div>
                    <div>
                        <span class="badge bg-secondary me-2">
                            <?= number_format($resp['pontos_questao'], 2) ?> pts
                        </span>
                        <?php if ($avaliavel): ?>
                            <?php if ($correta): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> +<?= number_format($resp['pontos_obtidos'], 2) ?> pts
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> 0 pts
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-info-circle"></i> Não avaliada
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- SUA RESPOSTA -->
                    <div class="mb-3">
                        <strong><i class="fas fa-user"></i> Sua Resposta:</strong>
                        <div class="alert alert-<?= $correta ? 'success' : ($avaliavel ? 'danger' : 'secondary') ?> mt-2 mb-0">
                            <?php if (!empty($resp['resposta'])): ?>
                                <?= nl2br(htmlspecialchars($resp['resposta'])) ?>
                            <?php else: ?>
                                <em class="text-muted">Não respondida</em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- GABARITO -->
                    <?php if (!empty($resp['gabarito'])): ?>
                        <div class="mb-3">
                            <strong>
                                <i class="fas fa-key"></i> 
                                <?= $correta ? 'Você acertou! Resposta:' : 'Gabarito:' ?>
                            </strong>
                            <div class="alert alert-success mt-2 mb-0">
                                <?= nl2br(htmlspecialchars($resp['gabarito'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- EXPLICAÇÃO -->
                    <?php if (!empty($resp['explicacao'])): ?>
                        <div class="alert alert-info mb-0">
                            <strong><i class="fas fa-lightbulb"></i> Explicação:</strong><br>
                            <?= nl2br(htmlspecialchars($resp['explicacao'])) ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
        
    <?php else: ?>
        <!-- GABARITO OCULTO -->
        <div class="alert alert-warning shadow">
            <h5><i class="fas fa-eye-slash"></i> Gabarito Oculto</h5>
            <p class="mb-0">O gabarito está oculto para esta avaliação.</p>
        </div>
    <?php endif; ?>

    <!-- RODAPÉ COM AÇÕES -->
    <div class="card shadow">
        <div class="card-body text-center">
            <a href="<?= $_ENV['URL_ADM'] ?>print-evaluation-result/<?= $attempt['id'] ?>" 
               class="btn btn-success" target="_blank">
                <i class="fas fa-print"></i> Imprimir PDF
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>evaluation-history/<?= $attempt['assignment_id'] ?>" 
               class="btn btn-info">
                <i class="fas fa-history"></i> Ver Histórico de Tentativas
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para Minhas Avaliações
            </a>
        </div>
    </div>

</div>

