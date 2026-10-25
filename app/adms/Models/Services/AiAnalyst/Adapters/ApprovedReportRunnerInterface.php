<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Adapters;

/**
 * Fronteira única dos relatórios usados pelo modo analítico.
 * Mês e ano são os únicos filtros aceitos nesta etapa.
 */
interface ApprovedReportRunnerInterface
{
    /** Verdadeiro somente quando a execução consulta relatórios reais do Portal/SAP. */
    public function usesLiveReports(): bool;

    /** @return list<array<string, mixed>> */
    public function listForUser(int $userId): array;

    /** @return array<string, mixed> */
    public function run(int $userId, string $binding, ?int $month = null, ?int $year = null): array;
}
