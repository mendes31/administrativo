<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;
use App\adms\Models\Services\RhEntrevistaComunicacaoPreflightService;
use PHPUnit\Framework\TestCase;

final class RhEntrevistaComunicacaoPreflightServiceTest extends TestCase
{
    public function testEvaluateReadyWhenComplete(): void
    {
        $service = new RhEntrevistaComunicacaoPreflightService();
        $result = $service->evaluate([
            'subject_snapshot' => 'Entrevista agendada',
            'has_body_html' => 1,
            'template_version' => 2,
            'recipient_address' => 'ana@example.com',
            'outbox_event_id' => 10,
            'event_name' => 'EntrevistaAgendada',
            'outbox_status' => 'pending',
            'purpose' => RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
        ]);

        self::assertSame(RhEntrevistaComunicacoesRepository::STATUS_READY, $result['decision']);
    }

    public function testEvaluateBlockedWhenEmailMissing(): void
    {
        $service = new RhEntrevistaComunicacaoPreflightService();
        $result = $service->evaluate([
            'subject_snapshot' => 'Entrevista agendada',
            'has_body_html' => 1,
            'template_version' => 2,
            'recipient_address' => '',
            'outbox_event_id' => 10,
            'event_name' => 'EntrevistaAgendada',
            'outbox_status' => 'pending',
            'purpose' => RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
        ]);

        self::assertSame(RhEntrevistaComunicacoesRepository::STATUS_BLOCKED, $result['decision']);
        self::assertContains('recipient_address ausente', $result['reasons']);
    }

    public function testEvaluateBlockedWhenOutboxNotPending(): void
    {
        $service = new RhEntrevistaComunicacaoPreflightService();
        $result = $service->evaluate([
            'subject_snapshot' => 'Entrevista agendada',
            'has_body_html' => 1,
            'template_version' => 2,
            'recipient_address' => 'ana@example.com',
            'outbox_event_id' => 10,
            'event_name' => 'EntrevistaAgendada',
            'outbox_status' => 'published',
            'purpose' => RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
        ]);

        self::assertSame(RhEntrevistaComunicacoesRepository::STATUS_BLOCKED, $result['decision']);
        self::assertContains('outbox_status diferente de pending', $result['reasons']);
    }
}
