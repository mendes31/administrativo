<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhEntrevistaEmailTemplateCatalog;
use PHPUnit\Framework\TestCase;

final class RhEntrevistaEmailTemplateCatalogTest extends TestCase
{
    public function testRendersAgendadaTemplate(): void
    {
        $tpl = RhEntrevistaEmailTemplateCatalog::render(
            RhEntrevistaEmailTemplateCatalog::KEY_AGENDADA,
            [
                'candidato_nome' => 'Ana',
                'vaga_titulo' => 'Analista',
                'data_hora' => '2026-07-20 10:00:00',
                'local' => 'Sala 1',
                'tipo' => 'presencial',
            ]
        );

        self::assertSame(RhEntrevistaEmailTemplateCatalog::KEY_AGENDADA, $tpl['key']);
        self::assertSame(1, $tpl['version']);
        self::assertStringContainsString('agendada', mb_strtolower($tpl['subject']));
        self::assertStringContainsString('Ana', $tpl['body_text']);
        self::assertStringContainsString('não é enviada', $tpl['body_text']);
    }

    public function testRendersReagendadaTemplate(): void
    {
        $tpl = RhEntrevistaEmailTemplateCatalog::render(
            RhEntrevistaEmailTemplateCatalog::KEY_REAGENDADA,
            [
                'candidato_nome' => 'Bruno',
                'data_hora' => '2026-07-21 15:00:00',
                'data_hora_anterior' => '2026-07-20 10:00:00',
            ]
        );

        self::assertSame(RhEntrevistaEmailTemplateCatalog::KEY_REAGENDADA, $tpl['key']);
        self::assertStringContainsString('reagendamento', mb_strtolower($tpl['subject']));
        self::assertStringContainsString('2026-07-20 10:00:00', $tpl['body_text']);
        self::assertStringContainsString('reagendada', mb_strtolower($tpl['body_text']));
    }
}
