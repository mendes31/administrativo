<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Interruptor de envio do worker SMTP de entrevistas na configuração de e-mail.
 * Default 0 (desativado): comportamento atual preservado até ativação manual.
 */
final class AddRhEntrevistaSendToggleToAdmsEmailConfig extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_email_config')) {
            return;
        }

        $table = $this->table('adms_email_config');
        if (!$table->hasColumn('rh_entrevista_send_enabled')) {
            $table->addColumn('rh_entrevista_send_enabled', 'boolean', [
                'default' => 0,
                'null' => false,
                'after' => 'test_recipient',
                'comment' => 'Ativa o envio real do worker de comunicações de entrevista',
            ])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_email_config')) {
            return;
        }

        $table = $this->table('adms_email_config');
        if ($table->hasColumn('rh_entrevista_send_enabled')) {
            $table->removeColumn('rh_entrevista_send_enabled')->update();
        }
    }
}
