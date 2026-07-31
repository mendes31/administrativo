<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Catálogo default de itens de offboarding (Expand Fase 4).
 */
final class RhOffboardingItemCatalog
{
    /**
     * @return list<array{codigo: string, titulo: string, obrigatorio: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['codigo' => 'devolucao_equipamentos', 'titulo' => 'Devolução de equipamentos / crachá', 'obrigatorio' => true],
            ['codigo' => 'revogar_acessos', 'titulo' => 'Revogar acessos de sistemas (mapa TI / Acessos)', 'obrigatorio' => true],
            ['codigo' => 'email_corporativo', 'titulo' => 'Desativar / redirecionar e-mail corporativo', 'obrigatorio' => true],
            ['codigo' => 'documentos_dp', 'titulo' => 'Encaminhar documentação ao DP', 'obrigatorio' => true],
            ['codigo' => 'entrevista_desligamento', 'titulo' => 'Entrevista de desligamento', 'obrigatorio' => false],
            ['codigo' => 'conhecimento_transferido', 'titulo' => 'Transferência de conhecimento / pendências', 'obrigatorio' => false],
            ['codigo' => 'beneficios', 'titulo' => 'Orientação sobre benefícios pós-desligamento', 'obrigatorio' => false],
        ];
    }
}
