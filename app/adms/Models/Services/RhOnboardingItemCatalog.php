<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Catálogo default de itens de onboarding (Expand Fase 4).
 */
final class RhOnboardingItemCatalog
{
    /**
     * @return list<array{codigo: string, titulo: string, obrigatorio: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['codigo' => 'conta_acesso', 'titulo' => 'Validar acesso ao Portal (login e senha)', 'obrigatorio' => true],
            ['codigo' => 'email_corporativo', 'titulo' => 'Provisionar e-mail corporativo', 'obrigatorio' => true],
            ['codigo' => 'equipamentos', 'titulo' => 'Entregar equipamentos / crachá', 'obrigatorio' => true],
            ['codigo' => 'apresentacao_equipe', 'titulo' => 'Apresentação à equipe e gestor', 'obrigatorio' => true],
            ['codigo' => 'treinamentos_obrigatorios', 'titulo' => 'Agendar treinamentos obrigatórios', 'obrigatorio' => true],
            ['codigo' => 'politicas_internas', 'titulo' => 'Ciência das políticas internas', 'obrigatorio' => false],
            ['codigo' => 'beneficios', 'titulo' => 'Orientação sobre benefícios', 'obrigatorio' => false],
            ['codigo' => 'experiencia_90', 'titulo' => 'Agendar avaliação de experiência (90 dias)', 'obrigatorio' => false],
        ];
    }
}
