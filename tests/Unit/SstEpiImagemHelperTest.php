<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstEpiImagemHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEpiImagemHelper::class)]
final class SstEpiImagemHelperTest extends TestCase
{
    public function testSanitizeAceitaSomentePastaDoEpi(): void
    {
        self::assertSame('sst/epis/foto.jpg', SstEpiImagemHelper::sanitizeStored('sst/epis/foto.jpg'));
        self::assertNull(SstEpiImagemHelper::sanitizeStored(''));
        self::assertNull(SstEpiImagemHelper::sanitizeStored('users/1/foto.png'));
        self::assertNull(SstEpiImagemHelper::sanitizeStored('sst/epis/../secret.png'));
    }

    public function testFromRequestSemArquivoMantemAtual(): void
    {
        $r = SstEpiImagemHelper::fromRequest(null, 'sst/epis/atual.jpg', false);
        self::assertTrue($r['ok']);
        self::assertFalse($r['changed']);
        self::assertSame('sst/epis/atual.jpg', $r['path']);
    }

    public function testFromRequestRemoveLimpaCaminho(): void
    {
        $r = SstEpiImagemHelper::fromRequest(null, 'sst/epis/atual.jpg', true);
        self::assertTrue($r['ok']);
        self::assertTrue($r['changed']);
        self::assertNull($r['path']);
    }

    public function testFromRequestErroDeUpload(): void
    {
        $r = SstEpiImagemHelper::fromRequest([
            'name' => 'x.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ], 'sst/epis/atual.jpg', false);
        self::assertFalse($r['ok']);
        self::assertFalse($r['changed']);
        self::assertSame('sst/epis/atual.jpg', $r['path']);
        self::assertNotEmpty($r['error']);
    }
}
