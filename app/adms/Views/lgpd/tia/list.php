<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-clipboard-check text-primary"></i>
            Testes de Impacto às Atividades (TIA)
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">TIA</li>
        </ol>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo $this->data['total_tias']; ?></h4>
                            <div>Total de Testes TIA</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                $concluidos = array_filter($this->data['tias'], function($t) { 
                                    return $t['status'] === 'Concluído'; 
                                });
                                echo count($concluidos);
                                ?>
                            </h4>
                            <div>Concluídos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                $em_andamento = array_filter($this->data['tias'], function($t) { 
                                    return $t['status'] === 'Em Andamento'; 
                                });
                                echo count($em_andamento);
                                ?>
                            </h4>
                            <div>Em Andamento</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                $aprovados = array_filter($this->data['tias'], function($t) { 
                                    return $t['status'] === 'Aprovado'; 
                                });
                                echo count($aprovados);
                                ?>
                            </h4>
                            <div>Aprovados</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-thumbs-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Lista de Testes TIA</h5>
                    <small class="text-muted">Gerencie os testes de impacto às atividades</small>
                </div>
                <div class="btn-group" role="group">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-dashboard" class="btn btn-info me-2">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-templates" class="btn btn-warning me-2">
                        <i class="fas fa-copy me-2"></i>Templates
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-export-pdf-list" class="btn btn-danger me-2">
                        <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                    </a>
                    <?php if (in_array('LgpdTiaCreate', $this->data['menuPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Novo Teste TIA
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Testes TIA -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Testes TIA Cadastrados
            </h6>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (empty($this->data['tias'])): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum teste TIA encontrado</h5>
                    <p class="text-muted">Comece criando o primeiro teste de impacto às atividades.</p>
                    <?php if (in_array('LgpdTiaCreate', $this->data['menuPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Criar Primeiro Teste TIA
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Departamento</th>
                                <th>ROPA</th>
                                <th>Data do Teste</th>
                                <th>Resultado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['tias'] as $tia): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($tia['codigo']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tia['titulo']); ?></strong>
                                        <?php if (!empty($tia['descricao'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($tia['descricao'], 0, 100)) . (strlen($tia['descricao']) > 100 ? '...' : ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($tia['departamento_nome'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($tia['ropa_atividade']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($tia['ropa_atividade'], 0, 50)) . (strlen($tia['ropa_atividade']) > 50 ? '...' : ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Não vinculado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($tia['data_teste'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $resultadoClass = match($tia['resultado']) {
                                            'Baixo Risco' => 'success',
                                            'Médio Risco' => 'warning',
                                            'Alto Risco' => 'danger',
                                            'Necessita AIPD' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $resultadoClass; ?>">
                                            <?php echo htmlspecialchars($tia['resultado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($tia['status']) {
                                            'Em Andamento' => 'warning',
                                            'Concluído' => 'success',
                                            'Aprovado' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($tia['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (in_array('LgpdTiaView', $this->data['menuPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-view/<?php echo $tia['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('LgpdTiaEdit', $this->data['menuPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-edit/<?php echo $tia['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('LgpdTiaDelete', $this->data['menuPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-delete/<?php echo $tia['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   title="Excluir"
                                                   onclick="return confirm('Tem certeza que deseja excluir este teste TIA?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
