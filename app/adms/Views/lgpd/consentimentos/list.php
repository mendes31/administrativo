<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-handshake text-primary"></i>
            Consentimentos LGPD
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Consentimentos</li>
        </ol>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count($this->data['consentimentos']); ?></h4>
                            <div>Total de Consentimentos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-handshake fa-2x"></i>
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
                                $ativos = array_filter($this->data['consentimentos'], function($c) { 
                                    return $c['status'] === 'Ativo'; 
                                });
                                echo count($ativos);
                                ?>
                            </h4>
                            <div>Ativos</div>
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
                                $revogados = array_filter($this->data['consentimentos'], function($c) { 
                                    return $c['status'] === 'Revogado'; 
                                });
                                echo count($revogados);
                                ?>
                            </h4>
                            <div>Revogados</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                $expirados = array_filter($this->data['consentimentos'], function($c) { 
                                    return $c['status'] === 'Expirado'; 
                                });
                                echo count($expirados);
                                ?>
                            </h4>
                            <div>Expirados</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-1"></i>
                    Lista de Consentimentos
                </div>
                <div>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-email" class="btn btn-success btn-sm me-2">
                        <i class="fas fa-envelope"></i> Enviar por E-mail
                    </a>
                    <?php if (in_array('CreateLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Novo Consentimento
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-hover" id="tabela">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Titular</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Finalidade</th>
                            <th scope="col">Canal</th>
                            <th scope="col">Data Consentimento</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="d-none d-lg-table-cell">Criado em</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['consentimentos'])): ?>
                            <?php foreach ($this->data['consentimentos'] as $consentimento): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $consentimento['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($consentimento['titular_nome']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($consentimento['titular_email']); ?></td>
                                    <td><?php echo htmlspecialchars($consentimento['finalidade']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($consentimento['canal']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $data = new DateTime($consentimento['data_consentimento']);
                                        echo $data->format('d/m/Y');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($consentimento['status']) {
                                            'Ativo' => 'success',
                                            'Revogado' => 'warning',
                                            'Expirado' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo $consentimento['status']; ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php 
                                        $data = new DateTime($consentimento['created_at']);
                                        echo $data->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-view/<?php echo $consentimento['id']; ?>" 
                                               class="btn btn-primary btn-sm me-1 mb-1" title="Visualizar">
                                                <i class="fas fa-eye"></i> Visualizar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-edit/<?php echo $consentimento['id']; ?>" 
                                               class="btn btn-warning btn-sm me-1 mb-1" title="Editar">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($consentimento['status'] === 'Ativo' && in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos/revogar/<?php echo $consentimento['id']; ?>" 
                                               class="btn btn-danger btn-sm me-1 mb-1" title="Revogar"
                                               onclick="return confirm('Tem certeza que deseja revogar este consentimento?')">
                                                <i class="fas fa-ban"></i> Revogar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array('DeleteLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-delete/<?php echo $consentimento['id']; ?>" 
                                               class="btn btn-danger btn-sm me-1 mb-1" title="Excluir"
                                               onclick="return confirm('Tem certeza que deseja excluir este consentimento?')">
                                                <i class="fas fa-trash"></i> Excluir
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Nenhum consentimento encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- CARDS MOBILE -->
            <div class="d-block d-md-none">
                <?php if (!empty($this->data['consentimentos'])): ?>
                    <?php foreach ($this->data['consentimentos'] as $consentimento): ?>
                        <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1"><b><?php echo htmlspecialchars($consentimento['titular_nome']); ?></b></h6>
                                        <div class="mb-1"><b>E-mail:</b> <?php echo htmlspecialchars($consentimento['titular_email']); ?></div>
                                        <div class="mb-1"><b>Finalidade:</b> <?php echo htmlspecialchars($consentimento['finalidade']); ?></div>
                                        <div class="mb-1"><b>Canal:</b> 
                                            <span class="badge bg-info"><?php echo htmlspecialchars($consentimento['canal']); ?></span>
                                        </div>
                                        <div class="mb-1"><b>Status:</b> 
                                            <?php
                                            $statusClass = match($consentimento['status']) {
                                                'Ativo' => 'success',
                                                'Revogado' => 'warning',
                                                'Expirado' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $consentimento['status']; ?></span>
                                        </div>
                                        <div class="mb-1"><b>Data:</b> 
                                            <?php 
                                            $data = new DateTime($consentimento['data_consentimento']);
                                            echo $data->format('d/m/Y');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="btn-group-vertical btn-group-sm">
                                            <?php if (in_array('ViewLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-view/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-primary btn-sm mb-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-edit/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-warning btn-sm mb-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($consentimento['status'] === 'Ativo' && in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos/revogar/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-danger btn-sm mb-1"
                                                   onclick="return confirm('Tem certeza que deseja revogar este consentimento?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('DeleteLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-delete/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-danger btn-sm mb-1"
                                                   onclick="return confirm('Tem certeza que deseja excluir este consentimento?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhum consentimento encontrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabela padrão do sistema - sem DataTables -->
