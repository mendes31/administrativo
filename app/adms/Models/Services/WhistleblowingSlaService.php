<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingCategoriesRepository;
use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Cálculo de prazos SLA para primeira resposta do comitê.
 */
final class WhistleblowingSlaService
{
    public function resolveFirstResponseHours(string $category, string $riskLevel): int
    {
        $config = new WhistleblowingConfigRepository();
        $categoryHours = $this->categoryHours($category);
        if ($categoryHours !== null) {
            return $categoryHours;
        }

        $riskHours = $this->riskHours($riskLevel, $config);
        if ($riskHours !== null) {
            return $riskHours;
        }

        return max(1, $config->getSlaFirstResponseHours());
    }

    public function computeDeadline(string $category, string $riskLevel, ?string $fromDatetime = null): string
    {
        $hours = $this->resolveFirstResponseHours($category, $riskLevel);
        $base = $fromDatetime !== null && $fromDatetime !== ''
            ? strtotime($fromDatetime)
            : time();
        if ($base === false) {
            $base = time();
        }

        return date('Y-m-d H:i:s', $base + ($hours * 3600));
    }

    public function defaultSlaLabel(): string
    {
        $hours = (new WhistleblowingConfigRepository())->getSlaFirstResponseHours();

        return $this->formatHoursLabel($hours);
    }

    public function formatHoursLabel(int $hours): string
    {
        if ($hours % 24 === 0 && $hours >= 24) {
            $days = (int) round($hours / 24);

            return $days . ($days === 1 ? ' dia' : ' dias');
        }

        return $hours . 'h';
    }

    /**
     * Dados de apresentação do consumo do SLA de primeira resposta.
     *
     * @param array<string, mixed> $report
     * @return array{available: bool, deadline: string, percent: int, color: string, label: string, title: string}
     */
    public function progress(array $report, ?int $now = null): array
    {
        $createdAt = strtotime((string) ($report['created_at'] ?? ''));
        $deadline = strtotime((string) ($report['sla_response_deadline'] ?? ''));
        if ($createdAt === false || $deadline === false || $deadline <= $createdAt) {
            return [
                'available' => false,
                'deadline' => '',
                'percent' => 0,
                'color' => 'secondary',
                'label' => 'SLA indisponível',
                'title' => 'Prazo de primeira resposta não definido',
            ];
        }

        $firstResponseAt = strtotime((string) ($report['first_response_at'] ?? ''));
        $completed = $firstResponseAt !== false;
        $reference = $completed ? $firstResponseAt : ($now ?? time());
        $elapsed = max(0, $reference - $createdAt);
        $duration = max(1, $deadline - $createdAt);
        $percent = min(100, (int) round(($elapsed / $duration) * 100));
        $overdue = $reference > $deadline;

        if ($overdue) {
            $color = 'danger';
            $label = $completed ? 'Respondida fora do prazo' : 'SLA vencido';
        } elseif ($completed) {
            $color = 'success';
            $label = 'Respondida no prazo';
        } elseif ($percent >= 80) {
            $color = 'warning';
            $label = 'Próximo do vencimento';
        } else {
            $color = 'success';
            $label = 'Dentro do prazo';
        }

        return [
            'available' => true,
            'deadline' => date('d/m/Y H:i', $deadline),
            'percent' => $percent,
            'color' => $color,
            'label' => $label,
            'title' => $label . ' — prazo: ' . date('d/m/Y H:i', $deadline),
        ];
    }

    private function categoryHours(string $category): ?int
    {
        try {
            $repo = new WhistleblowingCategoriesRepository();
            $rows = $repo->getAll();
            foreach ($rows as $row) {
                if (strcasecmp((string) ($row['name'] ?? ''), trim($category)) !== 0) {
                    continue;
                }
                $hours = $row['sla_first_response_hours'] ?? null;
                if ($hours === null || $hours === '') {
                    return null;
                }
                $int = (int) $hours;

                return $int > 0 ? min(720, $int) : null;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function riskHours(string $riskLevel, WhistleblowingConfigRepository $config): ?int
    {
        $map = [
            'Crítico' => $config->getSlaHoursCritico(),
            'Alto' => $config->getSlaHoursAlto(),
            'Médio' => $config->getSlaHoursMedio(),
            'Baixo' => $config->getSlaHoursBaixo(),
        ];
        $hours = $map[trim($riskLevel)] ?? null;

        return $hours !== null && $hours > 0 ? min(720, (int) $hours) : null;
    }
}
