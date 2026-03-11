<?php

use App\adms\Helpers\CSRFHelper;

$config = $this->data['sap_api_config'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_sap_api_config');
?>

<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-link me-2"></i>Configuração da API SAP B1
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Configuração SAP API</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Parâmetros de Conexão</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-sap-api-config">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">

                        <div class="mb-3">
                            <label class="form-label">URL Base da API *</label>
                            <input type="url"
                                   name="base_url"
                                   class="form-control"
                                   required
                                   placeholder="https://sap-api.seuservidor.com"
                                   value="<?= htmlspecialchars($config['base_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">
                                Informe a URL base sem barra no final. Ex: <code>https://sap-api.seuservidor.com</code>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Token de Autenticação</label>
                            <div class="input-group">
                                <input type="password"
                                       name="api_token"
                                       id="sap_api_token"
                                       class="form-control"
                                       value="<?= htmlspecialchars($config['api_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Se informado, será utilizado no cabeçalho <code>Authorization: Bearer &lt;token&gt;</code>.
                                Caso contrário, nenhuma autenticação por token será enviada e a segurança deve ser garantida pela própria API (IP/Firewall, etc.).
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Timeout (ms)</label>
                                <input type="number"
                                       name="timeout_ms"
                                       class="form-control"
                                       min="1000"
                                       step="100"
                                       value="<?= htmlspecialchars((string)($config['timeout_ms'] ?? 30000), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">
                                    Tempo máximo de espera por resposta (padrão: 30000 ms).
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Page Size</label>
                                <input type="number"
                                       name="page_size"
                                       class="form-control"
                                       min="1"
                                       step="100"
                                       value="<?= htmlspecialchars((string)($config['page_size'] ?? 5000), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">
                                    Quantidade máxima de registros por página (padrão: 5000).
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Endpoint de Health Check</label>
                            <input type="text"
                                   name="health_endpoint"
                                   class="form-control"
                                   placeholder="/health"
                                   value="<?= htmlspecialchars($config['health_endpoint'] ?? '/health', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">
                                Caminho usado no teste de conexão. Deve retornar HTTP 200 (JSON).
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_active"
                                   id="sap_api_is_active"
                                   value="1"
                                   <?= ($config['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sap_api_is_active">
                                Ativar integração com a API SAP
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Salvar Configuração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Testar Conexão</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        O teste envia uma requisição GET para <code>base_url + endpoint</code> e espera uma resposta 2xx.
                    </p>
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>test-sap-api-config">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-plug me-2"></i>Executar Health Check
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Dicas de Configuração</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Garanta que o endpoint esteja publicado com HTTPS e certificado válido.</li>
                        <li>O token deve ser de longa duração ou gerenciado automaticamente.</li>
                        <li>Mantenha a API limitada por firewall e lista de IPs permitidos.</li>
                        <li>No servidor da API, implemente rate limit e logs de auditoria.</li>
                        <li>Timeout baixo pode cortar consultas grandes; ajuste conforme necessidade.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTokenVisibility(button) {
    const input = document.getElementById('sap_api_token');
    const btnIcon = button.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        btnIcon.classList.remove('fa-eye');
        btnIcon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        btnIcon.classList.remove('fa-eye-slash');
        btnIcon.classList.add('fa-eye');
    }
}
</script>

