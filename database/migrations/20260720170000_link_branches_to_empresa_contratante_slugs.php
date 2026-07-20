<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Liga estabelecimentos (adms_branches) aos slugs de empresa_contratante do usuário.
 *
 * Os valores gravados em adms_users.empresa_contratante continuam sendo slugs
 * (tiaraju_farma, lab_tiaraju_matriz, lab_tiaraju_filial) — não há rename de dado.
 * O que muda é o rótulo (nome fantasia) e o code da filial para permitir relacionamento.
 */
final class LinkBranchesToEmpresaContratanteSlugs extends AbstractMigration
{
    /** @var array<string, string> cnpj_digits => slug */
    private const CNPJ_TO_SLUG = [
        '23739581000183' => 'tiaraju_farma',
        '08352440000110' => 'lab_tiaraju_matriz',
        '08352440000209' => 'lab_tiaraju_filial',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        foreach (self::CNPJ_TO_SLUG as $cnpj => $slug) {
            $this->execute(sprintf(
                "UPDATE adms_branches SET code = '%s' WHERE cnpj = '%s' LIMIT 1",
                $slug,
                $cnpj
            ));
        }
    }

    public function down(): void
    {
        // Não reverte codes automaticamente (podem ter sido editados na UI).
    }
}
