<?php

$client = $this->data['client'];

?>

<div class="container-fluid px-4">

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($client['razao_social']); ?>
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">SAC</li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-clients">Clientes</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <!-- Header com acoes -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-2">
                        <?php echo htmlspecialchars($client['razao_social']); ?>
                        <?php
                        $statusColor = match($client['status'] ?? '') {
                            'Ativo' => 'success',
                            'Inativo' => 'secondary',
                            'Bloqueado' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($client['status'] ?? '-'); ?></span>
                    </h4>
                    <?php if (!empty($client['nome_fantasia'])): ?>
                        <p class="text-muted mb-1">
                            <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($client['nome_fantasia']); ?>
                        </p>
                    <?php endif; ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-id-card me-1"></i><?php echo $client['type_person'] == 'PJ' ? 'Pessoa Juridica' : 'Pessoa Fisica'; ?>
                        <?php if (!empty($client['document'])): ?>
                            - <?php echo htmlspecialchars($client['document']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (in_array('SacUpdateClient', $this->data['buttonPermission'] ?? [])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>sac-update-client/<?php echo $client['id']; ?>"
                           class="btn btn-warning mb-2 me-1">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                    <?php endif; ?>

                    <?php if (in_array('SacDeleteClient', $this->data['buttonPermission'] ?? [])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>sac-delete-client/<?php echo $client['id']; ?>"
                           class="btn btn-danger mb-2 me-1"
                           onclick="return confirm('Tem certeza que deseja excluir este cliente?')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-clients" class="btn btn-secondary mb-2">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Esquerda -->
        <div class="col-md-8">

            <!-- Card Dados Cadastrais -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Dados Cadastrais</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="35%">Codigo:</th>
                            <td><?php echo htmlspecialchars($client['id']); ?></td>
                        </tr>
                        <tr>
                            <th>Tipo Pessoa:</th>
                            <td><?php echo $client['type_person'] == 'PJ' ? 'Pessoa Juridica' : 'Pessoa Fisica'; ?></td>
                        </tr>
                        <tr>
                            <th>Razao Social:</th>
                            <td><?php echo htmlspecialchars($client['razao_social'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Nome Fantasia:</th>
                            <td><?php echo htmlspecialchars($client['nome_fantasia'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>CPF/CNPJ:</th>
                            <td><?php echo htmlspecialchars($client['document'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Nome do Contato:</th>
                            <td><?php echo htmlspecialchars($client['contact_name'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Cadastrado em:</th>
                            <td><?php echo !empty($client['created_at']) ? date('d/m/Y H:i', strtotime($client['created_at'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Atualizado em:</th>
                            <td><?php echo !empty($client['updated_at']) ? date('d/m/Y H:i', strtotime($client['updated_at'])) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Card Endereco -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Endereco</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="35%">CEP:</th>
                            <td><?php echo htmlspecialchars($client['zip_code'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Endereco:</th>
                            <td>
                                <?php
                                $endereco = htmlspecialchars($client['address'] ?? '');
                                if (!empty($client['number'])) {
                                    $endereco .= ', ' . htmlspecialchars($client['number']);
                                }
                                if (!empty($client['complement'])) {
                                    $endereco .= ' - ' . htmlspecialchars($client['complement']);
                                }
                                echo $endereco ?: '-';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Bairro:</th>
                            <td><?php echo htmlspecialchars($client['neighborhood'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Cidade:</th>
                            <td><?php echo htmlspecialchars($client['city'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td><?php echo htmlspecialchars($client['state'] ?? '-'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Card Chamados Recentes -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Chamados Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['tickets'])): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Nenhum chamado encontrado para este cliente
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-green">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Assunto</th>
                                        <th>Status</th>
                                        <th>Prioridade</th>
                                        <th>Data</th>
                                        <th class="text-center">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['tickets'] as $ticket): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($ticket['id']); ?></code></td>
                                        <td><?php echo htmlspecialchars($ticket['subject'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $ticketStatusColor = match($ticket['status'] ?? '') {
                                                'Aberto' => 'primary',
                                                'Em analise' => 'info',
                                                'Em atendimento' => 'warning',
                                                'Aguardando cliente' => 'secondary',
                                                'Resolvido' => 'success',
                                                'Encerrado' => 'dark',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $ticketStatusColor; ?>"><?php echo htmlspecialchars($ticket['status'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $priorityColor = match($ticket['priority'] ?? '') {
                                                'Baixa' => 'secondary',
                                                'Media' => 'info',
                                                'Alta' => 'warning',
                                                'Urgente' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $priorityColor; ?>"><?php echo htmlspecialchars($ticket['priority'] ?? '-'); ?></span>
                                        </td>
                                        <td><?php echo !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '-'; ?></td>
                                        <td class="text-center">
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>sac-view-ticket/<?php echo $ticket['id']; ?>"
                                               class="btn btn-outline-primary btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Coluna Direita -->
        <div class="col-md-4">

            <!-- Card Contato -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-phone me-2"></i>Contato</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="40%">Telefone:</th>
                            <td><?php echo htmlspecialchars($client['phone'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Celular:</th>
                            <td><?php echo htmlspecialchars($client['mobile'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>E-mail:</th>
                            <td>
                                <?php if (!empty($client['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Card Classificacao -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-tags me-2"></i>Classificacao</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="40%">Segmento:</th>
                            <td>
                                <?php if (!empty($client['segment'])): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($client['segment']); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php
                                $statusColor = match($client['status'] ?? '') {
                                    'Ativo' => 'success',
                                    'Inativo' => 'secondary',
                                    'Bloqueado' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($client['status'] ?? '-'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Card Observacoes -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observacoes</h5></div>
                <div class="card-body">
                    <?php if (!empty($client['notes'])): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($client['notes'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma observacao registrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log de Alteracoes -->
            <?php if (!empty($this->data['log_resumo'])): ?>
                <?php
                $log_resumo = $this->data['log_resumo'];
                $log_btn_class = 'btn btn-outline-info w-100 mb-4';
                include './app/adms/Views/partials/button_log_alteracoes.php';
                ?>
            <?php endif; ?>

        </div>
    </div>
</div>
