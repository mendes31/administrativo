<?php

use App\adms\Helpers\CSRFHelper;

$config = $this->data['mcp_api_config'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_mcp_api_config');
$activeTab = (string) ($this->data['active_tab'] ?? 'conexao');
if (!in_array($activeTab, ['conexao', 'tools'], true)) {
    $activeTab = 'conexao';
}
$canTools = in_array('ListMcpChatTools', $this->data['buttonPermission'] ?? [], true);
if ($activeTab === 'tools' && !$canTools) {
    $activeTab = 'conexao';
}
?>

<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">
            <i class="fas fa-robot me-2"></i>Assistente MCP
        </h2>
        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center mb-3 mt-3">
            <?php
            $log_resumo = $this->data['log_resumo'] ?? [];
            $log_btn_class = 'btn btn-outline-info btn-sm';
            include __DIR__ . '/../partials/button_log_alteracoes.php';
            ?>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Administração</li>
            <li class="breadcrumb-item">Configurações</li>
            <li class="breadcrumb-item active">Assistente MCP</li>
        </ol>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="mcpAssistenteTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'conexao' ? 'active' : '' ?>"
                    id="tab-conexao"
                    data-bs-toggle="tab"
                    data-bs-target="#pane-conexao"
                    type="button"
                    role="tab"
                    aria-controls="pane-conexao"
                    aria-selected="<?= $activeTab === 'conexao' ? 'true' : 'false' ?>">
                <i class="fas fa-plug me-1"></i>Conexão
            </button>
        </li>
        <?php if ($canTools): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'tools' ? 'active' : '' ?>"
                        id="tab-tools"
                        data-bs-toggle="tab"
                        data-bs-target="#pane-tools"
                        type="button"
                        role="tab"
                        aria-controls="pane-tools"
                        aria-selected="<?= $activeTab === 'tools' ? 'true' : 'false' ?>">
                    <i class="fas fa-toolbox me-1"></i>Tools
                </button>
            </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content" id="mcpAssistenteTabContent">
        <div class="tab-pane fade <?= $activeTab === 'conexao' ? 'show active' : '' ?>"
             id="pane-conexao"
             role="tabpanel"
             aria-labelledby="tab-conexao"
             tabindex="0">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Parâmetros de Conexão</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-mcp-api-config">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">

                                <div class="mb-3">
                                    <label class="form-label">URL da API MCP *</label>
                                    <input type="text"
                                           name="base_url"
                                           class="form-control"
                                           required
                                           placeholder="local:internal  ou  https://seu-servidor-mcp.exemplo.com"
                                           value="<?= htmlspecialchars($config['base_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="form-text">
                                        Use <code>local:internal</code> para testar na homologação local com indicadores de RH do Portal (sem SAP e sem API paga).
                                        Ou informe a URL HTTPS do servidor MCP externo.
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_active"
                                           id="mcp_api_is_active"
                                           value="1"
                                           <?= ($config['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mcp_api_is_active">
                                        Ativar integração com a API MCP
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
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Como será usado</h6>
                        </div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li>Esta URL será utilizada pelos endpoints internos do sistema para conversar com o servidor MCP.</li>
                                <li>O <strong>Tiarajuzinho</strong> (ícone do robô) só aparece para usuários com permissão <em>McpChat</em> e quando a integração estiver ativa.</li>
                                <li>Chaves de IA (OpenAI/Claude) ficam no <code>.env</code>; nesta tela só a URL e o ativar/desativar.</li>
                                <li>O chat é transversal (vários departamentos); a permissão <em>McpChat</em> fica no grupo ACL <strong>Configurações</strong> (não no CRM).</li>
                                <?php if ($canTools): ?>
                                    <li>Para liberar relatórios no chat, use a aba
                                        <a href="<?= $_ENV['URL_ADM'] ?>mcp-api-config?tab=tools">Tools</a>.
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canTools): ?>
            <div class="tab-pane fade <?= $activeTab === 'tools' ? 'show active' : '' ?>"
                 id="pane-tools"
                 role="tabpanel"
                 aria-labelledby="tab-tools"
                 tabindex="0">
                <?php include __DIR__ . '/partials/mcpChatToolsPanels.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var tabs = document.getElementById('mcpAssistenteTabs');
    if (!tabs || !window.history || !window.history.replaceState) {
        return;
    }
    tabs.addEventListener('shown.bs.tab', function (ev) {
        var target = ev.target && ev.target.getAttribute('data-bs-target');
        var tab = target === '#pane-tools' ? 'tools' : 'conexao';
        var url = new URL(window.location.href);
        if (tab === 'conexao') {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', tab);
        }
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
    });
})();
</script>
