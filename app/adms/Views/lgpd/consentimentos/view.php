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
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-edit/<?php echo $this->data['consentimento']['id']; ?>" 
                           class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($this->data['consentimento']['status'] === 'Ativo' && in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-revogar/<?php echo $this->data['consentimento']['id']; ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Tem certeza que deseja revogar este consentimento?')">
                            <i class="fas fa-ban"></i> Revogar
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <?php
                    $log_resumo = $this->data['log_resumo'] ?? [];
                    $log_btn_class = 'btn btn-outline-info btn-sm';
                    include __DIR__ . '/../../partials/button_log_alteracoes.php';
                    ?>
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
                            <?php if (!empty($this->data['termo_vinculado'])): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <strong>Termo Vinculado:</strong><br>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($this->data['termo_vinculado']['titulo'] ?? 'N/A'); ?>
                                        (Versão: <?= htmlspecialchars($this->data['termo_vinculado']['versao'] ?? 'N/A'); ?> - 
                                        Tipo: <?= htmlspecialchars($this->data['termo_vinculado']['tipo'] ?? 'N/A'); ?>)
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($this->data['consentimento']['versao_termo'])): ?>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <strong>Versão do Termo no Consentimento:</strong><br>
                                    <?= htmlspecialchars($this->data['consentimento']['versao_termo']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuário Vinculado (se houver) -->
            <?php if (!empty($this->data['usuario_vinculado'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user-check"></i>
                                Usuário do Sistema Vinculado
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nome:</strong><br>
                                    <?= htmlspecialchars($this->data['usuario_vinculado']['name'] ?? 'N/A'); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>E-mail:</strong><br>
                                    <a href="mailto:<?= htmlspecialchars($this->data['usuario_vinculado']['email'] ?? ''); ?>">
                                        <?= htmlspecialchars($this->data['usuario_vinculado']['email'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($this->data['usuario_vinculado']['username'])): ?>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <strong>Username:</strong><br>
                                    <?= htmlspecialchars($this->data['usuario_vinculado']['username']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

            <!-- Documentos Anexos -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-dark">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-paperclip"></i>
                                Documentos Anexos
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Verificar se anexos estão sendo carregados
                            $anexosExistentes = $this->data['anexos'] ?? [];
                            if (!empty($anexosExistentes) && is_array($anexosExistentes) && count($anexosExistentes) > 0): 
                            ?>
                                <div class="list-group">
                                    <?php foreach ($anexosExistentes as $anexo): ?>
                                        <?php
                                        // Determinar o ícone baseado na extensão do arquivo ou mime_type
                                        $nomeArquivo = strtolower($anexo['nome_original'] ?? '');
                                        $mimeType = strtolower($anexo['mime_type'] ?? '');
                                        $extensao = pathinfo($nomeArquivo, PATHINFO_EXTENSION);
                                        
                                        $iconeClasse = 'fa-file';
                                        $iconeCor = 'text-secondary';
                                        
                                        // Verificar por extensão primeiro
                                        if (in_array($extensao, ['pdf'])) {
                                            $iconeClasse = 'fa-file-pdf';
                                            $iconeCor = 'text-danger';
                                        } elseif (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                                            $iconeClasse = 'fa-file-image';
                                            $iconeCor = 'text-info';
                                        } elseif (in_array($extensao, ['doc', 'docx'])) {
                                            $iconeClasse = 'fa-file-word';
                                            $iconeCor = 'text-primary';
                                        } elseif (in_array($extensao, ['xls', 'xlsx'])) {
                                            $iconeClasse = 'fa-file-excel';
                                            $iconeCor = 'text-success';
                                        } elseif (in_array($extensao, ['zip', 'rar', '7z'])) {
                                            $iconeClasse = 'fa-file-archive';
                                            $iconeCor = 'text-warning';
                                        } elseif (strpos($mimeType, 'pdf') !== false) {
                                            $iconeClasse = 'fa-file-pdf';
                                            $iconeCor = 'text-danger';
                                        } elseif (strpos($mimeType, 'image') !== false) {
                                            $iconeClasse = 'fa-file-image';
                                            $iconeCor = 'text-info';
                                        }
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas <?= $iconeClasse ?> <?= $iconeCor ?> me-2"></i>
                                                <strong><?= htmlspecialchars($anexo['nome_original']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php
                                                    $tamanhoKB = round(($anexo['tamanho_bytes'] ?? 0) / 1024, 2);
                                                    echo $tamanhoKB . ' KB';
                                                    ?>
                                                    <?php if (!empty($anexo['created_at'])): ?>
                                                        - Enviado em <?= date('d/m/Y H:i', strtotime($anexo['created_at'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <a href="<?= $_ENV['URL_ADM'] . $anexo['arquivo_path']; ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary"
                                               download="<?= htmlspecialchars($anexo['nome_original']); ?>">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    Nenhum documento anexado.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações de Auditoria -->
            <?php if (!empty($this->data['consentimento']['ip_address']) || 
                       !empty($this->data['consentimento']['user_agent']) || 
                       !empty($this->data['consentimento']['revoked_at'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-shield-alt"></i>
                                Informações de Auditoria
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if (!empty($this->data['consentimento']['ip_address'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>IP de Origem:</strong><br>
                                    <code><?= htmlspecialchars($this->data['consentimento']['ip_address']); ?></code>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($this->data['consentimento']['user_agent'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>User Agent:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($this->data['consentimento']['user_agent']); ?></small>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($this->data['consentimento']['revoked_at'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Data de Revogação:</strong><br>
                                    <?php 
                                    $dataRevogacao = new DateTime($this->data['consentimento']['revoked_at']);
                                    echo $dataRevogacao->format('d/m/Y H:i');
                                    ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($this->data['consentimento']['revocation_reason'])): ?>
                                <div class="col-md-12 mb-2">
                                    <strong>Motivo da Revogação:</strong><br>
                                    <?= nl2br(htmlspecialchars($this->data['consentimento']['revocation_reason'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
