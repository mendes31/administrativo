<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Adapters;

use App\adms\Models\Services\InternalChat\ChatDynamicReportService;

/**
 * Única fronteira SAP do núcleo AI Analyst nesta fase.
 * Recebe binding funcional e delega somente a relatório já habilitado/ACL-validado.
 */
final class ApprovedReportAdapter implements ApprovedReportRunnerInterface
{
    /** @var list<string> */
    private const SALES_ALIASES = ['sales_summary', 'vendas', 'faturamento'];

    private readonly ApprovedReportResultNormalizer $normalizer;

    public function __construct(
        private readonly ChatDynamicReportService $reports,
        ?ApprovedReportResultNormalizer $normalizer = null,
        private readonly ?string $salesBindingOverride = null
    ) {
        $this->normalizer = $normalizer ?? new ApprovedReportResultNormalizer();
    }

    public function usesLiveReports(): bool
    {
        return true;
    }

    /** @return list<array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        return array_map(static function (array $report): array {
            $binding = trim((string) ($report['chat_tool_name'] ?? ''));
            if ($binding === '') {
                $binding = (string) ($report['name'] ?? '');
            }

            return [
                'binding' => $binding,
                'name' => (string) ($report['name'] ?? ''),
                'description' => $report['chat_description'] ?? null,
                'examples' => $report['examples'] ?? [],
                'visualization_type' => (string) ($report['visualization_type'] ?? 'table'),
                'source_type' => 'approved_report',
            ];
        }, $this->reports->listCatalogForUser($userId));
    }

    /** @return array<string, mixed> */
    public function run(int $userId, string $binding, ?int $month = null, ?int $year = null): array
    {
        $requested = trim($binding);
        if ($requested === '' || preg_match('/^#?\d+$/', $requested)) {
            return ['ok' => false, 'resposta' => 'Binding de relatório inválido.'];
        }

        $functionalBinding = $this->isSalesAlias($requested)
            ? FakeReportRunner::BINDING
            : $requested;

        $preferMonth = $month !== null || $year !== null;
        $resolved = $this->resolveCatalogBinding($userId, $requested, $preferMonth);
        if ($resolved === null) {
            return [
                'ok' => false,
                'resposta' => $this->isSalesAlias($requested)
                    ? 'Nenhum relatório de vendas/faturamento está liberado no chat para o seu usuário. '
                        . 'Libere um relatório no Assistente MCP (aba Tools) ou defina AI_ANALYST_SALES_BINDING.'
                    : 'Relatório homologado não encontrado ou não autorizado.',
            ];
        }

        $report = $this->reports->resolveReport($userId, $resolved, $preferMonth);
        if ($report === null || (int) ($report['id'] ?? 0) < 1) {
            return ['ok' => false, 'resposta' => 'Relatório homologado não encontrado ou não autorizado.'];
        }

        $reportId = (int) $report['id'];
        if ($month !== null && $year !== null) {
            $result = $this->reports->runByIdFilteredByMonth($userId, $reportId, $month, $year);
        } elseif ($year !== null) {
            $result = $this->reports->runByIdFilteredByYear($userId, $reportId, $year);
        } else {
            $result = $this->reports->runById($userId, $reportId);
        }

        if (!is_array($result)) {
            return ['ok' => false, 'resposta' => 'Falha ao executar o relatório homologado.'];
        }

        $clean = $this->sanitize($result);
        if (!is_array($clean)) {
            return ['ok' => false, 'resposta' => 'Falha ao sanitizar o resultado do relatório.'];
        }

        return $this->normalizer->withMetric($clean, $functionalBinding, $month, $year);
    }

    private function resolveCatalogBinding(int $userId, string $binding, bool $preferMonth): ?string
    {
        $override = trim((string) ($this->salesBindingOverride
            ?? ($_ENV['AI_ANALYST_SALES_BINDING'] ?? $_SERVER['AI_ANALYST_SALES_BINDING'] ?? '')));

        if ($this->isSalesAlias($binding)) {
            if ($override !== '' && !preg_match('/^#?\d+$/', $override)) {
                return $override;
            }

            $report = $this->reports->resolveReport($userId, 'vendas', $preferMonth)
                ?? $this->reports->resolveReport($userId, 'faturamento', $preferMonth);
            if ($report === null) {
                return null;
            }

            $tool = trim((string) ($report['chat_tool_name'] ?? ''));
            if ($tool !== '') {
                return $tool;
            }

            $name = trim((string) ($report['name'] ?? ''));

            return $name !== '' ? $name : null;
        }

        return $binding;
    }

    private function isSalesAlias(string $binding): bool
    {
        $normalized = mb_strtolower(trim($binding));

        return in_array($normalized, self::SALES_ALIASES, true);
    }

    private function sanitize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && preg_match('/(?:^|_)(sql|raw|query|endpoint|curl_info)(?:$|_)/i', $key)) {
                continue;
            }
            if ($key === 'report_id') {
                continue;
            }
            $clean[$key] = $this->sanitize($child);
        }

        return $clean;
    }
}
