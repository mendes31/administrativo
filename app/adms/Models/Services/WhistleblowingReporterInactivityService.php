<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Regra e apresentação do prazo de retorno do denunciante.
 */
final class WhistleblowingReporterInactivityService
{
    public function computeDeadline(?string $fromDatetime = null): ?string
    {
        $config = new WhistleblowingConfigRepository();
        if (!$config->isReporterInactivityEnabled()) {
            return null;
        }

        $base = $fromDatetime !== null && $fromDatetime !== '' ? strtotime($fromDatetime) : time();
        if ($base === false) {
            $base = time();
        }

        return date('Y-m-d H:i:s', $base + ($config->getReporterInactivityDays() * 86400));
    }

    /**
     * @param array<string, mixed> $report
     * @return array{active: bool, overdue: bool, deadline: string, label: string, color: string}
     */
    public function status(array $report, ?int $now = null): array
    {
        $deadline = strtotime((string) ($report['reporter_response_deadline'] ?? ''));
        $active = ($report['status'] ?? '') !== 'Encerrada' && $deadline !== false;
        if (!$active) {
            return [
                'active' => false,
                'overdue' => false,
                'deadline' => '',
                'label' => 'Sem retorno pendente',
                'color' => 'secondary',
            ];
        }

        $overdue = $deadline < ($now ?? time());

        return [
            'active' => true,
            'overdue' => $overdue,
            'deadline' => date('d/m/Y H:i', $deadline),
            'label' => $overdue ? 'Retorno vencido' : 'Aguardando denunciante',
            'color' => $overdue ? 'danger' : 'warning',
        ];
    }
}
