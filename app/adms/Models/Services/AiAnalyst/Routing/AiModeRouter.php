<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Routing;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Auth\AiConversationContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;

/**
 * Resolve o modo efetivo exclusivamente no servidor.
 * O valor recebido do cliente e uma solicitacao, nunca uma autorizacao.
 */
final class AiModeRouter
{
    private readonly AiAutomaticModeClassifier $automaticClassifier;

    public function __construct(
        private readonly AiAnalystFeatureFlags $flags,
        ?AiAutomaticModeClassifier $automaticClassifier = null
    ) {
        $this->automaticClassifier = $automaticClassifier ?? new AiAutomaticModeClassifier();
    }

    public function decide(
        ?string $requestedMode,
        AiActorContext $actor,
        string $message = '',
        ?AiConversationContext $conversation = null
    ): AiModeDecision {
        $requested = strtolower(trim((string) $requestedMode));
        if ($requested === '' || $requested === 'legacy') {
            return AiModeDecision::allow('legacy', 'Fluxo atual do Tiarajuzinho.');
        }

        if (!in_array($requested, ['auto', 'business', 'technical'], true)) {
            return AiModeDecision::deny('Modo solicitado nao reconhecido.');
        }

        if ($actor->userId < 1) {
            return AiModeDecision::deny('Sessao invalida para os novos modos do Tiarajuzinho.');
        }

        if (!$this->flags->enabled()) {
            return AiModeDecision::deny('Os novos modos do Tiarajuzinho estao desativados.');
        }

        if ($requested === 'auto') {
            $effectiveMode = $this->automaticClassifier->classify(
                $message,
                $actor,
                $this->flags,
                $conversation
            );

            return AiModeDecision::allow(
                $effectiveMode,
                $effectiveMode === 'legacy'
                    ? 'Pergunta mantida no fluxo atual do Tiarajuzinho.'
                    : 'Capacidade analitica identificada automaticamente.'
            );
        }

        if ($requested === 'business') {
            return $this->flags->businessEnabled()
                ? AiModeDecision::allow('business')
                : AiModeDecision::deny('O modo Analista de Negocios esta desativado.');
        }

        if (!$this->flags->technicalEnabled()) {
            return AiModeDecision::deny('O modo Especialista Tecnico SAP esta desativado.');
        }

        if (!$actor->fullSystemAccess) {
            return AiModeDecision::deny('O modo Especialista Tecnico SAP exige acesso total ao sistema.');
        }

        return AiModeDecision::allow('technical');
    }
}
