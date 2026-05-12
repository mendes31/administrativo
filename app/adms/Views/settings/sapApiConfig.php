<?php

use App\adms\Helpers\CSRFHelper;

$config = $this->data['sap_api_config'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_sap_api_config');
?>

<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">
            <i class="fas fa-link me-2"></i>Configuração da API SAP B1
        </h2>
        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center mb-3 mt-3">
            <?php
            $log_resumo = $this->data['log_resumo'] ?? [];
            $log_btn_class = 'btn btn-outline-info btn-sm';
            include __DIR__ . '/../partials/button_log_alteracoes.php';
            ?>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Configuração SAP API</li>
        </ol>
        </div>
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
                                   placeholder="http://192.168.1.223:5000"
                                   value="<?= htmlspecialchars($config['base_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">
                                Informe apenas a URL base do serviço (host + porta), sem <code>/query</code> ou <code>?sql=</code>.
                                Ex: <code>http://192.168.1.223:5000</code>. O sistema irá montar internamente o endpoint de relatórios como
                                <code>base_url + "/query?sql="</code>.
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
                                Se informado, será utilizado no cabeçalho <code>Authorization: Bearer &lt;token&gt;</code> nas chamadas de relatório e de health-check.
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

                        <!-- Campo legacy de health_endpoint mantido como hidden apenas para compatibilidade com o banco -->
                        <input type="hidden"
                               name="health_endpoint"
                               value="<?= htmlspecialchars($config['health_endpoint'] ?? '/health', ENT_QUOTES, 'UTF-8') ?>">

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
                        O teste executa uma consulta simples <code>SELECT 1 FROM DUMMY</code> usando a URL base configurada
                        (chamando internamente <code>base_url + "/query?sql=SELECT 1 FROM DUMMY"</code>) e espera uma resposta HTTP 2xx.
                    </p>
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>test-sap-api-config">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-plug me-2"></i>Testar conexão (SELECT 1 FROM DUMMY)
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
                        <li>A configuração desta tela substitui o uso das variáveis <code>SAP_REPORT_API_URL</code> e <code>SAP_REPORT_API_TIMEOUT</code> do arquivo <code>.env</code>.</li>
                        <li>Em <strong>URL Base</strong>, informe apenas <code>host:porta</code>; o sistema adiciona <code>/query?sql=</code> automaticamente.</li>
                        <li>O token (se usado) deve ser de longa duração ou gerenciado pela própria API; se não houver token, restrinja o acesso por firewall/IP.</li>
                        <li>Mantenha logs e rate limit na API para evitar sobrecarga em consultas muito grandes.</li>
                        <li>Timeout muito baixo pode interromper relatórios extensos; ajuste conforme o volume de dados esperado.</li>
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

