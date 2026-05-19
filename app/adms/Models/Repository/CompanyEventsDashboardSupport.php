<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

/**
 * Métodos do repositório de eventos usados pelo dashboard (contrato para análise estática).
 */
interface CompanyEventsDashboardSupport
{
    /**
     * @param list<array<string, mixed>> $events
     * @return array<int, array<string, mixed>|null>
     */
    public function buildDashboardRsvpMapForUser(array $events, int $userId): array;
}
