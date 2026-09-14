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

    public function testValidateImportSourceUrlEArquivo(): void
    {
        self::assertNull(SstEpiImagemHelper::validateImportSource('https://cdn.exemplo.com/epi.jpg', ''));
        self::assertNotNull(SstEpiImagemHelper::validateImportSource('file:///etc/passwd', ''));
        self::assertNotNull(SstEpiImagemHelper::validateImportSource('http://127.0.0.1/x.jpg', ''));
        self::assertNotNull(SstEpiImagemHelper::validateImportSource('avental.jpg', ''));

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'epi_img_test_' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        $png = $dir . DIRECTORY_SEPARATOR . 'avental.png';
        $blob = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==');
        self::assertNotFalse($blob);
        file_put_contents($png, $blob);
        self::assertNull(SstEpiImagemHelper::validateImportSource('avental.png', $dir));
        @unlink($png);
        @rmdir($dir);
    }

    public function testFotoCasaComNomeDaChaveMesmoComEspacoAcentoEUnderscore(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'epi_img_key_' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        $blob = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==');
        self::assertNotFalse($blob);

        $extracted = $dir . DIRECTORY_SEPARATOR . 'calcadodeseguranca.png';
        file_put_contents($extracted, $blob);

        self::assertSame(
            SstEpiImagemHelper::assetStemKey('Calçado de Segurança'),
            SstEpiImagemHelper::assetStemKey('Calcado de Seguranca.png')
        );
        self::assertSame(
            'aventalraspadecouro.png',
            SstEpiImagemHelper::assetMatchKey('Avental Raspa de Couro.png')
        );
        self::assertSame(
            SstEpiImagemHelper::assetMatchKey('Avental Raspa de Couro.png'),
            SstEpiImagemHelper::assetMatchKey('Avental_Raspa_de_Couro.png')
        );

        self::assertNotNull(SstEpiImagemHelper::findAsset($dir, 'Calçado de Segurança.png'));
        self::assertNotNull(SstEpiImagemHelper::findAssetByEpiName($dir, 'Calçado de Segurança'));
        self::assertNotNull(SstEpiImagemHelper::resolveZipAsset($dir, 'outro.png', 'Calçado de Segurança'));
        self::assertNull(SstEpiImagemHelper::findAssetByEpiName($dir, 'Avental em Raspa de Couro'));
        self::assertNull(SstEpiImagemHelper::validateImportSource('Calçado de Segurança.png', $dir, 'Calçado de Segurança'));

        @unlink($extracted);
        @rmdir($dir);
    }
}
