<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\Imports\ImportProfileCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImportProfileCatalog::class)]
final class ImportProfileCatalogTest extends TestCase
{
    public function testKnownProfilesAndPermissions(): void
    {
        $all = ImportProfileCatalog::all();
        self::assertArrayHasKey('users', $all);
        self::assertArrayHasKey('departments', $all);
        self::assertArrayHasKey('positions', $all);

        self::assertSame('ImportCenterUsers', $all['users']->permission());
        self::assertSame('ImportCenterDepartments', $all['departments']->permission());
        self::assertSame('ImportCenterPositions', $all['positions']->permission());

        self::assertContains('cpf', $all['users']->keyFields());
        self::assertSame('cpf', $all['users']->defaultKeyField());
        self::assertArrayHasKey('department', $all['users']->fields());
        self::assertArrayHasKey('position', $all['users']->fields());
        self::assertArrayHasKey('name', $all['departments']->fields());
        self::assertArrayHasKey('name', $all['positions']->fields());
    }

    public function testSstProfilesShareImportCenterSstPermission(): void
    {
        $all = ImportProfileCatalog::all();
        $sstKeys = [
            'sst_riscos',
            'sst_epis',
            'sst_exames',
            'sst_treinamentos',
            'sst_cids',
            'sst_medicos',
            'sst_ghe',
            'sst_riscos_cargo',
            'sst_risco_epi',
            'sst_risco_exame',
            'sst_risco_treinamento',
            'sst_epi_necessidade',
            'sst_exame_necessidade',
            'sst_treinamento_necessidade',
        ];
        foreach ($sstKeys as $key) {
            self::assertArrayHasKey($key, $all, $key);
            self::assertSame('ImportCenterSst', $all[$key]->permission(), $key);
            self::assertNotSame([], $all[$key]->fields(), $key);
        }
        self::assertNull(ImportProfileCatalog::get('sst-epi'));
    }

    public function testSstSampleRowsMatchDeclaredFields(): void
    {
        foreach (ImportProfileCatalog::all() as $profile) {
            if (!method_exists($profile, 'sampleRow')) {
                continue;
            }
            $sample = $profile->sampleRow();
            self::assertCount(count($profile->fields()), $sample, $profile->key());
        }
    }

    public function testUnknownKeyReturnsNull(): void
    {
        self::assertNull(ImportProfileCatalog::get('sst-epi'));
        self::assertNull(ImportProfileCatalog::get(''));
    }
}
