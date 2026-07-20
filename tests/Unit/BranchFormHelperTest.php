<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\BranchFormHelper;
use PHPUnit\Framework\TestCase;

final class BranchFormHelperTest extends TestCase
{
    public function testNormalizeCnpjAndFormat(): void
    {
        $digits = BranchFormHelper::normalizeCnpj('08.352.440/0001-10');
        $this->assertSame('08352440000110', $digits);
        $this->assertSame('08.352.440/0001-10', BranchFormHelper::formatCnpj($digits));
    }

    public function testNormalizeFormCopiesFantasiaToName(): void
    {
        $payload = BranchFormHelper::normalizeFormPayload([
            'nome_fantasia' => 'Afra Pharma',
            'razao_social' => 'LABORATORIO TIARAJU ALIMENTOS E COSMETICOS S/A',
            'establishment_type' => 'matriz',
            'cnpj' => '08.352.440/0001-10',
            'code' => 'MATRIZ',
            'name' => '',
        ]);

        $this->assertSame('Afra Pharma', $payload['name']);
        $this->assertSame('Afra Pharma', $payload['nome_fantasia']);
        $this->assertSame('matriz', $payload['establishment_type']);
        $this->assertSame('08352440000110', $payload['cnpj']);
    }

    public function testComposeAddressLine(): void
    {
        $line = BranchFormHelper::composeAddressLine([
            'logradouro' => 'Avenida Sagrada Familia',
            'numero' => '2924',
            'complemento' => 'Anexo I',
            'bairro' => 'Jose Alcebiades Oliveira',
            'municipio' => 'Santo Ângelo',
            'uf' => 'RS',
            'cep' => '98805678',
        ]);

        $this->assertStringContainsString('Avenida Sagrada Familia, 2924', $line);
        $this->assertStringContainsString('Santo Ângelo/RS', $line);
        $this->assertStringContainsString('98805-678', $line);
    }

    public function testTypeLabel(): void
    {
        $this->assertSame('Matriz', BranchFormHelper::typeLabel('matriz'));
        $this->assertSame('Filial', BranchFormHelper::typeLabel('filial'));
    }
}
