<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Catálogo default de documentos de pré-admissão (Expand).
 */
final class RhPreAdmissaoDocumentoCatalog
{
    /**
     * @return list<array{codigo: string, titulo: string, obrigatorio: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['codigo' => 'documento_identidade', 'titulo' => 'Documento de identidade (RG/CNH)', 'obrigatorio' => true],
            ['codigo' => 'cpf', 'titulo' => 'CPF', 'obrigatorio' => true],
            ['codigo' => 'comprovante_residencia', 'titulo' => 'Comprovante de residência', 'obrigatorio' => true],
            ['codigo' => 'titulo_eleitor', 'titulo' => 'Título de eleitor', 'obrigatorio' => false],
            ['codigo' => 'ctps', 'titulo' => 'CTPS (física ou digital)', 'obrigatorio' => true],
            ['codigo' => 'pis_pasep', 'titulo' => 'PIS/PASEP', 'obrigatorio' => false],
            ['codigo' => 'certidao_nascimento_casamento', 'titulo' => 'Certidão de nascimento ou casamento', 'obrigatorio' => false],
            ['codigo' => 'dados_bancarios', 'titulo' => 'Dados bancários', 'obrigatorio' => true],
        ];
    }
}
