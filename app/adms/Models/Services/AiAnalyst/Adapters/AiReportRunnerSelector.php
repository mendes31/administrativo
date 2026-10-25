<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Adapters;

use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\InternalChat\ChatDynamicReportService;

/**
 * Escolhe a série simulada enquanto os relatórios reais estiverem desligados.
 */
final class AiReportRunnerSelector
{
    public function __construct(private readonly ?\Closure $liveRunner = null)
    {
    }

    public function select(AiAnalystFeatureFlags $flags): ApprovedReportRunnerInterface
    {
        if (!$flags->realReportsEnabled()) {
            return new FakeReportRunner();
        }

        $runner = $this->liveRunner !== null
            ? ($this->liveRunner)()
            : new ApprovedReportAdapter(new ChatDynamicReportService());

        if (!$runner instanceof ApprovedReportRunnerInterface) {
            throw new \LogicException('O runner de relatórios reais é inválido.');
        }

        return $runner;
    }
}
