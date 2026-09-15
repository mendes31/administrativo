<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Natureza "devolucao" para E Dev Venda (não existe opção Entrada na classificação).
 * Não esvazia o cache: a natureza vale na hora no dashboard.
 */
final class CrmSalesUsageDevolucao extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('crm_sales_usage_nature')) {
            return;
        }

        $this->execute(
            "UPDATE crm_sales_usage_nature
             SET natureza = 'devolucao', updated_at = NOW()
             WHERE natureza <> 'devolucao'
               AND (
                    usage_id = 42
                    OR usage_name LIKE 'E Dev Venda%'
                    OR usage_name LIKE 'E%Dev%Venda%'
               )"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('crm_sales_usage_nature')) {
            return;
        }

        $this->execute(
            "UPDATE crm_sales_usage_nature
             SET natureza = 'ignorar', updated_at = NOW()
             WHERE natureza = 'devolucao'"
        );
    }
}
