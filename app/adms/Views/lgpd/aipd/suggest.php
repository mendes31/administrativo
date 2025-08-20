<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Sugestões de AIPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Sugestões</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-lightbulb text-warning"></i>
                Análise Automática de ROPAs para AIPD
            </h5>
            <small class="text-muted">
                O sistema analisou automaticamente os ROPAs e identificou operações que podem necessitar de AIPD conforme a LGPD.
            </small>
        </div>

        <div class="card-body">
            <?php if (empty($this->data['sugestoes'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nenhuma sugestão encontrada!</strong><br>
                    Todos os ROPAs analisados estão em conformidade ou não apresentam riscos que necessitem de AIPD.
                </div>
            <?php else: ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h4 class="text-warning"><?php echo count($this->data['sugestoes']); ?></h4>
                                <small>Sugestões de AIPD</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h4 class="text-danger">
                                    <?php 
                                    $criticos = array_filter($this->data['sugestoes'], function($s) {
                                        return $s['nivel_risco'] === 'Crítico';
                                    });
                                    echo count($criticos);
                                    ?>
                                </h4>
                                <small>Riscos Críticos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ROPA</th>
                                <th>Pontuação</th>
                                <th>Nível de Risco</th>
                                <th>Prioridade</th>
                                <th>Motivos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['sugestoes'] as $sugestao): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sugestao['ropa']['atividade'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($sugestao['ropa']['departamento'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                <div class="progress-bar 
                                                    <?php 
                                                    if ($sugestao['pontuacao'] >= 80) echo 'bg-danger';
                                                    elseif ($sugestao['pontuacao'] >= 60) echo 'bg-warning';
                                                    elseif ($sugestao['pontuacao'] >= 40) echo 'bg-info';
                                                    else echo 'bg-success';
                                                    ?>" 
                                                    style="width: <?php echo min(100, $sugestao['pontuacao']); ?>%">
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?php echo $sugestao['pontuacao']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            switch($sugestao['nivel_risco']) {
                                                case 'Crítico': echo 'bg-danger'; break;
                                                case 'Alto': echo 'bg-warning'; break;
                                                case 'Médio': echo 'bg-info'; break;
                                                default: echo 'bg-success';
                                            }
                                            ?>">
                                            <?php echo $sugestao['nivel_risco']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            switch($sugestao['prioridade']) {
                                                case 'Alta': echo 'bg-danger'; break;
                                                case 'Média': echo 'bg-warning'; break;
                                                default: echo 'bg-success';
                                            }
                                            ?>">
                                            <?php echo $sugestao['prioridade']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($sugestao['motivos'] as $motivo): ?>
                                                <li><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo htmlspecialchars($motivo); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd-create?ropa_id=<?php echo $sugestao['ropa']['id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Criar AIPD">
                                                <i class="fas fa-plus"></i> Criar AIPD
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-view/<?php echo $sugestao['ropa']['id']; ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Ver ROPA">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Importante:</h6>
                    <ul class="mb-0">
                        <li>Esta análise é baseada em critérios automáticos e deve ser validada por um profissional</li>
                        <li>Riscos "Críticos" e "Altos" devem ter prioridade máxima</li>
                        <li>Consulte a legislação e orientações da ANPD para confirmação</li>
                        <li>O sistema analisa apenas os ROPAs cadastrados</li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>
