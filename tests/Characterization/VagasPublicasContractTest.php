<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasPublicasContractTest extends TestCase
{
    public function testMigrationRegistersPublicPage(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719236000_register_rh_vagas_publicas_page.php'
        );
        self::assertStringContainsString('RhVagasPublicas', $migration);
        self::assertStringContainsString('vagas-abertas', $migration);
        self::assertStringContainsString('public_page = 1', $migration);
    }

    public function testRepositoryUsesDedicatedPublicQueries(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/RhVagasRepository.php');
        self::assertStringContainsString('function listPublicadas', $repo);
        self::assertStringContainsString('function getPublicadaById', $repo);
        self::assertStringContainsString('v.publicada = 1', $repo);
        self::assertStringContainsString("v.status = 'aberta'", $repo);
        self::assertStringContainsString('data_limite_inscricao', $repo);
        self::assertStringNotContainsString('observacoes', $this->publicSelectSnippet($repo));
        self::assertStringNotContainsString('responsavel', $this->publicSelectSnippet($repo));
    }

    public function testControllerAllowsGetAndPostApplyWithoutAdminLayout(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhVagasPublicas.php');
        self::assertStringContainsString('POST', $controller);
        self::assertStringContainsString('Views/rh/public/layout.php', $controller);
        self::assertStringNotContainsString('PageLayoutService', $controller);
        self::assertStringNotContainsString('getById(', $controller);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('RhVagasPublicas', $routes);
    }

    private function publicSelectSnippet(string $repo): string
    {
        $start = strpos($repo, 'PUBLIC_SELECT');
        self::assertNotFalse($start);
        return substr($repo, $start, 500);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
