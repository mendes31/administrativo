<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Contrato de integridade do pipeline ATS (Fase 0).
 */
#[CoversNothing]
final class PipelineIntegrityContractTest extends TestCase
{
    public function testPipelineStatusUpdateUsesTransactionAndRequiresExistingLink(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhVagasRepository.php'
        );

        self::assertStringContainsString('beginTransaction()', $source);
        self::assertStringContainsString('rowCount()', $source);
        self::assertStringContainsString(
            'Candidato não está vinculado a esta vaga.',
            $source
        );
        self::assertStringContainsString('calcularStatusGeralPorVinculos', $source);
    }

    public function testVincularAndDesvincularRecalculateCandidateStatus(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhVagasRepository.php'
        );

        $vincularPos = strpos($source, 'function vincularCandidato');
        $desvincularPos = strpos($source, 'function desvincularCandidato');
        $fecharPos = strpos($source, 'function fecharVaga');
        self::assertNotFalse($vincularPos);
        self::assertNotFalse($desvincularPos);
        self::assertNotFalse($fecharPos);

        $vincularBlock = substr($source, $vincularPos, $desvincularPos - $vincularPos);
        $desvincularBlock = substr($source, $desvincularPos, $fecharPos - $desvincularPos);
        self::assertStringContainsString('calcularStatusGeralPorVinculos', $vincularBlock);
        self::assertStringContainsString('calcularStatusGeralPorVinculos', $desvincularBlock);
        self::assertStringContainsString('beginTransaction()', $vincularBlock);
        self::assertStringContainsString('beginTransaction()', $desvincularBlock);
    }

    public function testVagaEditPostRequiresCsrfAndObjectPermission(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/rh/RhVagasEdit.php'
        );

        self::assertStringContainsString("validateCSRFToken('form_edit_rh_vaga'", $source);
        self::assertStringContainsString('canEditVagaById', $source);
    }

    public function testEntrevistasMutationsRequireObjectPermission(): void
    {
        $create = $this->readProjectFile(
            'app/adms/Controllers/rh/RhEntrevistasCreate.php'
        );
        $edit = $this->readProjectFile(
            'app/adms/Controllers/rh/RhEntrevistasEdit.php'
        );
        $delete = $this->readProjectFile(
            'app/adms/Controllers/rh/RhEntrevistasDelete.php'
        );
        $permission = $this->readProjectFile(
            'app/adms/Models/Services/RhPermissionService.php'
        );

        self::assertStringContainsString('canManageEntrevista', $create);
        self::assertStringContainsString('canManageEntrevista', $edit);
        self::assertStringContainsString('canManageEntrevista', $delete);
        self::assertStringContainsString("validateCSRFToken('form_delete_rh_entrevista'", $delete);
        self::assertStringContainsString('function canManageEntrevista', $permission);
    }

    public function testCandidatoViewLinkModalSendsCsrf(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Views/rh/candidatos/view.php'
        );

        self::assertStringContainsString(
            "generateCSRFToken('form_rh_vincular_candidato_vaga')",
            $source
        );
        self::assertStringContainsString('name="csrf_token"', $source);
    }

    public function testMassLinkSyncIsTransactional(): void
    {
        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhVagasRepository.php'
        );
        $vagaCtrl = $this->readProjectFile(
            'app/adms/Controllers/rh/RhVagasCandidatos.php'
        );
        $candCtrl = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosVagas.php'
        );

        self::assertStringContainsString('function sincronizarCandidatosDaVaga', $repo);
        self::assertStringContainsString('function sincronizarVagasDoCandidato', $repo);
        self::assertStringContainsString('sincronizarCandidatosDaVaga', $vagaCtrl);
        self::assertStringContainsString('canManagePipelineByVagaId', $vagaCtrl);
        self::assertStringContainsString('sincronizarVagasDoCandidato', $candCtrl);
        self::assertStringContainsString('RhCandidatoPermissionService::canAccessCandidato', $candCtrl);
    }

    public function testStatusUpdateWritesImmutableHistory(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhVagasRepository.php'
        );

        self::assertStringContainsString('FOR UPDATE', $source);
        self::assertStringContainsString('RhCandidaturaHistoricoRepository', $source);
        self::assertStringContainsString('TIPO_MOVIMENTADA', $source);
        self::assertStringContainsString('TIPO_VINCULADA', $source);
        self::assertStringContainsString('TIPO_DESVINCULADA', $source);
    }

    public function testCandidatoViewLoadsHistoricoTimeline(): void
    {
        $controller = $this->readProjectFile(
            'app/adms/Controllers/rh/RhCandidatosView.php'
        );
        $view = $this->readProjectFile(
            'app/adms/Views/rh/candidatos/view.php'
        );

        self::assertStringContainsString('listByCandidato', $controller);
        self::assertStringContainsString('historico_candidatura', $controller);
        self::assertStringContainsString('Linha do tempo da candidatura', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
