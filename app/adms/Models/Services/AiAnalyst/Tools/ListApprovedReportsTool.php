<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Adapters\ApprovedReportRunnerInterface;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

final class ListApprovedReportsTool implements AiToolInterface
{
    public function __construct(private readonly ApprovedReportRunnerInterface $reports)
    {
    }

    public function definition(): AiToolDefinition
    {
        return new AiToolDefinition(
            'report.list_approved',
            'Lista os relatórios homologados e autorizados para o usuário.',
            ['business']
        );
    }

    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
    {
        if (!in_array($profile, $this->definition()->profiles, true)) {
            return PolicyDecision::deny('Perfil sem permissão para esta tool.');
        }
        if ($actor->userId < 1 || !$actor->canUseLegacyTool('report.list')) {
            return PolicyDecision::deny('Usuário sem permissão para listar relatórios do chat.');
        }

        return PolicyDecision::allow();
    }

    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
    {
        $catalog = $this->reports->listForUser($context->actor->userId);

        return new AiToolResult(
            true,
            $catalog === []
                ? 'Nenhum relatório homologado está disponível para este usuário.'
                : 'Relatórios homologados disponíveis: ' . count($catalog) . '.',
            ['catalog' => $catalog]
        );
    }
}
