<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Contrato pós-correção Fase 0 completa (currículos ATS).
 * Ver docs/09_DOMINIOS/Gestao_Pessoas/SEG_CURRICULOS_DIAGNOSTICO.md.
 */
#[CoversNothing]
final class CurriculoStorageContractTest extends TestCase
{
    public function testCurriculoUploadUsesSharedAnexoService(): void
    {
        $create = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosCreate.php'
        );
        $edit = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosEdit.php'
        );

        self::assertStringContainsString('RhCandidatoAnexoService::storeCurriculo', $create);
        self::assertStringContainsString('RhCandidatoAnexoService::storeCurriculo', $edit);

        $public = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaPublicaService.php'
        );
        self::assertStringContainsString('RhCandidatoAnexoService::storeCurriculo', $public);
    }

    public function testAnexoServiceStoresInPrivateStorageWithStrongValidation(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidatoAnexoService.php'
        );

        self::assertStringContainsString('privateBaseDir', $source);
        self::assertStringContainsString('storage', $source);
        self::assertStringContainsString('private', $source);
        self::assertStringContainsString('is_uploaded_file', $source);
        self::assertStringContainsString('detectMimeType', $source);
        self::assertStringContainsString('bin2hex(random_bytes(16))', $source);
        self::assertStringContainsString('legacyPublicBaseDir', $source);
    }

    public function testCreateRequiresLgpdConsentCheckboxAndTerm(): void
    {
        $controller = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosCreate.php'
        );
        $view = $this->readProjectFile(
            'app/adms/Views/rh/candidatos/create.php'
        );

        self::assertStringContainsString('curriculo_candidato', $controller);
        self::assertStringContainsString("lgpd_consent'] !== '1'", $controller);
        self::assertStringContainsString('registrarConsentimento', $controller);
        self::assertStringContainsString('name="lgpd_consent"', $view);
    }

    public function testDownloadUsesAuthorizedControllerAndObjectPermission(): void
    {
        $download = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosDownloadAnexo.php'
        );
        $permission = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidatoPermissionService.php'
        );
        $fileServer = $this->readProjectFile(
            'app/adms/Controllers/Services/FileServer.php'
        );
        $view = $this->readProjectFile(
            'app/adms/Views/rh/candidatos/view.php'
        );

        self::assertStringContainsString('canDownloadAnexo', $download);
        self::assertStringContainsString('RhCandidatoAnexoService::resolvePhysicalPath', $download);
        self::assertStringContainsString('RhCandidatoAnexoAccessLogRepository', $download);
        self::assertStringContainsString('logDownload', $download);
        self::assertStringContainsString('canAccessCandidato', $permission);
        self::assertStringContainsString('canDownloadAnexo', $fileServer);
        self::assertStringContainsString('RhCandidatoAnexoAccessLogRepository', $fileServer);
        self::assertStringContainsString('rh-candidatos-download-anexo/', $view);
    }

    public function testAccessLogMigrationAndRepositoryExist(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719231000_create_rh_candidato_anexo_access_logs.php'
        );
        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhCandidatoAnexoAccessLogRepository.php'
        );

        self::assertStringContainsString('rh_candidato_anexo_access_logs', $migration);
        self::assertStringContainsString('path_hash', $migration);
        self::assertStringContainsString('function logDownload', $repo);
        self::assertStringContainsString('GenerateLog::generateLog', $repo);
        self::assertStringContainsString('hasRecentEntry', $repo);
    }

    public function testRetentionAnonymizationUsesAnexoServicePhysicalPath(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhCandidatosRepository.php'
        );

        self::assertStringContainsString('deleteAnexosByCandidatoId', $source);
        self::assertStringContainsString('RhCandidatoAnexoService::deletePhysicalFile', $source);
        self::assertStringContainsString('area_interesse = NULL', $source);
        self::assertStringNotContainsString(
            "\$path = \$projectRoot . DIRECTORY_SEPARATOR . ltrim(\$anexo['arquivo_caminho']",
            $source
        );
    }

    public function testManualDeleteRequiresPostAndRemovesAnexos(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosDelete.php'
        );

        self::assertStringContainsString("REQUEST_METHOD'] !== 'POST'", $source);
        self::assertStringContainsString('deleteAnexosByCandidatoId', $source);
        self::assertStringContainsString('RhCandidatoPermissionService::canEditCandidato', $source);
        self::assertStringNotContainsString('Fallback simples (GET)', $source);
    }

    public function testEditFormAcceptsMultipartUpload(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Views/rh/candidatos/edit.php'
        );

        self::assertStringContainsString('enctype="multipart/form-data"', $source);
        self::assertStringContainsString('rh-candidatos-download-anexo/', $source);
    }

    public function testMigrationAddsFkAndLgpdTerm(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719130000_secure_rh_candidatos_anexos_and_lgpd.php'
        );

        self::assertStringContainsString('fk_rh_candidatos_anexos_candidato', $source);
        self::assertStringContainsString('CASCADE', $source);
        self::assertStringContainsString('curriculo_candidato', $source);
        self::assertStringContainsString('RhCandidatosDownloadAnexo', $source);
    }

    public function testPrivateStorageGitkeepExists(): void
    {
        self::assertFileExists(
            PROJECT_ROOT . '/storage/private/rh_candidatos/.gitkeep'
        );
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);

        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
