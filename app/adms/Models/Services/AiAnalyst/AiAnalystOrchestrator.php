<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiResultNarrator;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolExecutionContext;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;

/** Executa somente planos validados e tools registradas/autorizadas. */
final class AiAnalystOrchestrator
{
    public function __construct(
        private readonly AiAnalystFeatureFlags $flags,
        private readonly AiPlannerInterface $planner,
        private readonly AiToolRegistry $registry,
        private readonly AiPlanValidator $validator,
        private readonly ?AiResultNarrator $narrator = null
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(string $message, string $profile, AiActorContext $actor): array
    {
        $correlationId = bin2hex(random_bytes(16));
        $definitions = $this->registry->definitionsForProfile($profile);

        if (!$this->flags->plannerEnabled()) {
            return $this->reply(
                'O modo ' . $this->profileLabel($profile) . ' está disponível, mas o planejador permanece desligado pela feature flag.',
                'ai.mode.ready',
                'local-policy',
                $profile,
                $correlationId,
                ['available_tools' => array_map(static fn ($d): string => $d->name, $definitions)]
            );
        }

        $plannerResult = $this->planner->plan($message, $profile, $definitions);
        if ($plannerResult->plan === null) {
            return $this->reply(
                'Não foi possível criar um plano seguro para esta solicitação.',
                'ai.plan.error',
                $plannerResult->provider,
                $profile,
                $correlationId,
                [],
                [$plannerResult->error ?? 'Falha não detalhada do planejador.']
            );
        }

        $validation = $this->validator->validate($plannerResult->plan, $profile);
        if (!$validation->valid) {
            return $this->reply(
                'O plano foi bloqueado pela política de segurança e nenhuma tool foi executada.',
                'ai.plan.denied',
                $plannerResult->provider,
                $profile,
                $correlationId,
                [],
                $validation->errors
            );
        }

        $steps = $plannerResult->plan['steps'];
        $semanticHint = is_array($plannerResult->plan['semantic_hint'] ?? null)
            ? $plannerResult->plan['semantic_hint']
            : null;
        if ($steps === []) {
            $clarification = trim((string) ($plannerResult->plan['clarification'] ?? ''));
            return $this->reply(
                $clarification !== '' ? $clarification : 'Preciso de mais informações para montar um plano seguro.',
                'ai.clarification',
                $plannerResult->provider,
                $profile,
                $correlationId,
                $semanticHint !== null ? ['semantic_hint' => $semanticHint] : []
            );
        }

        if (!$this->flags->toolExecutionEnabled()) {
            $validatedData = ['planned_tools' => array_values(array_map(
                static fn (array $step): string => (string) ($step['tool'] ?? ''),
                $steps
            ))];
            if ($semanticHint !== null) {
                $validatedData['semantic_hint'] = $semanticHint;
            }

            return $this->reply(
                'O plano foi validado, porém a execução de tools permanece desligada pela feature flag.',
                'ai.plan.validated',
                $plannerResult->provider,
                $profile,
                $correlationId,
                $validatedData
            );
        }

        $context = new AiToolExecutionContext($correlationId, $profile, $actor);
        $executed = [];
        $lastResult = null;
        foreach ($steps as $step) {
            $toolName = (string) $step['tool'];
            $tool = $this->registry->get($toolName);
            if ($tool === null) {
                return $this->reply('Tool não registrada.', 'ai.execution.denied', 'local-policy', $profile, $correlationId);
            }
            $arguments = is_array($step['arguments'] ?? null) ? $step['arguments'] : [];
            $decision = $tool->authorize($actor, $profile, $arguments);
            if (!$decision->allowed) {
                return $this->reply(
                    'Execução bloqueada pela política: ' . $decision->reason,
                    'ai.execution.denied',
                    'local-policy',
                    $profile,
                    $correlationId,
                    ['executed_steps' => $executed]
                );
            }

            try {
                $lastResult = $tool->execute($context, $arguments);
            } catch (\Throwable) {
                return $this->reply(
                    'Falha controlada durante a execução da tool.',
                    'ai.execution.error',
                    'local-policy',
                    $profile,
                    $correlationId,
                    ['executed_steps' => $executed]
                );
            }
            $executed[] = (string) $step['step_id'];
            $context->remember((string) $step['step_id'], $lastResult);
            if (!$lastResult->success) {
                return $this->reply(
                    $lastResult->message,
                    $toolName,
                    $plannerResult->provider,
                    $profile,
                    $correlationId,
                    $lastResult->data,
                    $lastResult->warnings
                );
            }
        }

        $data = $lastResult?->data ?? [];
        if ($semanticHint !== null) {
            $data['semantic_hint'] = $semanticHint;
        }

        $toolName = (string) ($steps[array_key_last($steps)]['tool'] ?? 'ai.execution.complete');
        $replyText = $lastResult?->message ?? 'Plano concluído.';
        $provider = $plannerResult->provider;

        if ($this->narrator !== null && $profile === 'business') {
            $narrated = $this->narrator->narrate($message, $toolName, $replyText, $data);
            if ($narrated !== null) {
                $data['system_summary'] = $replyText;
                $data['narrative'] = true;
                $replyText = $narrated['text'];
                $provider = $narrated['provider'];
            }
        }

        return $this->reply(
            $replyText,
            $toolName,
            $provider,
            $profile,
            $correlationId,
            $data,
            $lastResult?->warnings ?? []
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $warnings
     * @return array<string, mixed>
     */
    private function reply(
        string $message,
        string $tool,
        string $provider,
        string $profile,
        string $correlationId,
        array $data = [],
        array $warnings = []
    ): array {
        $data['ai_context'] = [
            'effective_mode' => $profile,
            'correlation_id' => $correlationId,
            'warnings' => $warnings,
        ];

        return [
            'resposta' => $message,
            'tool' => $tool,
            'data' => $data,
            'provider' => $provider,
            'mode' => $profile,
        ];
    }

    private function profileLabel(string $profile): string
    {
        return $profile === 'technical' ? 'Especialista Técnico SAP' : 'Analista de Negócios';
    }
}
