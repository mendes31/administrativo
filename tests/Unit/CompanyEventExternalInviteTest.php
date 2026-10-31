<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\CompanyEventExternalInviteAccess;
use App\adms\Models\Services\CompanyEventExternalInviteMessage;
use PHPUnit\Framework\TestCase;

final class CompanyEventExternalInviteTest extends TestCase
{
    public function testNormalizePhoneAddsBrazilCode(): void
    {
        self::assertSame('5511999998888', CompanyEventExternalInviteMessage::normalizePhone('(11) 99999-8888'));
        self::assertSame('551133334444', CompanyEventExternalInviteMessage::normalizePhone('11 3333-4444'));
        self::assertNull(CompanyEventExternalInviteMessage::normalizePhone('123'));
        self::assertNull(CompanyEventExternalInviteMessage::normalizePhone(''));
    }

    public function testWhatsAppCarriesTextAndOpenLink(): void
    {
        $event = [
            'title' => 'Festa de Final de Ano',
            'location' => 'Salão',
            'starts_at' => '2026-12-20 19:00:00',
            'ends_at' => '2026-12-20 23:00:00',
        ];
        $url = 'http://192.168.3.77/administrativo/convite-evento/abc';
        $text = CompanyEventExternalInviteMessage::message('Ana', 'Fornecedora', $event, $url);
        $wa = CompanyEventExternalInviteMessage::whatsAppUrl('11988887777', $text);

        self::assertStringContainsString('Ana', $text);
        self::assertStringContainsString('Fornecedora', $text);
        self::assertStringContainsString($url, $text);
        self::assertStringContainsString('Festa de Final de Ano', $text);
        self::assertNotNull($wa);
        self::assertStringStartsWith('https://wa.me/5511988887777?text=', (string) $wa);
    }

    public function testEventStaysOpenUntilEnd(): void
    {
        $event = ['ends_at' => '2026-12-20 23:00:00'];
        self::assertTrue(CompanyEventExternalInviteMessage::eventIsOpen($event, strtotime('2026-12-20 22:00:00')));
        self::assertFalse(CompanyEventExternalInviteMessage::eventIsOpen($event, strtotime('2026-12-21 00:00:00')));
    }

    public function testCreatorOrCreateAndEditCanManageExternalInvites(): void
    {
        $event = ['created_by' => 9];
        self::assertTrue(CompanyEventExternalInviteAccess::canManage($event, 9, []));
        self::assertTrue(CompanyEventExternalInviteAccess::canManage(
            ['created_by' => 1],
            4,
            ['CreateCompanyEvent', 'UpdateCompanyEvent']
        ));
        self::assertFalse(CompanyEventExternalInviteAccess::canManage(
            ['created_by' => 1],
            4,
            ['CreateCompanyEvent']
        ));
        self::assertFalse(CompanyEventExternalInviteAccess::canManage(
            ['created_by' => 1],
            4,
            ['UpdateCompanyEvent']
        ));
        self::assertFalse(CompanyEventExternalInviteAccess::canManage($event, 0, ['CreateCompanyEvent', 'UpdateCompanyEvent']));
    }
}
