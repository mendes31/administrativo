<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;
use App\adms\Models\Services\InternalChat\InternalChatLlmClient;

/** Adapter do cliente LLM existente. O retorno ainda passa pelo AiPlanValidator. */
final class AiLlmPlanner implements AiPlannerInterface
{
    public function __construct(
        private readonly InternalChatLlmClient $llm,
        private readonly bool $liveReports = false,
        private readonly ?string $sessionDomain = null,
        private readonly ?string $sessionBinding = null
    ) {
    }

    /** @param list<AiToolDefinition> $tools */
    public function plan(string $message, string $profile, array $tools): AiPlannerResult
    {
        if (!$this->llm->isAnyConfigured()) {
            return new AiPlannerResult(null, 'none', 'Nenhum provedor LLM está configurado.');
        }

        $schemas = array_map(
            static fn (AiToolDefinition $tool): array => $tool->toPlannerSchema(),
            $tools
        );
        $system = $this->systemPrompt($profile);
        $user = json_encode([
            'effective_profile' => $profile,
            'message' => mb_substr(trim($message), 0, 4000),
            'allowed_tools' => $schemas,
            'hints' => $this->hints($profile),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $this->llm->complete($system, (string) $user, true, true);
        if ($result === null) {
            return new AiPlannerResult(null, 'llm-error', 'Falha ao consultar o provedor LLM.');
        }

        $raw = trim((string) ($result['text'] ?? ''));
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;
        $plan = json_decode($raw, true);
        if (!is_array($plan)) {
            return new AiPlannerResult(null, (string) ($result['provider'] ?? 'llm'), 'O planejador retornou JSON inválido.');
        }

        return new AiPlannerResult($plan, (string) ($result['provider'] ?? 'llm'));
    }

    private function systemPrompt(string $profile): string
    {
        $base = 'Você é o planejador seguro do Tiarajuzinho. '
            . 'Responda SOMENTE JSON válido com as chaves: goal (string), steps (lista), '
            . 'clarification (string opcional) e semantic_hint (objeto opcional). '
            . 'Cada step deve ter: step_id (ex.: s1), tool, arguments (objeto) e depends_on (lista de step_id). '
            . 'Máximo de 8 passos. Use exclusivamente as tools informadas em allowed_tools. '
            . 'Nunca produza SQL, URL, endpoint, classe PHP, nome de serviço HTTP ou argumento não declarado na tool. '
            . 'Interprete a quantidade de períodos, filtros e intenções a partir da mensagem do usuário — '
            . 'não assuma um limite fixo de 2 períodos se a pergunta pedir mais. '
            . 'Se faltarem dados essenciais, devolva steps vazio e clarification objetiva em português.';

        if ($profile !== 'business') {
            return $base;
        }

        return $base . ' '
            . 'Perfil business: analise vendas/faturamento com tools fechadas. '
            . 'Para cada período pedido (mês/ano), crie um step report.run_approved com '
            . 'report_binding="' . FakeReportRunner::BINDING . '", month (1-12) e year (AAAA). '
            . 'Se pedirem o ano inteiro / 12 meses de um ano (ex.: «compare os 12 meses de 2025»), '
            . 'use UM único step report.run_approved só com year (sem month) — não gere 12 steps. '
            . 'Se houver 2 a 5 períodos explícitos, acrescente analysis.compare com '
            . 'arguments.step_ids = lista dos step_id (ex.: ["s1","s2","s3"]) e depends_on igual. '
            . 'Pode usar left/right só quando forem exatamente 2 períodos. '
            . 'semantic_hint pode indicar domain=sales e tables como OINV/ORDR (dica semântica; sem SQL). '
            . 'Não invente outros bindings nesta fase. Se a pergunta for de outro domínio (compras, estoque), '
            . 'clarification explicando a limitação atual. '
            . 'Goals válidos: year_series, compare_periods, approved_period, clarify.';
    }

    /** @return array<string, mixed> */
    private function hints(string $profile): array
    {
        if ($profile !== 'business') {
            return [];
        }

        $hints = [
            'sales_binding' => $this->sessionBinding ?: FakeReportRunner::BINDING,
            'live_reports' => $this->liveReports,
            'max_compare_periods' => 5,
            'examples' => [
                'compare vendas de janeiro e junho de 2026',
                'compare vendas de janeiro 2024, janeiro 2025 e janeiro de 2026',
                'compare jan/25, mar/26',
            ],
        ];
        if ($this->sessionDomain !== null && $this->sessionDomain !== '') {
            $hints['session_domain'] = $this->sessionDomain;
            $hints['session_note'] = 'Se a mensagem só citar períodos (ex.: compare jan/25, mar/26), '
                . 'continue o domínio da sessão com report_binding=' . ($this->sessionBinding ?: FakeReportRunner::BINDING) . '.';
        }

        return $hints;
    }
}
