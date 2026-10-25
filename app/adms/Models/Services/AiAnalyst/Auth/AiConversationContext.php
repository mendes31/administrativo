<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Auth;

use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;

/**
 * Memória curta da conversa analítica (sessão PHP).
 * Permite follow-ups como «compare jan/25, mar/26» após uma análise de vendas.
 */
final class AiConversationContext
{
    private const SESSION_KEY = 'ai_analyst_conversation';

    /** @return array{domain?: string, binding?: string, last_tool?: string, last_message?: string, updated_at?: int} */
    public function get(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        $value = $_SESSION[self::SESSION_KEY] ?? null;

        return is_array($value) ? $value : [];
    }

    public function domain(): ?string
    {
        $domain = $this->get()['domain'] ?? null;

        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    public function binding(): ?string
    {
        $binding = $this->get()['binding'] ?? null;

        return is_string($binding) && $binding !== '' ? $binding : null;
    }

    public function clear(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * @param array<string, mixed> $result payload do orchestrator
     */
    public function rememberFromResult(string $userMessage, string $profile, array $result): void
    {
        if ($profile !== 'business' || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $hint = is_array($data['semantic_hint'] ?? null) ? $data['semantic_hint'] : [];
        $metric = is_array($data['metric'] ?? null) ? $data['metric'] : [];

        $domain = trim((string) ($hint['domain'] ?? ''));
        if ($domain === '' && $this->messageMentionsSales($userMessage)) {
            $domain = 'sales';
        }
        if ($domain === '' && in_array((string) ($result['tool'] ?? ''), ['analysis.compare', 'report.run_approved'], true)) {
            $domain = 'sales';
        }
        if ($domain === '') {
            $domain = (string) ($this->domain() ?? '');
        }
        if ($domain === '') {
            return;
        }

        $binding = trim((string) ($hint['binding'] ?? $metric['binding'] ?? $this->binding() ?? FakeReportRunner::BINDING));
        if ($binding === '') {
            $binding = FakeReportRunner::BINDING;
        }

        $_SESSION[self::SESSION_KEY] = [
            'domain' => $domain,
            'binding' => $binding,
            'last_tool' => (string) ($result['tool'] ?? ''),
            'last_message' => mb_substr(trim($userMessage), 0, 500),
            'updated_at' => time(),
        ];
    }

    /**
     * Anexa dica de sessão para o planejador LLM quando o follow-up omite o domínio.
     */
    public function enrichMessageForPlanner(string $message): string
    {
        $domain = $this->domain();
        $binding = $this->binding();
        if ($domain === null) {
            return $message;
        }
        if ($this->messageMentionsSales($message) || $this->messageMentionsForeignDomain($message)) {
            return $message;
        }
        if (!$this->looksLikeAnalyticalFollowUp($message)) {
            return $message;
        }

        return trim($message) . "\n\n[contexto_sessao: continue a análise anterior;"
            . ' dominio=' . $domain
            . '; report_binding=' . ($binding ?? FakeReportRunner::BINDING)
            . '; se a pergunta só cita períodos, use esse binding.]';
    }

    public function looksLikeAnalyticalFollowUp(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === '') {
            return false;
        }

        if (preg_match(
            '/\b(?:comparar|compare|comparativo|comparação|comparacao|varia[cç][aã]o|tend[eê]ncia|versus|vs)\b/iu',
            $normalized
        ) === 1) {
            return true;
        }

        return $this->countPeriodHints($normalized) >= 2;
    }

    public function countPeriodHints(string $message): int
    {
        $count = 0;
        $count += preg_match_all(
            '/\b(?:janeiro|fevereiro|mar[cç]o|abril|maio|junho|julho|agosto|setembro|outubro|novembro|dezembro'
            . '|jan|fev|mar|abr|mai|jun|jul|ago|set|out|nov|dez)\s*[\/\-]?\s*(?:de\s+)?(?:20\d{2}|\d{2})\b/iu',
            $message
        ) ?: 0;
        $count += preg_match_all(
            '/(?<!\d)(0?[1-9]|1[0-2])\s*[\/\-]\s*((?:20)?\d{2})(?!\d)/u',
            $message
        ) ?: 0;

        return $count;
    }

    private function messageMentionsSales(string $message): bool
    {
        return preg_match('/\b(?:vendas?|faturamento|receita)\b/iu', $message) === 1;
    }

    private function messageMentionsForeignDomain(string $message): bool
    {
        return preg_match(
            '/\b(?:compras?|estoques?|invent[aá]rio|financeir[oa]s?|produ[cç][aã]o)\b/iu',
            $message
        ) === 1;
    }
}
