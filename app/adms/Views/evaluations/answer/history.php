<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
$assignment = $this->data['assignment'];
$tentativas = $this->data['tentativas'] ?? [];
$melhorTentativa = $this->data['melhor_tentativa'] ?? null;
$estatisticas = $this->data['estatisticas'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-history"></i> Histórico de Tentativas
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="text-decoration-none">Minhas Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Histórico</li>
        </ol>
    </div>

    <!-- INFORMAÇÕES DA AVALIAÇÃO -->
    <div class="card mb-4 border-primary shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= htmlspecialchars($assignment['model_titulo']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Treinamento:</strong> <?= htmlspecialchars($assignment['training_name'] ?? 'N/A') ?></p>
                    <p class="mb-1"><strong>Nota Mínima:</strong> <?= $assignment['nota_minima_aprovacao'] ?></p>
                    <p class="mb-1"><strong>Status Atual:</strong> 
                        <?php
                        $statusColors = [
                            'pendente' => 'warning', 'em_andamento' => 'info',
                            'aprovado' => 'success', 'reprovado' => 'danger',
                            'concluido' => 'secondary', 'cancelado' => 'dark'
                        ];
                        $color = $statusColors[$assignment['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= ucfirst($assignment['status']) ?></span>
                    </p>
                </div>
                <div class="col-md-6">
                    <?php if (!empty($estatisticas)): ?>
                        <div class="bg-light p-3 rounded">
                            <strong>Estatísticas Gerais:</strong>
                            <ul class="list-unstyled mb-0 mt-2">
                                <li><i class="fas fa-redo text-info"></i> Total de Tentativas: <?= $estatisticas['total_tentativas'] ?></li>
                                <li><i class="fas fa-arrow-up text-success"></i> Melhor Nota: <?= number_format($estatisticas['melhor_nota'], 2) ?></li>
                                <li><i class="fas fa-arrow-down text-danger"></i> Pior Nota: <?= number_format($estatisticas['pior_nota'], 2) ?></li>
                                <li><i class="fas fa-chart-line text-warning"></i> Média: <?= number_format($estatisticas['media_notas'], 2) ?></li>
                                <li><i class="fas fa-check text-success"></i> Aprovações: <?= $estatisticas['aprovacoes'] ?></li>
                                <li><i class="fas fa-times text-danger"></i> Reprovações: <?= $estatisticas['reprovacoes'] ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($tentativas)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Você ainda não realizou nenhuma tentativa desta avaliação.
        </div>
    <?php else: ?>
        <!-- LISTA DE TENTATIVAS -->
        <h4 class="mb-3">Histórico de Tentativas</h4>

        <?php foreach ($tentativas as $tentativa): ?>
            <?php 
            $resultado = $tentativa['resultado'];
            $ehMelhor = $melhorTentativa && $tentativa['id'] == $melhorTentativa['id'];
            ?>
            
            <div class="card mb-3 border-start border-<?= $resultado === 'aprovado' ? 'success' : 'danger' ?> border-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Tentativa #<?= $tentativa['tentativa_numero'] ?></strong>
                        <?php if ($ehMelhor): ?>
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="fas fa-crown"></i> Melhor Nota
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="badge bg-<?= $resultado === 'aprovado' ? 'success' : 'danger' ?>">
                            <?= $resultado === 'aprovado' ? 'APROVADO' : 'REPROVADO' ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4 class="text-<?= $resultado === 'aprovado' ? 'success' : 'danger' ?> mb-0">
                                        <?= number_format($tentativa['nota_obtida'], 2) ?>
                                    </h4>
                                    <small class="text-muted">Nota</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-info mb-0">
                                        <?= $tentativa['questoes_corretas'] ?>/<?= $tentativa['total_questoes'] ?>
                                    </h4>
                                    <small class="text-muted">Acertos</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-warning mb-0">
                                        <?= number_format($tentativa['percentual'], 1) ?>%
                                    </h4>
                                    <small class="text-muted">Percentual</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1">
                                <small class="text-muted">Data:</small><br>
                                <strong><?= date('d/m/Y H:i', strtotime($tentativa['data_finalizacao'])) ?></strong>
                            </p>
                            <a href="<?= $_ENV['URL_ADM'] ?>resultado-avaliacao/<?= $tentativa['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver Detalhes
                            </a>
                            <a href="<?= $_ENV['URL_ADM'] ?>print-evaluation-result/<?= $tentativa['id'] ?>" 
                               class="btn btn-sm btn-success mt-1" target="_blank">
                                <i class="fas fa-print"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- BOTÃO VOLTAR -->
    <div class="text-center mb-4">
        <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar para Minhas Avaliações
        </a>
    </div>
</div>

