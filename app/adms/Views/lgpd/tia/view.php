<?php
use App\adms\Helpers\CSRFHelper;

$tia = $this->data['tia'];
$data_groups = $this->data['data_groups'];
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-eye text-primary"></i>
            Visualizar Teste TIA: <?php echo htmlspecialchars($tia['codigo']); ?>
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="text-decoration-none">TIA</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <!-- Botões de Ação -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Detalhes do Teste TIA</h5>
                    <small class="text-muted">Informações completas do teste de impacto às atividades</small>
                </div>
                <div>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-export-pdf-view/<?php echo $tia['id']; ?>" class="btn btn-danger me-2">
                        <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                    </a>
                    <?php if (in_array('LgpdTiaEdit', $this->data['menuPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-edit/<?php echo $tia['id']; ?>" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('LgpdTiaDelete', $this->data['menuPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-delete/<?php echo $tia['id']; ?>" 
                           class="btn btn-danger me-2"
                           onclick="return confirm('Tem certeza que deseja excluir este teste TIA?')">
                            <i class="fas fa-trash me-2"></i>Excluir
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações Básicas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informações Básicas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Código:</label>
                            <div class="mb-3">
                                <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($tia['codigo']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status:</label>
                            <div class="mb-3">
                                <?php
                                $statusClass = match($tia['status']) {
                                    'Em Andamento' => 'warning',
                                    'Concluído' => 'success',
                                    'Aprovado' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?> fs-6">
                                    <?php echo htmlspecialchars($tia['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label fw-bold">Título:</label>
                            <div class="mb-3">
                                <h5><?php echo htmlspecialchars($tia['titulo']); ?></h5>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($tia['descricao'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrição:</label>
                            <div class="mb-3">
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($tia['descricao'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalhes do Teste -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Detalhes do Teste
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Data do Teste:</label>
                        <div>
                            <i class="fas fa-calendar me-2 text-muted"></i>
                            <?php echo date('d/m/Y', strtotime($tia['data_teste'])); ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resultado:</label>
                        <div>
                            <?php
                            $resultadoClass = match($tia['resultado']) {
                                'Baixo Risco' => 'success',
                                'Médio Risco' => 'warning',
                                'Alto Risco' => 'danger',
                                'Necessita AIPD' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?php echo $resultadoClass; ?> fs-6">
                                <?php echo htmlspecialchars($tia['resultado']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($tia['justificativa'])): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Justificativa:</label>
                        <div>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($tia['justificativa'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tia['recomendacoes'])): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Recomendações:</label>
                        <div>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($tia['recomendacoes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Responsabilidades
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Departamento:</label>
                        <div>
                            <i class="fas fa-building me-2 text-muted"></i>
                            <?php echo htmlspecialchars($tia['departamento_nome'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    
                    <?php if ($tia['responsavel_nome']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Responsável:</label>
                        <div>
                            <i class="fas fa-user me-2 text-muted"></i>
                            <?php echo htmlspecialchars($tia['responsavel_nome']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tia['ropa_atividade']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ROPA Relacionada:</label>
                        <div>
                            <i class="fas fa-link me-2 text-muted"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($tia['ropa_atividade']); ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Criado em:</label>
                        <div>
                            <i class="fas fa-clock me-2 text-muted"></i>
                            <?php echo date('d/m/Y H:i', strtotime($tia['created_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($tia['updated_at']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Última atualização:</label>
                        <div>
                            <i class="fas fa-edit me-2 text-muted"></i>
                            <?php echo date('d/m/Y H:i', strtotime($tia['updated_at'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Grupos de Dados -->
    <?php if (!empty($data_groups)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Grupos de Dados Processados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Grupo de Dados</th>
                                    <th>Categoria</th>
                                    <th>Volume</th>
                                    <th>Sensibilidade</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_groups as $group): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($group['data_group_name']); ?></strong>
                                        <?php if ($group['data_group_sensitive']): ?>
                                            <span class="badge bg-danger ms-2">Sensível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($group['data_group_category']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $volumeClass = match($group['volume_dados']) {
                                            'Baixo' => 'success',
                                            'Médio' => 'warning',
                                            'Alto' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $volumeClass; ?>">
                                            <?php echo htmlspecialchars($group['volume_dados']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $sensibilidadeClass = match($group['sensibilidade']) {
                                            'Baixa' => 'success',
                                            'Média' => 'warning',
                                            'Alta' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $sensibilidadeClass; ?>">
                                            <?php echo htmlspecialchars($group['sensibilidade']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($group['observacoes'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($group['observacoes']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Informações Adicionais -->
    <div class="row">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Sobre os Testes TIA
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>O que é o TIA?</h6>
                            <p class="text-muted">
                                O Teste de Impacto às Atividades (TIA) é uma ferramenta para avaliar rapidamente 
                                se uma atividade de tratamento de dados pessoais necessita de uma AIPD completa.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Resultados Possíveis:</h6>
                            <ul class="text-muted">
                                <li><strong>Baixo Risco:</strong> Atividade pode prosseguir normalmente</li>
                                <li><strong>Médio Risco:</strong> Implementar medidas de mitigação</li>
                                <li><strong>Alto Risco:</strong> Necessita de controles rigorosos</li>
                                <li><strong>Necessita AIPD:</strong> Requer avaliação completa de impacto</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
