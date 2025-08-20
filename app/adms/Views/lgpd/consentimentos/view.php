<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-eye text-info"></i>
            Visualizar Consentimento
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="text-decoration-none">Consentimentos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-handshake"></i>
                    Detalhes do Consentimento #<?php echo $this->data['consentimento']['id']; ?>
                </h5>
                <div>
                    <?php if (in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos/edit/<?php echo $this->data['consentimento']['id']; ?>" 
                           class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($this->data['consentimento']['status'] === 'Ativo' && in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos/revogar/<?php echo $this->data['consentimento']['id']; ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Tem certeza que deseja revogar este consentimento?')">
                            <i class="fas fa-ban"></i> Revogar
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Status do Consentimento -->
            <div class="row mb-4">
                <div class="col-12">
                    <?php
                    $statusClass = '';
                    $statusIcon = '';
                    switch ($this->data['consentimento']['status']) {
                        case 'Ativo':
                            $statusClass = 'bg-success';
                            $statusIcon = 'fa-check-circle';
                            break;
                        case 'Revogado':
                            $statusClass = 'bg-warning';
                            $statusIcon = 'fa-times-circle';
                            break;
                        case 'Expirado':
                            $statusClass = 'bg-danger';
                            $statusIcon = 'fa-clock';
                            break;
                    }
                    ?>
                    <div class="alert <?php echo $statusClass; ?> text-white">
                        <h6 class="mb-0">
                            <i class="fas <?php echo $statusIcon; ?>"></i>
                            Status: <?php echo $this->data['consentimento']['status']; ?>
                        </h6>
                    </div>
                </div>
            </div>

            <!-- Informações do Titular -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user"></i>
                                Informações do Titular
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nome do Titular:</strong><br>
                                    <?php echo htmlspecialchars($this->data['consentimento']['titular_nome']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>E-mail do Titular:</strong><br>
                                    <?php if (!empty($this->data['consentimento']['titular_email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($this->data['consentimento']['titular_email']); ?>">
                                            <?php echo htmlspecialchars($this->data['consentimento']['titular_email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalhes do Consentimento -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-handshake"></i>
                                Detalhes do Consentimento
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <strong>Finalidade:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($this->data['consentimento']['finalidade'])); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Canal de Coleta:</strong><br>
                                    <?php if (!empty($this->data['consentimento']['canal'])): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($this->data['consentimento']['canal']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar"></i>
                                Informações de Data
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Data do Consentimento:</strong><br>
                                    <?php 
                                    $dataConsentimento = new DateTime($this->data['consentimento']['data_consentimento']);
                                    echo $dataConsentimento->format('d/m/Y');
                                    ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Data de Criação:</strong><br>
                                    <?php 
                                    $dataCriacao = new DateTime($this->data['consentimento']['created_at']);
                                    echo $dataCriacao->format('d/m/Y H:i');
                                    ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Última Atualização:</strong><br>
                                    <?php 
                                    if (!empty($this->data['consentimento']['updated_at'])) {
                                        $dataAtualizacao = new DateTime($this->data['consentimento']['updated_at']);
                                        echo $dataAtualizacao->format('d/m/Y H:i');
                                    } else {
                                        echo '<span class="text-muted">Não atualizado</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle"></i>
                                Informações Adicionais
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>ID do Consentimento:</strong><br>
                                    #<?php echo $this->data['consentimento']['id']; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Validade:</strong><br>
                                    <?php 
                                    $dataConsentimento = new DateTime($this->data['consentimento']['data_consentimento']);
                                    $dataExpiracao = clone $dataConsentimento;
                                    $dataExpiracao->add(new DateInterval('P1Y')); // 1 ano
                                    $hoje = new DateTime();
                                    
                                    if ($this->data['consentimento']['status'] === 'Ativo') {
                                        if ($hoje > $dataExpiracao) {
                                            echo '<span class="text-danger">Expirado em ' . $dataExpiracao->format('d/m/Y') . '</span>';
                                        } else {
                                            echo '<span class="text-success">Válido até ' . $dataExpiracao->format('d/m/Y') . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Não aplicável</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
