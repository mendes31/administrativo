<?php

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\NavbarLayoutCacheHelper;
use App\adms\Models\Repository\AdmsMcpApiConfigRepository;
use App\adms\Models\Services\InternalChat\InternalChatLlmClient;
use App\adms\Models\Services\InternalChat\InternalChatLlmSettings;

class SaveMcpApiConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_mcp_api_config', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        $action = (string) ($_POST['form_action'] ?? 'save');
        if ($action === 'test_llm') {
            $this->testLlm();
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config#mcp-ia-resultado');
            exit;
        }

        $payload = $this->collectFormData();
        if ($payload === null) {
            header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
            exit;
        }

        $repo = new AdmsMcpApiConfigRepository();
        $saved = $repo->saveConfig($payload);

        if ($saved) {
            NavbarLayoutCacheHelper::clear();
            $_SESSION['msg'] = 'Configurações da API MCP salvas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar as configurações da API MCP.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config');
        exit;
    }

    private function testLlm(): void
    {
        $repo = new AdmsMcpApiConfigRepository();
        $settings = (new InternalChatLlmSettings($repo->getConfig() ?: []))->withPostedForm($_POST);
        $plan = $settings->resolveCallPlan()[0] ?? null;
        if ($plan === null) {
            $this->setTestFeedback(
                'Nenhum motor de IA configurado. Informe uma chave (Groq/Gemini/OpenAI/Claude) ou a URL do Ollama, depois teste de novo.',
                'warning'
            );
            return;
        }

        $client = new InternalChatLlmClient($settings);
        $result = $client->complete(
            'Você é um teste de conectividade. Responda somente com a palavra ok.',
            'ping',
            false,
            true
        );

        if ($result === null) {
            $detail = $client->getLastError() ?? 'Sem detalhe da API.';
            $this->setTestFeedback(
                'Falha ao falar com ' . (string) $plan['label'] . ' (' . (string) $plan['slot'] . '). ' . $detail,
                'danger'
            );
            return;
        }

        $reply = trim((string) ($result['text'] ?? ''));
        $preview = $reply !== '' ? ' Resposta: «' . mb_substr($reply, 0, 80) . '».' : '';
        $this->setTestFeedback(
            'IA funcionando: ' . (string) ($result['provider'] ?? $plan['slot']) . '.' . $preview
            . ' Se alterou chaves ou o motor, clique em Salvar para gravar.',
            'success'
        );
    }

    private function setTestFeedback(string $message, string $type): void
    {
        $_SESSION['msg'] = $message;
        $_SESSION['msg_type'] = $type;
        $_SESSION['mcp_llm_test_result'] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    private function postedModel(string $field): string
    {
        $custom = trim((string) ($_POST[$field . '_custom'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return trim((string) ($_POST[$field] ?? ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function collectFormData(): ?array
    {
        $baseUrl = trim($_POST['base_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $ollamaModel = trim((string) ($_POST['ollama_model'] ?? ''));
        $ollamaFallback = trim((string) ($_POST['ollama_models_fallback'] ?? ''));
        if ($ollamaModel === '__custom__') {
            $ollamaModel = trim((string) ($_POST['ollama_model_custom'] ?? ''));
        }

        $isLocalInternal = in_array(strtolower($baseUrl), ['local:internal', 'local://internal'], true);
        if ($baseUrl === '' || (!$isLocalInternal && !filter_var($baseUrl, FILTER_VALIDATE_URL))) {
            $_SESSION['msg'] = 'Informe uma URL válida (https://...) ou use local:internal para o piloto RH no Portal.';
            $_SESSION['msg_type'] = 'danger';
            return null;
        }

        $provider = strtolower(trim((string) ($_POST['llm_provider'] ?? 'auto')));
        if (!in_array($provider, InternalChatLlmSettings::PROVIDERS, true)) {
            $provider = 'auto';
        }

        $openaiBase = trim((string) ($_POST['llm_openai_base_url'] ?? ''));
        if ($openaiBase !== '' && !filter_var($openaiBase, FILTER_VALIDATE_URL)) {
            $_SESSION['msg'] = 'A URL base da OpenAI/compatível precisa ser uma URL válida, ou fique em branco para o padrão.';
            $_SESSION['msg_type'] = 'danger';
            return null;
        }

        $ollamaUrl = trim((string) ($_POST['llm_ollama_url'] ?? ''));
        if ($ollamaUrl !== '' && !filter_var($ollamaUrl, FILTER_VALIDATE_URL)) {
            $_SESSION['msg'] = 'A URL do Ollama precisa ser válida (ex.: http://127.0.0.1:11434).';
            $_SESSION['msg_type'] = 'danger';
            return null;
        }

        return [
            'base_url' => $isLocalInternal ? 'local:internal' : rtrim($baseUrl, '/'),
            'is_active' => $isActive,
            'ollama_model' => $ollamaModel,
            'ollama_models_fallback' => $ollamaFallback,
            'llm_provider' => $provider,
            'llm_groq_api_key' => trim((string) ($_POST['llm_groq_api_key'] ?? '')),
            'llm_groq_model' => $this->postedModel('llm_groq_model'),
            'llm_gemini_api_key' => trim((string) ($_POST['llm_gemini_api_key'] ?? '')),
            'llm_gemini_model' => $this->postedModel('llm_gemini_model'),
            'llm_openai_api_key' => trim((string) ($_POST['llm_openai_api_key'] ?? '')),
            'llm_openai_base_url' => $openaiBase !== '' ? rtrim($openaiBase, '/') : '',
            'llm_openai_model' => $this->postedModel('llm_openai_model'),
            'llm_anthropic_api_key' => trim((string) ($_POST['llm_anthropic_api_key'] ?? '')),
            'llm_anthropic_model' => $this->postedModel('llm_anthropic_model'),
            'llm_ollama_url' => $ollamaUrl !== '' ? rtrim($ollamaUrl, '/') : '',
            'clear_llm_groq_api_key' => !empty($_POST['clear_llm_groq_api_key']),
            'clear_llm_gemini_api_key' => !empty($_POST['clear_llm_gemini_api_key']),
            'clear_llm_openai_api_key' => !empty($_POST['clear_llm_openai_api_key']),
            'clear_llm_anthropic_api_key' => !empty($_POST['clear_llm_anthropic_api_key']),
        ];
    }
}
