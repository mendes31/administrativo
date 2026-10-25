<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Routing;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Auth\AiConversationContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;

/**
 * Classificador local e conservador do modo automatico.
 * Negócio prevalece sobre diagnóstico de schema. Na duvida, legado.
 */
final class AiAutomaticModeClassifier
{
    public function classify(
        string $message,
        AiActorContext $actor,
        AiAnalystFeatureFlags $flags,
        ?AiConversationContext $conversation = null
    ): string {
        $message = mb_strtolower(trim($message));
        if ($message === '' || $this->matchesLegacyCapability($message)) {
            return 'legacy';
        }

        // Análises de negócio primeiro — o catálogo técnico é infraestrutura, não o produto.
        if ($flags->businessEnabled() && $this->matchesBusinessQuestion($message, $conversation)) {
            return 'business';
        }

        if (
            $actor->fullSystemAccess
            && $flags->technicalEnabled()
            && $this->matchesExplicitSchemaQuestion($message)
        ) {
            return 'technical';
        }

        return 'legacy';
    }

    private function matchesLegacyCapability(string $message): bool
    {
        return preg_match(
            '/\b(?:oi|ola|olá|bom\s+dia|boa\s+tarde|boa\s+noite|ajuda)\b'
            . '|\b(?:sala|salas|reuniao|reunião|agendar|reservar|reserva)\b'
            . '|\b(?:funcionario|funcionário|funcionarios|funcionários|colaborador|colaboradores|admitido|admitidos|demitido|demitidos|desligado|desligados|rh)\b/iu',
            $message
        ) === 1;
    }

    /**
     * Diagnóstico de schema só com intenção explícita (não basta a palavra «tabela»).
     */
    private function matchesExplicitSchemaQuestion(string $message): bool
    {
        if (preg_match(
            '/\b(?:sql|procedure|query\s*manager|di\s*api|service\s*layer|obj(?:ect)?\s*type|otype|objtype)\b/iu',
            $message
        ) === 1) {
            return true;
        }

        $asksStructure = preg_match(
            '/\b(?:explique|explicar|descreva|descrever|campos?|colunas?|udf|user\s*fields?|userfields?|estrutura|schema|metadados?)\b/iu',
            $message
        ) === 1;

        $namesTable = preg_match('/\b(?:beas_[a-z0-9_]+|o[a-z]{3})\b/iu', $message) === 1
            || preg_match('/\b(?:oitm|ocrd|oinv|ordr|opor|odrf|inv1|rdr1|hana|beas)\b/iu', $message) === 1;

        if ($asksStructure && $namesTable) {
            return true;
        }

        if (
            preg_match('/\btabelas?\s+(?:da\s+|de\s+|do\s+)?(?:beas_[a-z0-9_]+|o[a-z]{3}|oitm|ocrd|oinv|ordr|odrf)\b/iu', $message) === 1
            || preg_match('/\b(?:explique|descreva|campos?)\b.+\btabelas?\b/iu', $message) === 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Negócios: domínio explícito + intenção analítica, ou follow-up de períodos
     * após análise de vendas na mesma sessão.
     */
    private function matchesBusinessQuestion(string $message, ?AiConversationContext $conversation): bool
    {
        $analytical = $this->hasAnalyticalIntent($message);
        if (!$analytical) {
            return false;
        }

        if ($this->mentionsBusinessDomain($message)) {
            return true;
        }

        // «12 meses de 2025» / «compare os meses de 2025» → série anual (fase atual = vendas).
        if ($this->mentionsYearSeries($message)) {
            return true;
        }

        // «compare jan/25, mar/26» depois de vendas na sessão
        return $conversation !== null
            && $conversation->domain() === 'sales'
            && $conversation->looksLikeAnalyticalFollowUp($message);
    }

    private function mentionsYearSeries(string $message): bool
    {
        if (preg_match(
            '/\b(?:12\s*meses|todos\s+os\s+meses|ano\s+(?:inteiro\s+)?(?:de\s+)?20\d{2})\b/iu',
            $message
        ) === 1) {
            return true;
        }

        return preg_match('/\b(?:comparar|compare|analis[ae]r?|avali[ae]r?)\b/iu', $message) === 1
            && preg_match('/\bmeses?\b/iu', $message) === 1
            && preg_match('/\b(20\d{2})\b/u', $message) === 1;
    }

    private function mentionsBusinessDomain(string $message): bool
    {
        return preg_match(
            '/\b(?:venda|vendas|faturamento|receita|pedido|pedidos|cliente|clientes|compra|compras|fornecedor|fornecedores)\b'
            . '|\b(?:financeiro|financeira|contas\s+a\s+pagar|contas\s+a\s+receber|inadimplencia|inadimplência|fluxo\s+de\s+caixa)\b'
            . '|\b(?:estoque|inventario|inventário|produto|produtos|item|itens|producao|produção|ordem\s+de\s+producao|ordem\s+de\s+produção)\b'
            . '|\b(?:margem|custo|custos|indicador|indicadores|kpi)\b/iu',
            $message
        ) === 1;
    }

    private function hasAnalyticalIntent(string $message): bool
    {
        if (preg_match(
            '/\b(?:comparar|compare|comparativo|comparação|comparacao|varia[cç][aã]o|tend[eê]ncia|versus|vs|analis[ae]r?)\b/iu',
            $message
        ) === 1) {
            return true;
        }

        if (preg_match(
            '/\b(?:12\s*meses|todos\s+os\s+meses|ano\s+(?:inteiro\s+)?(?:de\s+)?20\d{2})\b/iu',
            $message
        ) === 1) {
            return true;
        }

        $ctx = new AiConversationContext();

        return $ctx->countPeriodHints($message) >= 2;
    }
}
