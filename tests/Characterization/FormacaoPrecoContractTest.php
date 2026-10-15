<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class FormacaoPrecoContractTest extends TestCase
{
    public function testRoutesDirectoryAndMenuAreWired(): void
    {
        $acl = $this->readProjectFile('routes/LoadPageAdmAccessLevel.php');
        self::assertStringContainsString("'pricing'", $acl);

        $legacy = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('"pricing"', $legacy);

        $menu = $this->readProjectFile('app/adms/Views/partials/menu.php');
        self::assertStringContainsString("'id' => 'formacao_preco'", $menu);
        self::assertStringContainsString('Formação de Preço', $menu);
        self::assertStringContainsString("'permission' => 'FormacaoPreco'", $menu);
        self::assertStringContainsString('formacao-preco', $menu);
        self::assertStringContainsString('formacao-preco-pdf', $menu);
    }

    public function testMigrationRegistersClosedAclThenCopiesCrmDashboard(): void
    {
        $source = $this->readProjectFile('database/migrations/20261015140000_create_formacao_preco.php');
        foreach ([
            'FormacaoPreco',
            'FormacaoPrecoCreate',
            'FormacaoPrecoUpdate',
            'FormacaoPrecoView',
            'FormacaoPrecoDelete',
            'formacao_precos',
            'formacao_preco_itens',
            'Comercial - Formação de Preço',
            'pricing',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        self::assertStringContainsString('SELECT 0, al.id', $source);
        self::assertStringContainsString('CrmDashboard', $source);
        self::assertStringNotContainsString('SELECT 1, al.id', $source);
    }

    public function testPdfPageIsClosedAclAndWired(): void
    {
        $source = $this->readProjectFile('database/migrations/20261015160000_register_formacao_preco_pdf_page.php');
        self::assertStringContainsString('FormacaoPrecoPdf', $source);
        self::assertStringContainsString('formacao-preco-pdf', $source);
        self::assertStringContainsString('SELECT 0, al.id', $source);
        self::assertStringContainsString('FormacaoPrecoView', $source);

        $ctrl = $this->readProjectFile('app/adms/Controllers/pricing/FormacaoPrecoPdf.php');
        self::assertStringContainsString('FormacaoPrecoPdfService', $ctrl);
        self::assertStringContainsString('A4-L', $ctrl);
        self::assertStringContainsString('SetHTMLHeader', $ctrl);

        $service = $this->readProjectFile('app/adms/Models/Services/FormacaoPrecoPdfService.php');
        self::assertStringContainsString('RL real %', $service);
        self::assertStringContainsString('Fator por dentro', $service);
        self::assertStringContainsString('Lucro projeto', $service);
    }

    public function testControllersExist(): void
    {
        foreach ([
            'app/adms/Controllers/pricing/FormacaoPreco.php',
            'app/adms/Controllers/pricing/FormacaoPrecoCreate.php',
            'app/adms/Controllers/pricing/FormacaoPrecoUpdate.php',
            'app/adms/Controllers/pricing/FormacaoPrecoView.php',
            'app/adms/Controllers/pricing/FormacaoPrecoDelete.php',
            'app/adms/Controllers/pricing/FormacaoPrecoPdf.php',
            'app/adms/Models/Services/FormacaoPrecoCalculator.php',
            'docs/manual/content/formacao_preco/formacao-preco.html',
            'docs/manual/content/formacao_preco/formacao-preco-pdf.html',
        ] as $file) {
            self::assertFileExists(PROJECT_ROOT . '/' . $file);
        }
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
