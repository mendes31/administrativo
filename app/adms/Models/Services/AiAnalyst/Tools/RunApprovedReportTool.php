<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Adapters\ApprovedReportRunnerInterface;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

final class RunApprovedReportTool implements AiToolInterface
{
    public function __construct(private readonly ApprovedReportRunnerInterface $reports)
    {
    }

    public function definition(): AiToolDefinition
    {
        return new AiToolDefinition(
            'report.run_approved',
            'Executa um relatório homologado por binding funcional; nunca recebe SQL. '
            . 'Aceita month+year, só year (série anual) ou sem período.',
            ['business'],
            ['report_binding' => 'string'],
            ['month' => 'integer', 'year' => 'integer']
        );
    }

    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
    {
        if (!in_array($profile, $this->definition()->profiles, true)) {
            return PolicyDecision::deny('Perfil sem permissão para esta tool.');
        }
        if ($actor->userId < 1 || !$actor->canUseLegacyTool('report.run')) {
            return PolicyDecision::deny('Usuário sem permissão para executar relatórios do chat.');
        }

        // Relatório real continua fail-closed até o servidor resolver a filial.
        // A série simulada não carrega dado de filial e pode ser executada sem esse escopo.
        if ($this->reports->usesLiveReports()) {
            $scopeMode = strtolower(trim((string) ($actor->dataScopes['scope_mode'] ?? 'none')));
            if ($scopeMode !== 'all') {
                return PolicyDecision::deny(
                    'Relatórios reais exigem acesso total nesta fase (mapeamento de filial Portal→SAP ainda não homologado). '
                    . 'Com usuário de acesso total, o ACL do relatório continua valendo.'
                );
            }
        }

        $binding = trim((string) ($arguments['report_binding'] ?? ''));
        if ($binding === '' || mb_strlen($binding) > 160 || preg_match('/^#?\d+$/', $binding)) {
            return PolicyDecision::deny('Binding de relatório inválido.');
        }

        $hasMonth = array_key_exists('month', $arguments) && $arguments['month'] !== null && $arguments['month'] !== '';
        $hasYear = array_key_exists('year', $arguments) && $arguments['year'] !== null && $arguments['year'] !== '';
        if ($hasMonth && !$hasYear) {
            return PolicyDecision::deny('Ao informar mês, o ano também é obrigatório.');
        }
        if ($hasMonth) {
            $month = (int) $arguments['month'];
            $year = (int) $arguments['year'];
            if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
                return PolicyDecision::deny('Período inválido.');
            }
        } elseif ($hasYear) {
            $year = (int) $arguments['year'];
            if ($year < 2000 || $year > 2100) {
                return PolicyDecision::deny('Ano inválido.');
            }
        }

        return PolicyDecision::allowWithRedaction('Resultado sujeito à sanitização do adapter.');
    }

    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
    {
        $month = array_key_exists('month', $arguments) && $arguments['month'] !== null && $arguments['month'] !== ''
            ? (int) $arguments['month']
            : null;
        $year = array_key_exists('year', $arguments) && $arguments['year'] !== null && $arguments['year'] !== ''
            ? (int) $arguments['year']
            : null;

        $result = $this->reports->run(
            $context->actor->userId,
            (string) $arguments['report_binding'],
            $month,
            $year
        );

        return new AiToolResult(
            !empty($result['ok']),
            (string) ($result['resposta'] ?? 'Relatório processado.'),
            is_array($result['data'] ?? null) ? $result['data'] : []
        );
    }
}
