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
                <table class="table table-striped table-hover" id="tabela" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 12%;">Titular</th>
                            <th scope="col" style="width: 12%;">E-mail</th>
                            <th scope="col" style="width: 20%;">Finalidade</th>
                            <th scope="col" style="width: 8%;">Canal</th>
                            <th scope="col" style="width: 8%;">Data</th>
                            <th scope="col" style="width: 8%;">Status</th>
                            <th scope="col" class="d-none d-lg-table-cell" style="width: 10%;">Criado em</th>
                            <th scope="col" class="text-center" style="width: 17%;">Ações</th>
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
                                        <div class="fw-bold text-break" style="word-wrap: break-word; overflow-wrap: break-word;">
                                            <?php echo htmlspecialchars($consentimento['titular_nome']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-break" style="word-wrap: break-word; overflow-wrap: break-word; font-size: 0.9em;">
                                            <?php echo htmlspecialchars($consentimento['titular_email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-break" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.4; font-size: 0.85em;">
                                            <?php echo htmlspecialchars($consentimento['finalidade']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info" style="white-space: nowrap;">
                                            <?php echo htmlspecialchars($consentimento['canal']); ?>
                                        </span>
                                    </td>
                                    <td style="white-space: nowrap; font-size: 0.9em;">
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
                                    <td class="d-none d-lg-table-cell" style="white-space: nowrap; font-size: 0.85em;">
                                        <?php 
                                        $data = new DateTime($consentimento['created_at']);
                                        echo $data->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('ViewLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-view/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-primary" title="Visualizar" style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos-edit/<?php echo $consentimento['id']; ?>" 
                                                   class="btn btn-warning" title="Editar" style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($consentimento['status'] === 'Ativo' && in_array('EditLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <button type="button"
                                                        class="btn btn-danger"
                                                        title="Revogar"
                                                        onclick="openRevokeConsentModal(<?php echo (int)$consentimento['id']; ?>)"
                                                        style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('DeleteLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <button type="button"
                                                        class="btn btn-danger"
                                                        title="Excluir"
                                                        onclick="openDeleteConsentModal(<?php echo (int)$consentimento['id']; ?>)"
                                                        style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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
                                                <button type="button"
                                                        class="btn btn-danger btn-sm mb-1"
                                                        onclick="openRevokeConsentModal(<?php echo (int)$consentimento['id']; ?>)">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array('DeleteLgpdConsentimentos', $this->data['buttonPermission'])): ?>
                                                <button type="button"
                                                        class="btn btn-danger btn-sm mb-1"
                                                        onclick="openDeleteConsentModal(<?php echo (int)$consentimento['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Modal de Revogação de Consentimento -->
<div class="modal fade" id="revokeConsentModal" tabindex="-1" aria-labelledby="revokeConsentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="revokeConsentForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="revokeConsentModalLabel">
                        <i class="fas fa-ban text-danger"></i> Revogar Consentimento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="consent_id" id="revoke_consent_id">

                    <div class="alert alert-warning">
                        <strong>Atenção:</strong> Para revogar o consentimento é necessário informar uma justificativa
                        e confirmar com sua senha. Esta ação será registrada para fins de auditoria LGPD.
                    </div>

                    <div class="mb-3">
                        <label for="revoke_motivo" class="form-label">Justificativa / Motivo da Revogação *</label>
                        <textarea class="form-control" id="revoke_motivo" name="motivo" rows="3" required
                                  placeholder="Descreva o motivo da revogação do consentimento"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="revoke_password" class="form-label">Senha de Confirmação *</label>
                        <input type="password" class="form-control" id="revoke_password" name="password" required
                               placeholder="Digite sua senha para confirmar a revogação">
                    </div>

                    <div id="revoke_error_message" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Revogação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRevokeConsentModal(consentId) {
    const form = document.getElementById('revokeConsentForm');
    form.reset();
    document.getElementById('revoke_error_message').classList.add('d-none');
    document.getElementById('revoke_consent_id').value = consentId;

    const modal = new bootstrap.Modal(document.getElementById('revokeConsentModal'));
    modal.show();
}

document.getElementById('revokeConsentForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const consentId = document.getElementById('revoke_consent_id').value;
    const formData = new FormData(this);
    const errorDiv = document.getElementById('revoke_error_message');
    errorDiv.classList.add('d-none');

    fetch('<?= $_ENV['URL_ADM'] ?>lgpd-consentimentos-revogar/' + consentId, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('revokeConsentModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                window.location.reload();
            } else {
                errorDiv.textContent = data.message || 'Erro ao revogar consentimento.';
                errorDiv.classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Erro na revogação:', error);
            errorDiv.textContent = 'Erro ao processar a requisição.';
            errorDiv.classList.remove('d-none');
        });
});

// Modal de Exclusão de Consentimento
function openDeleteConsentModal(consentId) {
    const form = document.getElementById('deleteConsentForm');
    form.reset();
    document.getElementById('delete_error_message').classList.add('d-none');
    document.getElementById('delete_consent_id').value = consentId;

    const modal = new bootstrap.Modal(document.getElementById('deleteConsentModal'));
    modal.show();
}

if (!document.getElementById('deleteConsentModal')) {
    // Cria o modal de exclusão dinamicamente apenas uma vez
    const modalHtml = `
<div class="modal fade" id="deleteConsentModal" tabindex="-1" aria-labelledby="deleteConsentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="deleteConsentForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConsentModalLabel">
                        <i class="fas fa-trash-alt text-danger"></i> Excluir Consentimento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="consent_id" id="delete_consent_id">

                    <div class="alert alert-warning">
                        <strong>Atenção:</strong> Esta ação irá remover definitivamente o registro de consentimento.
                        Informe um motivo e confirme com sua senha. A exclusão será registrada para fins de auditoria.
                    </div>

                    <div class="mb-3">
                        <label for="delete_motivo" class="form-label">Justificativa / Motivo da Exclusão *</label>
                        <textarea class="form-control" id="delete_motivo" name="motivo" rows="3" required
                                  placeholder="Descreva o motivo da exclusão do consentimento"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Senha de Confirmação *</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required
                               placeholder="Digite sua senha para confirmar a exclusão">
                    </div>

                    <div id="delete_error_message" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>
</div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    document.getElementById('deleteConsentForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const consentId = document.getElementById('delete_consent_id').value;
        const formData = new FormData(this);
        const errorDiv = document.getElementById('delete_error_message');
        errorDiv.classList.add('d-none');

        fetch('<?= $_ENV['URL_ADM'] ?>lgpd-consentimentos-delete/' + consentId, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById('deleteConsentModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.message || 'Erro ao excluir consentimento.';
                    errorDiv.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Erro na exclusão:', error);
                errorDiv.textContent = 'Erro ao processar a requisição.';
                errorDiv.classList.remove('d-none');
            });
    });
}
</script>

<style>
/* Estilos para evitar rolagem horizontal na tabela */
#tabela {
    table-layout: fixed;
    width: 100%;
    word-wrap: break-word;
}

#tabela td, #tabela th {
    overflow: hidden;
    text-overflow: ellipsis;
}

#tabela .text-break {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Garantir que a tabela não ultrapasse o container */
.table-responsive {
    overflow-x: auto;
    max-width: 100%;
}

/* Ajustar botões de ação para ficarem horizontais e menores */
#tabela .btn-group {
    display: flex;
    flex-direction: row;
    gap: 2px;
    justify-content: center;
}

#tabela .btn-group .btn {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    min-width: 32px;
}
</style>

<!-- Tabela padrão do sistema - sem DataTables -->
