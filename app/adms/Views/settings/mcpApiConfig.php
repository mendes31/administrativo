<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\InternalChat\InternalChatLlmSettings;

$config = $this->data['mcp_api_config'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_mcp_api_config');
$ollamaModels = $this->data['ollama_models'] ?? [];
$ollamaUrl = (string) ($this->data['ollama_url'] ?? '');
$ollamaEnvModel = (string) ($this->data['ollama_env_model'] ?? 'llama3.2');
$selectedModel = trim((string) ($config['ollama_model'] ?? ''));
$fallbackModels = trim((string) ($config['ollama_models_fallback'] ?? ''));
$customSelected = $selectedModel !== '' && $ollamaModels !== [] && !in_array($selectedModel, $ollamaModels, true);
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
                            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-mcp-api-config" id="form-mcp-api-config">
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

                                <?php include __DIR__ . '/partials/mcpLlmProviders.php'; ?>

                                <h6 class="mb-3"><i class="fas fa-server me-1"></i> Modelo Ollama (quando o motor local estiver ativo)</h6>
                                <?php if ($ollamaUrl === ''): ?>
                                    <div class="alert alert-warning small">
                                        Informe a URL do Ollama no bloco acima (ou <code>OLLAMA_URL</code> no <code>.env</code>),
                                        por exemplo <code>http://127.0.0.1:11434</code>, para listar modelos locais.
                                    </div>
                                <?php else: ?>
                                    <p class="small text-muted mb-2">
                                        Ollama em <code><?= htmlspecialchars($ollamaUrl, ENT_QUOTES, 'UTF-8') ?></code>.
                                        Cada requisição usa <strong>um</strong> modelo; se falhar, tenta os fallbacks na ordem.
                                    </p>
                                    <?php if (!InternalChatLlmSettings::isPlausibleOllamaUrl($ollamaUrl)): ?>
                                        <div class="alert alert-warning small">
                                            Essa URL não é o Ollama local (<code>http://127.0.0.1:11434</code>) —
                                            parece a API MCP/ERP antiga. Em <strong>Automático</strong> ela é ignorada.
                                            Para testar Groq, deixe <em>Usar agora</em> em Automático ou Groq e clique em
                                            <strong>Testar IA</strong> de novo (a faixa de resultado fica acima dos botões).
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label" for="ollama_model">Modelo principal</label>
                                    <?php if ($ollamaModels !== []): ?>
                                        <select name="ollama_model" id="ollama_model" class="form-select">
                                            <option value="">Usar padrão do .env (<?= htmlspecialchars($ollamaEnvModel, ENT_QUOTES, 'UTF-8') ?>)</option>
                                            <?php foreach ($ollamaModels as $modelName): ?>
                                                <option value="<?= htmlspecialchars((string) $modelName, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= $selectedModel === $modelName ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $modelName, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="__custom__" <?= $customSelected ? 'selected' : '' ?>>Outro (digitar)…</option>
                                        </select>
                                        <input type="text"
                                               name="ollama_model_custom"
                                               id="ollama_model_custom"
                                               class="form-control mt-2 <?= $customSelected ? '' : 'd-none' ?>"
                                               placeholder="ex.: qwen:4b"
                                               value="<?= $customSelected ? htmlspecialchars($selectedModel, ENT_QUOTES, 'UTF-8') : '' ?>">
                                    <?php else: ?>
                                        <input type="text"
                                               name="ollama_model"
                                               id="ollama_model"
                                               class="form-control"
                                               placeholder="ex.: qwen:4b  (vazio = <?= htmlspecialchars($ollamaEnvModel, ENT_QUOTES, 'UTF-8') ?>)"
                                               value="<?= htmlspecialchars($selectedModel, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="form-text">
                                            Não foi possível listar modelos via API. Digite o nome (ou rode <code>ollama list</code>).
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="ollama_models_fallback">Fallbacks (opcional)</label>
                                    <input type="text"
                                           name="ollama_models_fallback"
                                           id="ollama_models_fallback"
                                           class="form-control"
                                           placeholder="llama3.2, qwen:4b"
                                           value="<?= htmlspecialchars($fallbackModels, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="form-text">
                                        Separados por vírgula. A API do Ollama não mistura modelos numa única chamada;
                                        o chat tenta o principal e, se falhar, o próximo da lista.
                                    </div>
                                </div>

                                <div id="mcp-ia-resultado" class="mb-3">
                                    <?php
                                    $llmTest = $_SESSION['mcp_llm_test_result'] ?? null;
                                    unset($_SESSION['mcp_llm_test_result']);
                                    if (is_array($llmTest) && !empty($llmTest['message'])):
                                        $llmType = in_array(($llmTest['type'] ?? ''), ['success', 'warning', 'danger', 'info'], true)
                                            ? (string) $llmTest['type']
                                            : 'info';
                                        ?>
                                        <div class="alert alert-<?= htmlspecialchars($llmType, ENT_QUOTES, 'UTF-8') ?> mb-0" role="alert">
                                            <?= htmlspecialchars((string) $llmTest['message'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-light border small mb-0 text-muted">
                                            Clique em <strong>Testar IA</strong> para pingar o motor selecionado em «Usar agora».
                                            A resposta aparece nesta faixa (verde = ok, vermelho = falha).
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-sm-flex">
                                    <button type="submit" name="form_action" value="save" class="btn btn-success btn-lg flex-grow-1">
                                        <i class="fas fa-save me-2"></i>Salvar Configuração
                                    </button>
                                    <button type="submit" name="form_action" value="test_llm" id="btn-test-llm" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-vial me-2"></i>Testar IA
                                    </button>
                                </div>
                            </form>
                            <script>
                            (function () {
                                var form = document.getElementById('form-mcp-api-config');
                                var btn = document.getElementById('btn-test-llm');
                                if (form && btn) {
                                    form.addEventListener('submit', function (ev) {
                                        var submitter = ev.submitter;
                                        if (!submitter || submitter.getAttribute('value') !== 'test_llm') {
                                            return;
                                        }
                                        btn.disabled = true;
                                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Testando…';
                                    });
                                }
                            })();
                            </script>
                            <?php if ($ollamaModels !== []): ?>
                            <script>
                            (function () {
                                var sel = document.getElementById('ollama_model');
                                var custom = document.getElementById('ollama_model_custom');
                                if (!sel || !custom) return;
                                function sync() {
                                    var isCustom = sel.value === '__custom__';
                                    custom.classList.toggle('d-none', !isCustom);
                                    custom.disabled = !isCustom;
                                }
                                sel.addEventListener('change', sync);
                                sync();
                            })();
                            </script>
                            <?php endif; ?>
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
                                <li>Chaves de IA (Groq, Gemini, OpenAI, Claude) e a URL do Ollama ficam nesta tela. O <code>.env</code> só entra se o campo correspondente estiver vazio.</li>
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
