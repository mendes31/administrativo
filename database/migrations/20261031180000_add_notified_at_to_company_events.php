<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Marca eventos já visíveis como notificados para não reenviar push em massa.
 * Eventos ativos com "Publicar a partir de" no futuro ficam pendentes e disparam ao entrar na janela.
 */
final class AddNotifiedAtToCompanyEvents extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_company_events')) {
            return;
        }

        $table = $this->table('adms_company_events');
        if (!$table->hasColumn('notified_at')) {
            $table
                ->addColumn('notified_at', 'datetime', [
                    'null' => true,
                    'comment' => 'Quando o push de publicação foi disparado. NULL = ainda pendente.',
                ])
                ->update();
        }

        $this->execute(
            'UPDATE adms_company_events
             SET notified_at = NOW()
             WHERE notified_at IS NULL
               AND (
                    ativo = 0
                    OR (expire_at IS NOT NULL AND expire_at <= NOW())
                    OR publish_at IS NULL
                    OR publish_at <= NOW()
               )'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_company_events')) {
            return;
        }

        $table = $this->table('adms_company_events');
        if ($table->hasColumn('notified_at')) {
            $table->removeColumn('notified_at')->update();
        }
    }
}
