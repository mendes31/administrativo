<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\InternalChat\InternalChatLlmClient;

/**
 * Segunda passagem do LLM: narra totais já obtidos pelas tools.
 * Não inventa números nem SQL — só interpreta o payload sanitizado.
 */
final class AiResultNarrator
{
    public function __construct(private readonly InternalChatLlmClient $llm)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{text: string, provider: string}|null
     */
    public function narrate(string $userMessage, string $tool, string $fallbackText, array $data): ?array
    {
        if (!$this->llm->isAnyConfigured()) {
            return null;
        }
        if (!in_array($tool, ['analysis.compare', 'report.run_approved'], true)) {
            return null;
        }

        $facts = $this->facts($tool, $data, $fallbackText);
        if ($facts === []) {
            return null;
        }

        $system = 'Você é o Tiarajuzinho, analista de negócios do Portal Tiaraju. '
            . 'Escreva em português do Brasil, tom claro e profissional, 2 a 5 frases curtas ou bullets. '
            . 'Use APENAS os fatos JSON fornecidos (valores, períodos, variações). '
            . 'Não invente números, causas, filiais, produtos ou dados ausentes. '
            . 'Se um período estiver zerado ou faltando, diga isso com cautela e sugira verificar o relatório/fonte. '
            . 'Não mencione SQL, APIs, prompts nem ferramentas internas. '
            . 'Não repita a tabela inteira; destaque o insight principal e a tendência.';

        $user = json_encode([
            'pergunta_usuario' => mb_substr(trim($userMessage), 0, 2000),
            'tool' => $tool,
            'fatos' => $facts,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $this->llm->complete($system, (string) $user, false, true);
        if ($result === null) {
            return null;
        }

        $text = trim((string) ($result['text'] ?? ''));
        $text = preg_replace('/^```(?:\w+)?\s*/u', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/u', '', $text) ?? $text;
        $text = trim($text);
        if ($text === '' || mb_strlen($text) < 20) {
            return null;
        }

        return [
            'text' => mb_substr($text, 0, 4000),
            'provider' => (string) ($result['provider'] ?? 'llm'),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function facts(string $tool, array $data, string $fallbackText): array
    {
        $facts = [
            'titulo' => (string) ($data['name'] ?? ''),
            'origem' => (string) ($data['source_type'] ?? ''),
            'resumo_sistema' => mb_substr(trim($fallbackText), 0, 1500),
        ];

        if (is_array($data['analysis'] ?? null)) {
            $facts['analise_numerica'] = $data['analysis'];
        }

        if (is_array($data['rows'] ?? null)) {
            $facts['periodos'] = array_slice($data['rows'], 0, 8);
        }

        if (is_array($data['metric'] ?? null)) {
            $facts['metric'] = $data['metric'];
        }

        if (is_array($data['chart'] ?? null)) {
            $facts['grafico'] = [
                'titulo' => $data['chart']['title'] ?? null,
                'rotulos' => $data['chart']['labels'] ?? null,
                'valores' => $data['chart']['values'] ?? null,
            ];
        }

        return array_filter(
            $facts,
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []
        );
    }
}
