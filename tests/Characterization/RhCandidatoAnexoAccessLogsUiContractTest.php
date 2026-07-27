<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class RhCandidatoAnexoAccessLogsUiContractTest extends TestCase
{
    public function testPagesMigrationAndControllers(): void
    {
        $m = $this->read('database/migrations/20260720150000_register_rh_candidato_anexo_access_logs_pages.php');
        self::assertStringContainsString('ListRhCandidatoAnexoAccessLogs', $m);
        self::assertStringContainsString('ExportRhCandidatoAnexoAccessLogsExcel', $m);
        self::assertStringContainsString('ListLogAcessos', $m);

        $pdfMig = $this->read(
            'database/migrations/20260727150000_register_rh_candidato_anexo_access_logs_pdf_export.php'
        );
        self::assertStringContainsString('ExportRhCandidatoAnexoAccessLogsPdf', $pdfMig);

        self::assertFileExists(PROJECT_ROOT . '/app/adms/Controllers/rh/ListRhCandidatoAnexoAccessLogs.php');
        self::assertFileExists(PROJECT_ROOT . '/app/adms/Controllers/rh/ExportRhCandidatoAnexoAccessLogsExcel.php');
        self::assertFileExists(PROJECT_ROOT . '/app/adms/Controllers/rh/ExportRhCandidatoAnexoAccessLogsPdf.php');
        self::assertFileExists(PROJECT_ROOT . '/app/adms/Views/rh/candidatos/list_anexo_access_logs.php');
    }

    public function testRepositoryHasListMethods(): void
    {
        $repo = $this->read('app/adms/Models/Repository/RhCandidatoAnexoAccessLogRepository.php');
        self::assertStringContainsString('function getAll', $repo);
        self::assertStringContainsString('function countAll', $repo);
        self::assertStringContainsString('buildListFilters', $repo);
    }

    public function testManualMapped(): void
    {
        $map = $this->read('docs/manual/page-topic-map.json');
        self::assertStringContainsString('"list-rh-candidato-anexo-access-logs"', $map);
        self::assertFileExists(
            PROJECT_ROOT . '/docs/manual/content/gestao_pessoas/list-rh-candidato-anexo-access-logs.html'
        );
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source);

        return $source;
    }
}
