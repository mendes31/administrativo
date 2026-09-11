<?php

use App\adms\Models\Services\InternalChat\InternalChatLlmSettings;

$llmCatalog = $this->data['llm_catalog'] ?? InternalChatLlmSettings::catalog();
$llmProvider = (string) ($this->data['llm_provider'] ?? 'auto');
$llmMasks = $this->data['llm_key_masks'] ?? [];
$config = $this->data['mcp_api_config'] ?? [];

$renderModelField = static function (
    string $name,
    string $current,
    array $meta
): void {
    $models = $meta['models'] ?? [];
    $placeholder = (string) ($meta['default_model'] ?? '');
    if ($current !== '' && $models !== [] && !in_array($current, $models, true)) {
        array_unshift($models, $current);
    }
    ?>
    <label class="form-label" for="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">Modelo</label>
    <?php if ($models !== []): ?>
        <select name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                id="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                class="form-select">
            <option value=""><?= htmlspecialchars('Padrão (' . $placeholder . ')', ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach ($models as $modelName): ?>
                <option value="<?= htmlspecialchars((string) $modelName, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $current === $modelName ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $modelName, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text"
               class="form-control mt-2"
               name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>_custom"
               placeholder="Ou cole outro ID de modelo"
               value="">
        <div class="form-text">Se preencher o campo extra, ele substitui a lista (o catálogo do Groq/Gemini muda com frequência).</div>
    <?php else: ?>
        <input type="text"
               class="form-control"
               name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
               value="<?= htmlspecialchars($current, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php
};

$renderKeyField = static function (string $field, string $slot, array $masks, array $meta): void {
    $mask = trim((string) ($masks[$slot] ?? ''));
    $clearName = 'clear_' . $field;
    ?>
    <label class="form-label" for="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>">Chave da API</label>
    <input type="password"
           name="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>"
           id="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>"
           class="form-control"
           autocomplete="new-password"
           placeholder="<?= $mask !== '' ? 'Deixe em branco para manter ' . $mask : 'Cole a chave aqui' ?>">
    <div class="form-text">
        <?php if ($mask !== ''): ?>
            Cadastrada: <code><?= htmlspecialchars($mask, ENT_QUOTES, 'UTF-8') ?></code>.
        <?php endif; ?>
        Obtenha em
        <a href="<?= htmlspecialchars((string) $meta['key_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
            <?= htmlspecialchars((string) $meta['key_url'], ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
    <?php if ($mask !== ''): ?>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($clearName, ENT_QUOTES, 'UTF-8') ?>"
                   id="<?= htmlspecialchars($clearName, ENT_QUOTES, 'UTF-8') ?>" value="1">
            <label class="form-check-label small" for="<?= htmlspecialchars($clearName, ENT_QUOTES, 'UTF-8') ?>">
                Apagar a chave cadastrada
            </label>
        </div>
    <?php endif; ?>
    <?php
};
?>

<hr class="my-4">
<h6 class="mb-2"><i class="fas fa-brain me-1"></i> Motor de IA (roteamento / análise)</h6>
<p class="small text-muted">
    Cadastre as chaves aqui para trocar de ferramenta sem editar o <code>.env</code>.
    O chat usa o motor selecionado; em <strong>Automático</strong> tenta Groq → Gemini → OpenAI → Claude → Ollama.
    Campos vazios caem no <code>.env</code>, se existir.
</p>

<div class="mb-3">
    <label class="form-label" for="llm_provider">Usar agora</label>
    <select name="llm_provider" id="llm_provider" class="form-select">
        <option value="auto" <?= $llmProvider === 'auto' ? 'selected' : '' ?>>Automático (primeiro que tiver chave)</option>
        <option value="groq" <?= $llmProvider === 'groq' ? 'selected' : '' ?>>Groq</option>
        <option value="gemini" <?= $llmProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini</option>
        <option value="openai" <?= $llmProvider === 'openai' ? 'selected' : '' ?>>OpenAI</option>
        <option value="anthropic" <?= $llmProvider === 'anthropic' ? 'selected' : '' ?>>Anthropic Claude</option>
        <option value="ollama" <?= $llmProvider === 'ollama' ? 'selected' : '' ?>>Ollama (local)</option>
    </select>
</div>

<div class="accordion mb-3" id="accordionLlmProviders">
    <?php
    $slots = ['groq', 'gemini', 'openai', 'anthropic'];
    foreach ($slots as $i => $slot):
        $meta = $llmCatalog[$slot] ?? [];
        $headingId = 'heading-llm-' . $slot;
        $collapseId = 'collapse-llm-' . $slot;
        $open = $llmProvider === $slot || ($llmProvider === 'auto' && $i === 0);
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="<?= $headingId ?>">
                <button class="accordion-button <?= $open ? '' : 'collapsed' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#<?= $collapseId ?>"
                        aria-expanded="<?= $open ? 'true' : 'false' ?>"
                        aria-controls="<?= $collapseId ?>">
                    <?= htmlspecialchars((string) ($meta['label'] ?? $slot), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($llmMasks[$slot])): ?>
                        <span class="badge text-bg-success ms-2">chave ok</span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="<?= $collapseId ?>"
                 class="accordion-collapse collapse <?= $open ? 'show' : '' ?>"
                 aria-labelledby="<?= $headingId ?>">
                <div class="accordion-body">
                    <p class="small text-muted">
                        Conta:
                        <a href="<?= htmlspecialchars((string) ($meta['signup_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            criar / entrar
                        </a>
                    </p>
                    <div class="mb-3">
                        <?php
                        $keyField = match ($slot) {
                            'groq' => 'llm_groq_api_key',
                            'gemini' => 'llm_gemini_api_key',
                            'openai' => 'llm_openai_api_key',
                            default => 'llm_anthropic_api_key',
                        };
                        $renderKeyField($keyField, $slot, $llmMasks, $meta);
                        ?>
                    </div>
                    <?php if ($slot === 'openai'): ?>
                        <div class="mb-3">
                            <label class="form-label" for="llm_openai_base_url">URL base (OpenAI-compatible)</label>
                            <input type="url"
                                   name="llm_openai_base_url"
                                   id="llm_openai_base_url"
                                   class="form-control"
                                   placeholder="https://api.openai.com/v1"
                                   value="<?= htmlspecialchars((string) ($config['llm_openai_base_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Vazio = <?= htmlspecialchars((string) ($meta['base_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <?php
                        $modelField = match ($slot) {
                            'groq' => 'llm_groq_model',
                            'gemini' => 'llm_gemini_model',
                            'openai' => 'llm_openai_model',
                            default => 'llm_anthropic_model',
                        };
                        $currentModel = trim((string) ($config[$modelField] ?? ''));
                        $renderModelField($modelField, $currentModel, $meta);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php
    $ollamaMeta = $llmCatalog['ollama'] ?? [];
    $ollamaOpen = $llmProvider === 'ollama';
    ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-llm-ollama">
            <button class="accordion-button <?= $ollamaOpen ? '' : 'collapsed' ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-llm-ollama"
                    aria-expanded="<?= $ollamaOpen ? 'true' : 'false' ?>"
                    aria-controls="collapse-llm-ollama">
                <?= htmlspecialchars((string) ($ollamaMeta['label'] ?? 'Ollama'), ENT_QUOTES, 'UTF-8') ?>
                <?php if ($ollamaUrl !== ''): ?>
                    <span class="badge text-bg-secondary ms-2">URL ok</span>
                <?php endif; ?>
            </button>
        </h2>
        <div id="collapse-llm-ollama"
             class="accordion-collapse collapse <?= $ollamaOpen ? 'show' : '' ?>"
             aria-labelledby="heading-llm-ollama">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label" for="llm_ollama_url">URL do Ollama</label>
                    <input type="url"
                           name="llm_ollama_url"
                           id="llm_ollama_url"
                           class="form-control"
                           placeholder="http://127.0.0.1:11434"
                           value="<?= htmlspecialchars((string) ($config['llm_ollama_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text">
                        Instale em <a href="https://ollama.com" target="_blank" rel="noopener">ollama.com</a>
                        e rode <code>ollama pull qwen2.5:14b</code> (melhor que llama3.2).
                        Vazio = <code>OLLAMA_URL</code> do .env
                        <?php if ($ollamaUrl !== '' && trim((string) ($config['llm_ollama_url'] ?? '')) === ''): ?>
                            (atual: <code><?= htmlspecialchars($ollamaUrl, ENT_QUOTES, 'UTF-8') ?></code>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
