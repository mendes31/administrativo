<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsMcpApiConfigRepository;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\AiAnalyst\Adapters\AiReportRunnerSelector;
use App\adms\Models\Services\AiAnalyst\AiAnalystOrchestrator;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Auth\AiConversationContext;
use App\adms\Models\Services\AiAnalyst\Auth\AiDataScopeResolver;
use App\adms\Models\Services\AiAnalyst\Catalog\AiColumnsResolverSelector;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiDeterministicBusinessPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiDeterministicTechnicalPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiLazyPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiLlmPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Planning\AiPreferLlmPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiResultNarrator;
use App\adms\Models\Services\AiAnalyst\Routing\AiModeRouter;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\CatalogDescribeTableTool;
use App\adms\Models\Services\AiAnalyst\Tools\CatalogTechnicalSearchTool;
use App\adms\Models\Services\AiAnalyst\Tools\ComparePeriodsTool;
use App\adms\Models\Services\AiAnalyst\Tools\ListApprovedReportsTool;
use App\adms\Models\Services\AiAnalyst\Tools\RunApprovedReportTool;
use App\adms\Models\Services\InternalChat\ChatToolPermissionGate;
use App\adms\Models\Services\InternalChat\InternalChatLlmClient;

class McpChatApi
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (empty($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'logout' => true,
                'message' => 'Sessão expirada. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar permissão via página lógica "McpChat"
        $buttonRepo = new ButtonPermissionUserRepository();
        $perms = $buttonRepo->buttonPermission(['McpChat']);
        if (empty($perms) || !in_array('McpChat', $perms, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para usar o chat MCP.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $message = trim((string) ($payload['message'] ?? ''));
        $conversation = new AiConversationContext();
        if (!empty($payload['clear_context'])) {
            $conversation->clear();
            if ($message === '' || $message === '__tjz_clear__') {
                echo json_encode([
                    'success' => true,
                    'cleared' => true,
                    'message' => 'Contexto analítico limpo.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        $flags = AiAnalystFeatureFlags::fromEnvironment();
        $requestedMode = strtolower(trim((string) ($payload['mode'] ?? '')));
        // Com o AI Analyst ligado, o caminho padrão é automático: o servidor decide.
        // O seletor de perfil só existe para diagnóstico e não eleva permissão.
        if ($flags->enabled() && !$flags->modeSelectorEnabled()) {
            $requestedMode = 'auto';
        } elseif ($requestedMode === '') {
            $requestedMode = $flags->enabled() ? 'auto' : 'legacy';
        }

        if ($message === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Mensagem não informada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $configRepo = new AdmsMcpApiConfigRepository();
        $config = $configRepo->getConfig();

        if (empty($config) || empty($config['base_url']) || empty($config['is_active'])) {
            echo json_encode([
                'success' => false,
                'message' => 'API MCP não está configurada ou está desativada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $endpoint = rtrim((string) $config['base_url'], '/');

        // Com mode=legacy (ou AI desligado), o fluxo abaixo permanece igual.
        if ($requestedMode !== '' && $requestedMode !== 'legacy') {
            $gate = new ChatToolPermissionGate();
            $fullSystemAccess = UserAccessHelper::hasFullSystemAccess();
            $actor = new AiActorContext(
                (int) ($_SESSION['user_id'] ?? 0),
                $fullSystemAccess,
                $gate->allowedTools(),
                (new AiDataScopeResolver())->resolve($fullSystemAccess)
            );
            $decision = (new AiModeRouter($flags))->decide($requestedMode, $actor, $message, $conversation);
            if (!$decision->allowed) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => $decision->reason,
                    'mode' => 'legacy',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if ($decision->effectiveMode !== 'legacy') {
                $reportRunner = (new AiReportRunnerSelector())->select($flags);
                $registry = new AiToolRegistry();
                $registry->register(new ListApprovedReportsTool($reportRunner));
                $registry->register(new RunApprovedReportTool($reportRunner));
                $registry->register(new ComparePeriodsTool());
                $technicalCatalog = new AiTechnicalCatalogService();
                $columnsResolver = (new AiColumnsResolverSelector())->select($flags, $technicalCatalog);
                $registry->register(new CatalogTechnicalSearchTool($technicalCatalog));
                $registry->register(new CatalogDescribeTableTool($technicalCatalog, $columnsResolver));
                $liveReports = $flags->realReportsEnabled();
                $sessionDomain = $conversation->domain();
                $sessionBinding = $conversation->binding();
                $llmPlanner = new AiLazyPlanner(
                    static fn (): AiPlannerInterface => new AiLlmPlanner(
                        new InternalChatLlmClient(),
                        $liveReports,
                        $sessionDomain,
                        $sessionBinding
                    )
                );
                $planner = new AiPreferLlmPlanner(
                    $llmPlanner,
                    new AiDeterministicBusinessPlanner(
                        new AiDeterministicTechnicalPlanner($llmPlanner),
                        null,
                        $technicalCatalog,
                        $liveReports,
                        $sessionDomain,
                        $sessionBinding
                    ),
                    static fn (): bool => (new InternalChatLlmClient())->isAnyConfigured()
                );
                $llmClient = new InternalChatLlmClient();
                $orchestrator = new AiAnalystOrchestrator(
                    $flags,
                    $planner,
                    $registry,
                    new AiPlanValidator($registry, 8),
                    $llmClient->isAnyConfigured() ? new AiResultNarrator($llmClient) : null
                );
                $plannerMessage = $conversation->enrichMessageForPlanner($message);
                $result = $orchestrator->handle($plannerMessage, $decision->effectiveMode, $actor);
                $conversation->rememberFromResult($message, $decision->effectiveMode, $result);

                echo json_encode([
                    'success' => true,
                    'reply' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'payload' => $result,
                    'http_code' => 200,
                    'mode' => $decision->effectiveMode,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        // Piloto local (homologação): consultas RH no MySQL do Portal, sem servidor MCP externo.
        if (strcasecmp($endpoint, 'local:internal') === 0 || strcasecmp($endpoint, 'local://internal') === 0) {
            $gate = new \App\adms\Models\Services\InternalChat\ChatToolPermissionGate();
            $agent = new \App\adms\Models\Services\InternalChat\LocalInternalChatAgent(perms: $gate);
            $authContext = [
                'user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'allowed_tools' => $gate->allowedTools(),
            ];
            $result = $agent->handle($message, $authContext);
            echo json_encode([
                'success' => true,
                'reply' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'payload' => $result,
                'http_code' => 200,
                'mode' => 'local:internal',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // O servidor MCP espera exatamente: { "message": "texto" }
        $requestBody = json_encode([
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $requestBody,
            // Alguns proxies/túneis (como devtunnels) podem usar certificados não totalmente confiáveis.
            // Para este endpoint específico, desabilitamos a verificação estrita de SSL.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao contatar servidor MCP: ' . $curlError
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Se o servidor MCP retornou erro HTTP, repassar corpo e código para facilitar diagnóstico
        if ($httpCode >= 400) {
            echo json_encode([
                'success' => false,
                'message' => 'Servidor MCP retornou erro HTTP ' . $httpCode,
                'raw' => $responseBody,
                'http_code' => $httpCode,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Proxy transparente: repassa exatamente a resposta do MCP (texto ou JSON) para o frontend
        echo json_encode([
            'success' => true,
            'reply' => $responseBody,
            'http_code' => $httpCode,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

