<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhCandidatoAnexoService;
use App\adms\Models\Services\RhCandidatoPermissionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RhCandidatoAnexoService::class)]
final class RhCandidatoAnexoServiceTest extends TestCase
{
    private string $candidatePrivateDir;
    private string $candidateLegacyDir;
    private string $relativePath;
    private string $privatePath;
    private string $legacyPath;

    protected function setUp(): void
    {
        $this->relativePath = 'rh_candidatos/999001/cv_test_unit.pdf';

        $this->candidatePrivateDir = RhCandidatoAnexoService::privateBaseDir()
            . DIRECTORY_SEPARATOR . '999001';
        $this->candidateLegacyDir = RhCandidatoAnexoService::legacyPublicBaseDir()
            . DIRECTORY_SEPARATOR . '999001';

        if (!is_dir($this->candidatePrivateDir)) {
            mkdir($this->candidatePrivateDir, 0775, true);
        }
        if (!is_dir($this->candidateLegacyDir)) {
            mkdir($this->candidateLegacyDir, 0775, true);
        }

        $this->privatePath = $this->candidatePrivateDir . DIRECTORY_SEPARATOR . 'cv_test_unit.pdf';
        $this->legacyPath = $this->candidateLegacyDir . DIRECTORY_SEPARATOR . 'cv_test_unit.pdf';
        file_put_contents($this->privatePath, '%PDF-1.4 private-unit-test');
        file_put_contents($this->legacyPath, '%PDF-1.4 legacy-unit-test');
    }

    protected function tearDown(): void
    {
        foreach ([$this->privatePath, $this->legacyPath] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        foreach ([$this->candidatePrivateDir, $this->candidateLegacyDir] as $dir) {
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }
    }

    public function testResolvePhysicalPathPrefersPrivateStorage(): void
    {
        $resolved = RhCandidatoAnexoService::resolvePhysicalPath($this->relativePath);

        self::assertNotNull($resolved);
        self::assertSame(realpath($this->privatePath), $resolved);
        self::assertStringContainsString(
            'storage' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'rh_candidatos',
            $resolved
        );
    }

    public function testResolvePhysicalPathFallsBackToLegacyPublic(): void
    {
        @unlink($this->privatePath);

        $resolved = RhCandidatoAnexoService::resolvePhysicalPath($this->relativePath);

        self::assertNotNull($resolved);
        self::assertSame(realpath($this->legacyPath), $resolved);
    }

    public function testResolvePhysicalPathRejectsTraversal(): void
    {
        self::assertNull(
            RhCandidatoAnexoService::resolvePhysicalPath('rh_candidatos/../.env')
        );
    }

    public function testDeletePhysicalFileRemovesPrivateAndLegacy(): void
    {
        self::assertFileExists($this->privatePath);
        self::assertFileExists($this->legacyPath);
        self::assertTrue(RhCandidatoAnexoService::deletePhysicalFile($this->relativePath));
        self::assertFileDoesNotExist($this->privatePath);
        self::assertFileDoesNotExist($this->legacyPath);
    }

    public function testExtractCandidatoIdFromAnexoPath(): void
    {
        self::assertSame(
            12,
            RhCandidatoPermissionService::extractCandidatoIdFromAnexoPath('rh_candidatos/12/abc.pdf')
        );
        self::assertNull(
            RhCandidatoPermissionService::extractCandidatoIdFromAnexoPath('users/1/foto.jpg')
        );
    }
}
